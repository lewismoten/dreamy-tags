<?php
/**
 * Plugin Name: Dreamy Tags
 * Description: Generates a tag cloud filtered by category and specific tags, with exclusion logic.
 * Version: 1.0.1
 * Author: Lewis Moten
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Dreamy_Tags_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'dreamy_tags_widget',
            'Dreamy Tags',
            array( 'description' => 'A tag cloud filtered by categories and tags.' )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        // 1. Get all post IDs that match the category filter
        $post_args = array(
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        if ( ! empty( $instance['filter_category_ids'] ) ) {
            $post_args['category__in'] = $instance['filter_category_ids'];
        }

        if ( ! empty( $instance['filter_tag_ids'] ) ) {
            $post_args['tag__in'] = $instance['filter_tag_ids'];
        }

        $filtered_post_ids = get_posts( $post_args );

        // 2. Collect all tags used by these specific posts
        $tags_in_use = wp_get_object_terms( $filtered_post_ids, 'post_tag' );
        $tag_ids_to_show = wp_list_pluck( $tags_in_use, 'term_id' );

        // 3. Handle Exclusions
        $exclude_tag_ids = ! empty( $instance['exclude_tag_ids'] ) ? $instance['exclude_tag_ids'] : array();
        
        // If the "Exclude filtered tags" checkbox is checked, add those to the exclusion list
        if ( isset( $instance['auto_exclude_filter'] ) && $instance['auto_exclude_filter'] && ! empty( $instance['filter_tag_ids'] ) ) {
            $exclude_tag_ids = array_unique( array_merge( $exclude_tag_ids, $instance['filter_tag_ids'] ) );
        }

        // Final Filter: Remove excluded IDs from our "To Show" list
        $final_tag_ids = array_diff( $tag_ids_to_show, $exclude_tag_ids );

        if ( ! empty( $final_tag_ids ) ) {
            wp_tag_cloud( array(
                'include' => $final_tag_ids,
                'taxonomy' => 'post_tag',
                'format'   => 'flat'
            ) );
        } else {
            echo '<p>No matching dream symbols found.</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Dream Symbols';
        $cat_ids = ! empty( $instance['filter_category_ids'] ) ? $instance['filter_category_ids'] : array();
        $filter_tags = ! empty( $instance['filter_tag_ids'] ) ? $instance['filter_tag_ids'] : array();
        $exclude_tag_ids = ! empty( $instance['exclude_tag_ids'] ) ? $instance['exclude_tag_ids'] : array();
        $auto_exclude = isset( $instance['auto_exclude_filter'] ) ? (bool) $instance['auto_exclude_filter'] : true;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label>Filter by Categories (Hold Ctrl to select multiple):</label><br>
            <select name="<?php echo $this->get_field_name( 'filter_category_ids' ); ?>[]" multiple class="widefat" style="height:100px;">
                <?php
                $categories = get_categories();
                foreach ( $categories as $cat ) {
                    echo '<option value="' . $cat->term_id . '" ' . ( in_array( $cat->term_id, $cat_ids ) ? 'selected' : '' ) . '>' . $cat->name . '</option>';
                }
                ?>
            </select>
        </p>

        <p>
            <label>Filter by Tags (Optional):</label><br>
            <select name="<?php echo $this->get_field_name( 'filter_tag_ids' ); ?>[]" multiple class="widefat" style="height:100px;">
                <?php
                $tags = get_tags();
                foreach ( $tags as $tag ) {
                    echo '<option value="' . $tag->term_id . '" ' . ( in_array( $tag->term_id, $filter_tags ) ? 'selected' : '' ) . '>' . $tag->name . '</option>';
                }
                ?>
            </select>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( $auto_exclude ); ?> id="<?php echo $this->get_field_id( 'auto_exclude_filter' ); ?>" name="<?php echo $this->get_field_name( 'auto_exclude_filter' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'auto_exclude_filter' ); ?>">Exclude Filtered Tags from Cloud</label>
        </p>

        <p>
            <label>Manual Tag Exclusions (Tag IDs, comma separated):</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'exclude_tag_ids_str' ); ?>" name="<?php echo $this->get_field_name( 'exclude_tag_ids_str' ); ?>" type="text" value="<?php echo implode(',', $exclude_tag_ids); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['filter_category_ids'] = ( ! empty( $new_instance['filter_category_ids'] ) ) ? array_map( 'intval', $new_instance['filter_category_ids'] ) : array();
        $instance['filter_tag_ids'] = ( ! empty( $new_instance['filter_tag_ids'] ) ) ? array_map( 'intval', $new_instance['filter_tag_ids'] ) : array();
        $instance['auto_exclude_filter'] = isset( $new_instance['auto_exclude_filter'] ) ? (bool) $new_instance['auto_exclude_filter'] : true;
        
        // Convert comma string to array
        if ( ! empty( $new_instance['exclude_tag_ids_str'] ) ) {
            $instance['exclude_tag_ids'] = array_map( 'intval', explode( ',', $new_instance['exclude_tag_ids_str'] ) );
        } else {
            $instance['exclude_tag_ids'] = array();
        }

        return $instance;
    }
}
