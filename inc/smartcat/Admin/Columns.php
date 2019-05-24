<?php
/**
 * @package    Smartcat Translation Manager for Wordpress
 *
 * @author     Smartcat <support@smartcat.ai>
 * @copyright  (c) 2019 Smartcat. All Rights Reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       http://smartcat.ai
 */

namespace SmartCAT\WP\Admin;

use Psr\Container\ContainerInterface;
use SmartCAT\WP\Connector;
use SmartCAT\WP\Helpers\Language\LanguageConverter;
use SmartCAT\WP\WP\HookInterface;

final class Columns implements HookInterface
{

    static function bulk_handler($redirect_to, $action_name, $post_ids)
    {
        if ('send_to_smartcat' === $action_name) {
            $redirect_to = add_query_arg('submit_for_translation_success', 'success', $redirect_to);
        }

        return $redirect_to;
    }

    static function register_bulk_actions($bulk_actions)
    {
        $bulk_actions['send_to_smartcat'] = __('Submit for translation', 'translation-connectors');

        return $bulk_actions;
    }

    static function register_bulk_action_notices()
    {
        //TODO: переехать на Notice
        if (! empty($_REQUEST['submit_for_translation_success'])) {
            if ('success' === $_REQUEST['submit_for_translation_success']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Posts have been sent for translation',
                        'translation-connectors') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Posts have not been sent for translation',
                        'translation-connectors') . '</p></div>';
            }
        }
    }

    static function add_post_column($columns)
    {
        return self::add_column($columns, 'comments');
    }

    static protected function add_column($columns, $before)
    {
        if ($n = array_search($before, array_keys($columns))) {
            $end     = array_slice($columns, $n);
            $columns = array_slice($columns, 0, $n);
        }

        $columns['smartCAT'] = esc_html(__('Submit for translation', 'translation-connectors'));

        return isset($end) ? array_merge($columns, $end) : $columns;
    }

    static function post_column($column, $post_id)
    {
        //чтобы не отражалось на других колонках
        if (false === strpos($column, 'smartCAT')) {
            return;
        }

        //требование отображать кнопку только в случае, если нет переводов на все доступные языки
        /** @noinspection PhpUndefinedFunctionInspection */
        $pll_slugs = pll_languages_list([ 'fields' => 'slug' ]);

        //fixme: фиговое решение, но другого не нашел, возможно, переработать
        $translation_posts = $translation_slugs = [];
        foreach ($pll_slugs as $slug) {
            /** @noinspection PhpUndefinedFunctionInspection */
            $post = pll_get_post($post_id, $slug);
            if ($post) {
                $translation_posts[] = $post;
                $translation_slugs[] = $slug;
            }
        }

        if (count($translation_posts) !== count($pll_slugs)) {
            /** @var ContainerInterface $container */
            $container = Connector::get_container();
            /** @var LanguageConverter $converter */
            $converter = $container->get('language.converter');

            /** @noinspection PhpUndefinedFunctionInspection */
            $post_language_slug = pll_get_post_language($post_id);

            $attributes = [
                'data_post_id'              => esc_attr($post_id),
                'id'                        => esc_attr("translation-connectors-$post_id"),
                'data-author'               => esc_attr(get_the_author_meta('nicename')),
                'data-source-language-slug' => esc_attr($post_language_slug),
                'data-source-language-name' => esc_attr($converter->get_polylang_language_name_by_slug($post_language_slug)),
                'data-post-pll-slugs'       => esc_attr(implode(',', $pll_slugs)),
                'data-translation-slugs'    => esc_attr(implode(',', $translation_slugs))
            ];

            $attributes_string = self::make_attributes_string($attributes);
            $link_text         = __('Submit for translation', 'translation-connectors');

            $result_string = "<a class='send-to-smartcat-anchor' 
                                href='#' 
                                $attributes_string>
                                {$link_text}
                                </a>";
        } else {
            $result_string = __('The article has already been translated to all languages', 'translation-connectors');
        }

        echo $result_string;
    }

    public function register_hooks()
    {
        self::register_columns();
    }

    static function register_columns()
    {
        $post_types = [ 'post' => 'post', 'page' => 'page' ];
        foreach ($post_types as $type) {
            add_filter('manage_' . ('edit-' . $type) . '_columns', [ self::class, 'add_post_column' ], 100);
            add_action('manage_' . ($type . '_posts') . '_custom_column', [ self::class, 'post_column' ], 10, 2);
        }

        // http://wpengineer.com/2803/create-your-own-bulk-actions/
        $pages_ids = [ 'edit-page', 'edit-post' ];
        foreach ($pages_ids as $page) {
            add_filter('bulk_actions-' . $page, [ self::class, 'register_bulk_actions' ]);
            add_filter('handle_bulk_actions-' . $page, [ self::class, 'bulk_handler' ], 10, 3);
        }
        add_action('admin_notices', [ self::class, 'register_bulk_action_notices' ]);
    }

    static function make_attributes_string($array)
    {
        $result = ' ';
        foreach ($array as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $value  = esc_attr($value);
            $result .= "{$key}='{$value}'";
        }

        return $result;
    }
}
