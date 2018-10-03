<?php

namespace SmartCAT\WP\Admin;

use SmartCAT\WP\DITrait;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\WP\HookInterface;

final class Frontend implements HookInterface {
	use DITrait;

	static public function custom_admin_footer_js() {
		$modal_title          = __( 'Send for translation', 'translation-connectors' ); // выбранные статьи для перевода
		$title                = __( 'Title', 'translation-connectors' ); // заголовок
		$author               = __( 'Author', 'translation-connectors' ); // автор
		$status               = __( 'Status', 'translation-connectors' ); // статус
		$source_language      = __( 'Source language', 'translation-connectors' ); // язык оригинала
		$target_language_text = __( 'Target language', 'translation-connectors' ); // Перевести на

		$style = 'display:none;';

		$container = self::get_container();
		/** @var LanguageConverter $languages_converter */
		$languages_converter         = $container->get( 'language.converter' );
		$unsupported_languages_array = [];
		$languages                   = $languages_converter->get_polylang_languages_supported_by_sc( $unsupported_languages_array );
		$total_languages_count       = count( $languages );

		?>

		<div id="smartcat-modal-window" style="<?php echo esc_attr( $style ); ?>">
			<h3><?php echo $modal_title ?></h3>
			<div class="smartcat-source-language-error"></div>
			<form method="POST">
				<div>
					<table class="wp-list-table widefat fixed striped languages">
						<thead>
						<tr>
							<td><?php echo $title; ?></td>
							<td><?php echo $author; ?></td>
							<td><?php echo $status; ?></td>
							<td><?php echo $source_language; ?></td>
						</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<?php
				$unsupported_languages_count = count( $unsupported_languages_array );
				if ( $unsupported_languages_count ) {
					$message = $unsupported_languages_count === 1
						? __( 'Translation into %s language is not supported', 'translation-connectors' )
						: __( 'Translation into %s languages is not supported', 'translation-connectors' );

					$languages_list = implode( ', ', $unsupported_languages_array );
					?>
					<div class='smartcat-target-language-error'><?php printf( $message, $languages_list ); ?></div>
					<?php
				}
				?>

				<div class="sc-select-language-block">
					<div><?php echo $target_language_text ?>: <select name="sc-target-lang[]">
							<option value=""><?php echo __( 'Choose language', 'translation-connectors' ) ?></option>

							<?php
							foreach ( $languages as $key => $value ) {
								echo '<option value="' . $key . '">' . $value . '</option>';
							}

							echo '</select>';

							if ( $total_languages_count > 1 ) {
								echo '<div class="add-language"></div>';
							}
							?>

					</div>
				</div>
				<input type="submit"
				       value="<?php echo esc_attr( __( 'Submit for translation', 'translation-connectors' ) ); ?>"/>
				<input type="hidden" name="posts" id="smartcat-modal-window-posts" value=""/>
			</form>
		</div>

		<?php

		//TODO: переделать на средства WP
		$plugin_dir_url = plugin_dir_url( SMARTCAT_PLUGIN_FILE );
		$css            = $plugin_dir_url . 'css/smartcat.css';
		echo '<link rel="stylesheet" href="' . $css . '" type="text/css" />';
	}

	static public function queue_my_admin_scripts() {
		wp_enqueue_script( 'jquery-ui-dialog' );

		$container      = self::get_container();
		$plugin_dir_url = plugin_dir_url( SMARTCAT_PLUGIN_FILE );
		/** @var LanguageConverter $languages_converter */
		$languages_converter = $container->get( 'language.converter' );
		$languages           = $languages_converter->get_polylang_languages_supported_by_sc();
		$path_to_js          = plugins_url( '/' . SMARTCAT_PLUGIN_NAME . '/js/smartcat.js' );

		$pll_locales_array = $languages_converter->get_polylang_slugs_supported_by_sc();

		wp_register_script( 'smartcat-frontend', $path_to_js );

		$translation_and_vars = [
			//переменные
			'pluginUrl'                     => $plugin_dir_url,
			'adminUrl'                      => admin_url(),
			'smartcat_table_prefix'         => $container->getParameter( 'plugin.table.prefix' ),
			'totalLanguages'                => count( $languages ),
			'pll_languages_supported_by_sc' => $pll_locales_array,
			//переводы
			'sourceLanguageNotSupported'    => __( 'won\'t be translated. Source language is not supported',
				'translation-connectors' ),
			'postsAreNotChoosen'            => __( 'Please, choose posts or pages for translation',
				'translation-connectors' ),
			'workflowStagesAreNotSelected'  => __( 'Workflow stages are not selected', 'translation-connectors' ),
            'postsAreAlreadyTranslated'     => __( 'Selected posts or pages have already been translated', 'translation-connectors' )
		];

		wp_localize_script( 'smartcat-frontend', 'SmartcatFrontend', $translation_and_vars );
		wp_enqueue_script( 'smartcat-frontend' );

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ self::class, 'queue_my_admin_scripts' ] );
		add_action( 'admin_footer', [ self::class, 'custom_admin_footer_js' ] );
	}
}