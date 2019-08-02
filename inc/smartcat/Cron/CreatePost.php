<?php
/**
 * Smartcat Translation Manager for WordPress
 *
 * @package Smartcat Translation Manager for WordPress
 * @author Smartcat <support@smartcat.ai>
 * @copyright (c) 2019 Smartcat. All Rights Reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @link http://smartcat.ai
 */

namespace SmartCAT\WP\Cron;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class ClearDeletedProject
 *
 * @package SmartCAT\WP\Cron
 */
class CreatePost extends CronAbstract {
	/**
	 * @return mixed
	 */
	public function get_interval() {
		$schedules['1m'] = [
			'interval' => 60,
			'display'  => __( 'Every minute', 'translation-connectors' ),
		];

		return $schedules;
	}

	/**
	 *
	 */
	public function run() {
		if ( ! SmartCAT::is_active() ) {
			return;
		}

		Logger::event( 'createPost', 'Start creating posts' );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		foreach ( $statistic_repository->get_export() as $statistic ) {
			$this->create_item( $statistic );
		}

		Logger::event( 'createPost', 'End creating posts' );
	}

	/**
	 * @param Statistics $item
	 */
	private function create_item( $item ) {
		Logger::event( 'createPost', "Start queue item '{$item->get_document_id()}'" );

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		try {
			$result = $sc->getDocumentExportManager()->documentExportDownloadExportResult( $item->get_task_id() );
			if ( 204 === $result->getStatusCode() ) {
				Logger::event( 'createPost', "Export not done yet '{$item->get_document_id()}'" );
				return;
			} elseif ( 200 === $result->getStatusCode() ) {
				Logger::event( 'createPost', "Download document '{$item->get_document_id()}'" );
				$response_body = $result->getBody()->getContents();
				$html          = new \DOMDocument();
				$html->loadHTML( $response_body );
				$title    = $html->getElementsByTagName( 'title' )->item( 0 )->nodeValue;
				$body     = '';
				$children = $html->getElementsByTagName( 'body' )->item( 0 )->childNodes;
				foreach ( $children as $child ) {
					$body .= $child->ownerDocument->saveHTML( $child );
				}

				$replace_count = 0;
				$iteration     = 0;

				do {
					$body = preg_replace_callback(
						'%<sc-shortcode-([\w]+)\s+sc-type="([\w]+)"\s+sc-single="(true|false)"\s*(\s+.+?)?>(.*?)<\/sc-shortcode-\1>%s',
						function( $matches ) {
							if ( 'true' === $matches[3] ) {
								return "[{$matches[2]}{$matches[4]}]";
							} else {
								return "[{$matches[2]}{$matches[4]}]{$matches[5]}[/{$matches[2]}]";
							}
						},
						$body,
						-1,
						$replace_count
					);

					$iteration++;

					if ( $iteration >= 50 ) {
						Logger::warning( 'Limit exceeded', "Shortcodes replacing iteration limit exceeded in returned post from SC '{$title}'" );
					}
				} while ( $replace_count && ( $iteration < 50 ) );

				if ( $statistics->get_target_post_id() ) {
					Logger::event( 'createPost', "Updating post {$statistics->get_target_post_id()} '{$item['documentID']}'" );
					wp_update_post(
						[
							'ID'             => $item->get_target_post_id(),
							'post_title'     => $title,
							'post_content'   => $body,
							'post_status'    => 'draft',
						]
					);
				} else {
					Logger::event( 'createPost', "Generate new post '{$item->get_document_id()}'" );
					$post           = get_post( $item->get_post_id() );
					$thumbnail_id   = get_post_meta( $item->get_post_id(), '_thumbnail_id', true );
					$target_post_id = wp_insert_post(
						[
							'post_title'     => $title,
							'menu_order'     => $post->menu_order,
							'comment_status' => $post->comment_status,
							'ping_status'    => $post->ping_status,
							'pinged'         => $post->pinged,
							'post_content'   => $body,
							'post_status'    => 'draft',
							'post_author'    => $post->post_author,
							'post_password'  => $post->post_password,
							'post_type'      => $post->post_type,
							'meta_input'     => [ '_thumbnail_id' => $thumbnail_id ],
						]
					);
					$item->set_target_post_id( $target_post_id );

					/** @noinspection PhpUndefinedFunctionInspection */
					pll_set_post_language( $target_post_id, $item->get_target_language() );

					/** @var LanguageConverter $converter */
					$converter = $container->get( 'language.converter' );
					$slug_list = $converter->get_polylang_locales_to_slugs();

					if ( isset( $slug_list[ $item->get_target_language() ] ) ) {
						/** @noinspection PhpUndefinedFunctionInspection */
						$pll_info          = pll_get_post_translations( $item->get_post_id() );
						$slug              = $slug_list[ $item->get_target_language() ];
						$pll_info[ $slug ] = $target_post_id;
						/** @noinspection PhpUndefinedFunctionInspection */
						pll_save_post_translations( $pll_info );

						$categories = wp_get_post_categories( $item->get_post_id() );
						$tags       = wp_get_post_tags( 1, [ 'fields' => 'ids' ] );

						$translate_categories = [];
						foreach ( $categories as $category ) {
							/** @noinspection PhpUndefinedFunctionInspection */
							$translate_category = pll_get_term_translations( $category );
							if ( isset( $translate_category[ $slug ] ) ) {
								$translate_categories[] = $translate_category[ $slug ];
							}
						}

						$translate_tags = [];
						foreach ( $tags as $tag ) {
							/** @noinspection PhpUndefinedFunctionInspection */
							$translate_tag = pll_get_term_translations( $tag );
							if ( isset( $translate_tag[ $slug ] ) ) {
								$translate_tags[] = $translate_tag[ $slug ];
							}
						}
						if ( count( $translate_categories ) > 0 ) {
							wp_set_post_categories( $target_post_id, $translate_categories );
						}
						if ( count( $translate_tags ) > 0 ) {
							wp_set_post_tags( $target_post_id, $translate_tags );
						}
					}
				}

				$item->set_status( Statistics::STATUS_COMPLETED );
				Logger::event( 'createPost', "Set completed status for statistic id '{$item->get_id()}'" );
				$statistic_repository->save( $item );
				Logger::event( 'createPost', "Generated post for '{$item->get_document_id()}' and status = {$item->get_status()}" );
			}
		} catch ( ClientErrorException $e ) {
			if ( 404 === $e->getResponse()->getStatusCode() ) {
				$item->set_status( Statistics::STATUS_SENDED );
				$statistic_repository->save( $item );
				Logger::event( 'createPost', "Export document '{$item->get_document_id()}' does't exists. Try again" );
			} elseif ( $item->get_error_count() < 360 ) {
				$item->inc_error_count();
				$statistic_repository->save( $item );
				Logger::event( 'createPost', "New {$item->get_error_count()} try of '{$item->get_document_id()}'" );
			}
			Logger::error(
				"Document {$item->get_document_id()}, download translate error",
				"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}"
			);
		} catch ( \Throwable $e ) {
			$item->set_status( Statistics::STATUS_SENDED );
			$statistic_repository->save( $item );
			Logger::error( "Document {$item->get_document_id()}, download translate error", "Message: {$e->getMessage()}" );
		}

		Logger::event( 'createPost', "End queue item '{$item->get_document_id()}'" );
	}
}
