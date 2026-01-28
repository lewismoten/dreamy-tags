<?php
/**
 * Plugin Name: Dreamy Tags
 * Plugin URI: https://github.com/lewismoten/dreamy-tags
 * Description: A specialized tag cloud generator designed for blogs, archives, and taxonomy-based layouts. Dreamy Tags allows you to filter displayed tags by category, exclude organizational tags, and control minimum usage thresholds for cleaner, more meaningful tag clouds.
 * Version: 1.0.74
 * Author: Lewis Moten
 * Author URI: https://lewismoten.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dreamy-tags
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the widget class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dreamy-tags-widget.php';

function lewismoten_dreamy_tags_version() {
    $version = '1.0.74';
    return $version;
}

function lewismoten_dreamy_tags_register_widget() {
    register_widget( 'LewismotenDreamyTagsWidget' );
}
add_action( 'widgets_init', 'lewismoten_dreamy_tags_register_widget' );

function lewismoten_dreamy_tags_shortcode($atts) {
    $a = shortcode_atts(array(
        'cat' => '',
        'children' => false,
        'tags' =>  '',
        'exclude_tags'  => '',
        'auto_exclude' => true,
        'min_count' => 2,
    ), $atts);

    $cat_array = !empty($a['cat']) ? array_map('intval', explode(',', $a['cat'])) : array();
    $tag_array = !empty($a['tags']) ? array_map('intval', explode(',', $a['tags'])) : array();
    $exclude_tags_array = !empty($a['exclude_tags']) ? array_map('intval', explode(',', $a['exclude_tags'])) : array();
    
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
    if(class_exists('LewismotenDreamyTagsWidget')) {
        the_widget('LewismotenDreamyTagsWidget', array(
            'filter_category_ids' => $cat_array,
            'children'            => $a['children'],
            'filter_tag_ids'      => $tag_array,
            'exclude_tag_ids'     => $exclude_tags_array,
            'auto_exclude_filter' => $a['auto_exclude'],
            'min_count'           => $a['min_count'],
        ));
    }
    return ob_get_clean();
}
add_shortcode('dreamy_tags', 'lewismoten_dreamy_tags_shortcode');

function lewismoten_dreamy_tags_flat_ids(&$a, $key) {
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
    lewismoten_dreamy_tags_flat_ids($attributes, 'cat');
    lewismoten_dreamy_tags_flat_ids($attributes, 'tags');
    lewismoten_dreamy_tags_flat_ids($attributes, 'exclude_tags');

    $html = lewismoten_dreamy_tags_shortcode( $attributes );
    if ( is_admin() ) {
        $html = preg_replace('/\s+href=("|\').*?\1/i', '', $html);
    }
    return $html;
}
function lewismoten_dreamy_tags_register_block_render() {
    register_block_type( __DIR__, array(
        'render_callback' => 'lewismoten_dreamy_tags_block_render',
    ) );
}
add_action( 'init', 'lewismoten_dreamy_tags_register_block_render' );

function lewismoten_dreamy_tags_styles() {
    $version = lewismoten_dreamy_tags_version();
    wp_register_style('lewismoten_dreamy_tags_styles', false, array(), $version);
    wp_enqueue_style('lewismoten_dreamy_tags_styles');
    wp_add_inline_style('lewismoten_dreamy_tags_styles', "
        .lewismoten-dreamy-tags a { 
            display: inline-block; margin: 4px; padding: 6px 12px;
            background: rgba(144, 238, 144, 0.1); color: #2e7d32 !important;
            border: 1px solid #a5d6a7; border-radius: 20px; text-decoration: none;
            transition: all 0.3s ease;
        }
        .lewismoten-dreamy-tags a:hover { 
            background: #a5d6a7; color: #fff !important;
            box-shadow: 0 0 15px rgba(165, 214, 167, 0.6); transform: translateY(-2px);
        }
    ");
}
add_action('wp_head', 'lewismoten_dreamy_tags_styles');
function lewismoten_dreamy_tags_assets() {
    $version = lewismoten_dreamy_tags_version();
    $name = 'lewismoten_dreamy_tags_block_editor';

    wp_register_script(
        $name,
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
    wp_localize_script($name, 'lewismoten_dreamy_tags_block',
        array(
            'previewImage' => plugins_url( 'images/block-preview.png', __FILE__ ),
        )
    );
    wp_enqueue_script($name);
}
add_action( 'enqueue_block_editor_assets', 'lewismoten_dreamy_tags_assets' );
