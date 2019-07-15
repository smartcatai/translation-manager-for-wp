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

namespace SmartCAT\WP\Queue;

use Http\Client\Common\Exception\ClientErrorException;
use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\DB\Entity\Statistics;
use SmartCAT\WP\DB\Repository\StatisticRepository;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\Helpers\Logger;
use SmartCAT\WP\Helpers\SmartCAT;

/**
 * Class CreatePost
 *
 * @package SmartCAT\WP\Queue
 */
class CreatePost extends QueueAbstract {
	protected $action = 'smartcat_createpost_async';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		if ( ! SmartCAT::is_active() ) {
			sleep( 10 );

			return $item;
		}

		Logger::event( "createPost", "Start queue '{$item['documentID']}'");

		/** @var ContainerInterface $container */
		$container = Connector::get_container();

		/** @var StatisticRepository $statistic_repository */
		$statistic_repository = $container->get( 'entity.repository.statistic' );

		/** @var SmartCAT $sc */
		$sc = $container->get( 'smartcat' );

		$statistics = $statistic_repository->get_one_by( [ 'documentID' => $item['documentID'] ] );
		if ( is_null( $statistics ) ) {
			Logger::error(
				'CreatePost - Statistics object is empty',
				'Can\'t get statistics by documentID - ' . $item['documentID']
			);
			return false;
		}

		try {
			$result = $sc->getDocumentExportManager()->documentExportDownloadExportResult( $item['taskID'] );
			if ( 204 === $result->getStatusCode() ) {
				sleep( 1 );
				Logger::event( "createPost", "Export not done yet '{$item['documentID']}'");
				return $item;
			} elseif ( 200 === $result->getStatusCode() ) {
				Logger::event( "createPost", "Download document '{$item['documentID']}'");
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
						'%<\s*([\w]+)\s+type="sc-shortcode"\s*(\s+.+?)?>((.*?)<\/\1>)?%s',
						function( $matches ) {
							if ( empty( $matches[4] ) ) {
								return "[{$matches[1]}{$matches[2]}]";
							} else {
								return "[{$matches[1]}{$matches[2]}]{$matches[4]}[/{$matches[1]}]";
							}
						},
						$body,
						-1,
						$replace_count
					);

					$iteration++;
				} while ( $replace_count && ( $iteration < 50 ) );

				if ( $statistics->get_target_post_id() ) {
					Logger::event( "createPost", "Updating post {$statistics->get_target_post_id()} '{$item['documentID']}'");
					wp_update_post(
						[
							'ID'             => $statistics->get_target_post_id(),
							'post_title'     => $title,
							'post_content'   => $body,
							'post_status'    => 'draft',
						]
					);
				} else {
					Logger::event( "createPost", "Generate new post '{$item['documentID']}'");
					$post           = get_post( $statistics->get_post_id() );
					$thumbnail_id   = get_post_meta( $statistics->get_post_id(), '_thumbnail_id', true );
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
					$statistics->set_target_post_id( $target_post_id );

					/** @noinspection PhpUndefinedFunctionInspection */
					pll_set_post_language( $target_post_id, $statistics->get_target_language() );

					/** @var LanguageConverter $converter */
					$converter = $container->get( 'language.converter' );
					$slug_list = $converter->get_polylang_locales_to_slugs();

					if ( isset( $slug_list[ $statistics->get_target_language() ] ) ) {
						/** @noinspection PhpUndefinedFunctionInspection */
						$pll_info          = pll_get_post_translations( $statistics->get_post_id() );
						$slug              = $slug_list[ $statistics->get_target_language() ];
						$pll_info[ $slug ] = $target_post_id;
						/** @noinspection PhpUndefinedFunctionInspection */
						pll_save_post_translations( $pll_info );

						$categories = wp_get_post_categories( $statistics->get_post_id() );
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

				$statistics->set_status( Statistics::STATUS_COMPLETED );
				Logger::event( "createPost", "Set completed status for statistic id '{$statistics->get_id()}'");
				$statistic_repository->save( $statistics );
				Logger::event( "createPost", "Generated post for '{$item['documentID']}' and status = {$statistics->get_status()}");
			}
		} catch ( ClientErrorException $e ) {
			if ( 404 === $e->getResponse()->getStatusCode() ) {
				$statistics->set_status( Statistics::STATUS_SENDED );
				$statistic_repository->save( $statistics );
				/** @var Publication $queue */
				$queue = $container->get( 'core.queue.publication' );
				Logger::event( "createPost", "Pushed to publication '{$item['documentID']}'");
				$queue->push_to_queue( $item['documentID'] )->save()->dispatch();
			} elseif ( $statistics->get_error_count() < 360 ) {
				$statistics->inc_error_count();
				$statistic_repository->save( $statistics );
				sleep( 2 );

				Logger::event( "createPost", "New {$statistics->get_error_count()} try of '{$item['documentID']}'");
				return $item;
			}
			Logger::error(
				"Document {$item['documentID']}, download translate error",
				"API error code: {$e->getResponse()->getStatusCode()}. API error message: {$e->getResponse()->getBody()->getContents()}"
			);
		} catch ( \Throwable $e ) {
			$statistics->set_status( Statistics::STATUS_SENDED );
			$statistic_repository->save( $statistics );
			Logger::error( "Document {$item['documentID']}, download translate error","Message: {$e->getMessage()}" );
		}

		Logger::event( "createPost", "End queue '{$item['documentID']}'");

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		// Show notice to user or perform some other arbitrary task...
	}
}
