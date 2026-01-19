<?php
/**
 * Plugin Name:       Dreamy Tags
 * Plugin URI:        https://github.com/lewismoten/dreamy-tags
 * Description:       A specialized tag cloud generator designed for blogs, archives, and taxonomy-based layouts. Dreamy Tags allows you to filter displayed tags by category, exclude organizational tags, and control minimum usage thresholds for cleaner, more meaningful tag clouds.
 * Version:           1.0.47
 * Author:            Lewis Moten
 * Author URI:        https://lewismoten.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dreamy-tags
 * Requires at least: 6.4
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include the widget class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dreamy-tags-widget.php';

function register_lewismoten_dreamy_tags_widget() {
    register_widget( 'Dreamy_Tags_Widget' );
}
add_action( 'widgets_init', 'register_lewismoten_dreamy_tags_widget' );

function lewismoten_dreamy_tags_shortcode($atts) {
    $a = shortcode_atts(array(
        'cat' => '',
        'children' => false,
        'tags' =>  '',
        'exclude'  => '',
        'auto_exclude' => true,
        'min_count' => 2,
    ), $atts);

    $cat_array = !empty($a['cat']) ? array_map('intval', explode(',', $a['cat'])) : array();
    $tag_array = !empty($a['tags']) ? array_map('intval', explode(',', $a['tags'])) : array();
    $exclude_array = !empty($a['exclude']) ? array_map('intval', explode(',', $a['exclude'])) : array();
    
    $a['min_count'] = isset($a['min_count']) ? max(1, intval($a['min_count'])) : 2;

    $a['auto_exclude'] = filter_var( $a['auto_exclude'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE );
    if ( $a['auto_exclude'] === null ) {
        $a['auto_exclude'] = true;
    }

    $a['children'] = filter_var( $a['children'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE );
    if ( $a['children'] === null ) {
        $a['children'] = true;
    }

    ob_start();
    if(class_exists('Dreamy_Tags_Widget')) {
        the_widget('Dreamy_Tags_Widget', array(
            'filter_category_ids' => $cat_array,
            'children'            => $a['children'],
            'filter_tag_ids'      => $tag_array,
            'exclude_tag_ids'     => $exclude_array,
            'auto_exclude_filter' => $a['auto_exclude'],
            'min_count'           => $a['min_count'],
        ));
    }
    return ob_get_clean();
}
add_shortcode('dreamy_tags', 'lewismoten_dreamy_tags_shortcode');

function lewismoten_flat_ids(&$a, $key) {
    if ( isset( $a[ $key ] ) && is_array( $a[ $key ] ) ) {
        $a[ $key ] = implode(
            ',',
            array_values(
                array_filter(
                    array_map( 'strval', $a[ $key ] ),
                    static fn( $v ) => $v !== ''
                )
            )
        );
        return;
    }

    $a[ $key ] = isset( $a[ $key ] ) ? (string) $a[ $key ] : '';
}
function lewismoten_dreamy_tags_block_render( $attributes, $content = '', $block = null ) {
    $attributes = is_array( $attributes ) ? $attributes : array();
    lewismoten_flat_ids($attributes, 'cat');
    lewismoten_flat_ids($attributes, 'tags');
    lewismoten_flat_ids($attributes, 'exclude');

    $html = lewismoten_dreamy_tags_shortcode( $attributes );
    if ( is_admin() ) {
        $html = preg_replace('/\s+href=("|\').*?\1/i', '', $html);
    }
    return $html;
}
function register_lewismoten_dreamy_tags_block() {
    register_block_type( __DIR__, array(
        'render_callback' => 'lewismoten_dreamy_tags_block_render',
    ) );
}
add_action( 'init', 'register_lewismoten_dreamy_tags_block' );

function lewismoten_dreamy_tags_styles() {
    echo '<style>
        .dreamy-tags a { 
            display: inline-block; margin: 4px; padding: 6px 12px;
            background: rgba(144, 238, 144, 0.1); color: #2e7d32 !important;
            border: 1px solid #a5d6a7; border-radius: 20px; text-decoration: none;
            transition: all 0.3s ease;
        }
        .dreamy-tags a:hover { 
            background: #a5d6a7; color: #fff !important;
            box-shadow: 0 0 15px rgba(165, 214, 167, 0.6); transform: translateY(-2px);
        }
    </style>';
}
add_action('wp_head', 'lewismoten_dreamy_tags_styles');

function lewismoten_dreamy_tags_assets() {
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugin_data = get_plugin_data( __FILE__ );
    $version     = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;

    wp_enqueue_style(
        'dreamy-tags-admin-style',
        plugins_url('admin/admin-style.css', __FILE__),
        array(),
        $version
    );

    wp_register_script(
        'dreamy-tags-block-editor',
        plugins_url('block.js', __FILE__),
        array(
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-data',
            'wp-core-data',
            'wp-server-side-render',
        ),
        $version,
        true
    );
    wp_localize_script(
        'dreamy-tags-block-editor',
        'DreamyTagsBlock',
        array(
            'previewImage' => plugins_url( 'assets/block-preview.png', __FILE__ ),
        )
    );
    wp_enqueue_script( 'dreamy-tags-block-editor' );
}
add_action( 'enqueue_block_editor_assets', 'lewismoten_dreamy_tags_assets' );