<?php
/**
 * Plugin Name: Dreamy Tags
 * Description: A specialized tag cloud generator designed for blogs, archives, and taxonomy-based layouts. Dreamy Tags allows you to filter displayed tags by category, exclude organizational tags, and control minimum usage thresholds for cleaner, more meaningful tag clouds.
 * Version: 1.0.47
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
        echo wp_kses_post( $args['before_widget'] );
        $this->actual_widget($instance);
        echo wp_kses_post( $args['after_widget'] );
    }

    private function get_int_array($instance, $name): array {
        return empty($instance[$name]) ? array() : array_map( 'intval', (array) $instance[$name] );
    }
    private function get_str_as_int_array($instance, $name): array {
        if ( empty( $instance[$name] ) ) return array();
        
        return array_values(
            array_filter(
                array_map(
                    'intval',
                    explode( ',', (string)$instance[$name] )
                ),
                static fn( $v ) => $v > 0
            )
        );
    }
    private function get_bool($instance, $name, $default_value): bool {
       return isset( $instance[ $name ] ) ? ! empty( $instance[ $name ] ) : (bool) $default_value;
    }
    private function get_int($instance, $name, $min, $default_value): int  {
        if ( ! isset( $instance[ $name ] ) ) {
            return (int) $default_value;
        }
        return max( (int) $min, (int) $instance[ $name ] );
    }
    private function actual_widget($instance) {

        $no_tags_found = 'No matching tags found.';
        $min_font = 8;
        $max_font = 22;

        $min_count = $this->get_int($instance, 'min_count', 1, 2);
        $filter_category_ids = $this->get_int_array($instance, 'filter_category_ids');
        $filter_tag_ids = $this->get_int_array($instance, 'filter_tag_ids');
        $exclude_tag_ids = $this->get_int_array($instance, 'exclude_tag_ids');
        $auto_exclude_filter = $this->get_bool($instance, 'auto_exclude_filter', true);
        if ( $auto_exclude_filter && ! empty( $filter_tag_ids ) ) {
            $exclude_tag_ids = array_unique( array_merge( $exclude_tag_ids, $filter_tag_ids ) );
        }
        $children = $this->get_bool($instance, 'children', true);

        // 1. Get all post IDs that match the filters
        $post_args = array(
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'post_type'      => 'post',
            'no_found_rows'  => true,
        );

        $tax_query = array();

        if ( ! empty( $filter_category_ids ) ) {
            $tax_query[] = array(
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => $filter_category_ids,
                'include_children' => $children,
                'operator'         => 'IN',
            );
        }

        if ( ! empty( $filter_tag_ids ) ) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'term_id',
                'terms'    => $filter_tag_ids,
                'operator' => 'IN',
            );
        }

        if ( count( $tax_query ) > 1 ) {
            array_unshift( $tax_query, array( 'relation' => 'AND' ) );
        }

        if ( ! empty( $tax_query ) ) {
            $post_args['tax_query'] = $tax_query;
        }

        $filtered_post_ids = get_posts( $post_args );

        if ( empty( $filtered_post_ids ) ) {
            $this->paragraph($no_tags_found);
            return;
        }

        // 2. Collect tags used by these specific posts (and apply min_count within this subset)
        $tag_counts = array();

        foreach ( $filtered_post_ids as $pid ) {
            $tag_ids = wp_get_post_terms( $pid, 'post_tag', array( 'fields' => 'ids' ) );
            if ( empty( $tag_ids ) || is_wp_error( $tag_ids ) ) {
                continue;
            }

            foreach ( $tag_ids as $tid ) {
                $tid = intval( $tid );
                if ( in_array( $tid, $exclude_tag_ids, true ) ) {
                    continue;
                }
                $tag_counts[ $tid ] = ( $tag_counts[ $tid ] ?? 0 ) + 1;
            }
        }

        $kept_tag_ids = array_keys(
            array_filter(
                $tag_counts,
                static function ( $c ) use ( $min_count ) {
                    return intval( $c ) >= $min_count;
                }
            )
        );

        if ( empty( $kept_tag_ids ) ) {
            $this->paragraph($no_tags_found);
            return;
        }

        $tags_in_use = get_terms( array(
            'taxonomy'   => 'post_tag',
            'include'    => $kept_tag_ids,
            'hide_empty' => false,
        ) );

        if ( empty( $tags_in_use ) || is_wp_error( $tags_in_use ) ) {
            $this->paragraph($no_tags_found);
            return;
        }

        $tag_ids_to_show = wp_list_pluck( $tags_in_use, 'term_id' );

        // Final Filter: Remove excluded IDs from our "To Show" list
        $final_tag_ids = array_diff( array_map( 'intval', $tag_ids_to_show ), $exclude_tag_ids );

        if ( ! empty( $final_tag_ids ) ) {
            $this->dreamy_tags_cloud( array(
                'include'  => $final_tag_ids,
                'taxonomy' => 'post_tag',
                'format'   => 'flat',
                'min_font' => $min_font,
                'max_font' => $max_font,
                'no_tags_found' => $no_tags_found,
                'tag_counts' => $tag_counts
            ) );
        } else {
            $this->paragraph($no_tags_found);
        }

    }
    private function paragraph($txt) {
        echo '<p>'.esc_html($txt).'</p>';
    }
    public function dreamy_tags_cloud($a) {
        $min_font = $a['min_font'];
        $max_font = $a['max_font'];
        $no_tags_found = $a['no_tags_found'];

        $subset_counts = array_intersect_key($a['tag_counts'], array_flip($a['include']));
        if (empty($subset_counts)) {
            $this->paragraph($no_tags_found);
            return;
        }

        $min_c = min($subset_counts);
        $max_c = max($subset_counts);

        // Avoid divide-by-zero when all counts are the same
        $spread = max(1, $max_c - $min_c);

        $terms = get_terms([
            'taxonomy' => $a['taxonomy'],
            'include' => $a['include'],
            'hide_empty' => false,
        ]);
        if (empty($terms) || is_wp_error($terms)) {
            $this->paragraph($no_tags_found);
            return;
        }

        // Sort by name so output is stable
        usort($terms, static function($x, $y) {
            return strcasecmp($x->name, $y->name);
        });

        echo '<div class="dreamy-tags-cloud">';
        foreach ($terms as $term) {

            $term_url   = get_term_link( $term );
            $term_url   = is_wp_error( $term_url ) ? '' : $term_url;

            $name = $term->name;

            $tid = (int) $term->term_id;
            $c = (int) ($subset_counts[$tid] ?? 0);
            if ($c <= 0) continue;

            // Linear scaling based on subset count
            $ratio = ($c - $min_c) / $spread; // 0..1
            $size = $min_font + ($ratio * ($max_font - $min_font));

            $tid_attr = (int) $tid;
            $size_attr = (float) $size;
            $count_attr = (int)$c;

            printf(
                '<a href="%s" class="dreamy-tags-link-%d" style="font-size: %.2fpt" aria-label="%s (%d)">%s</a> ',
                esc_url($term_url),
                $tid_attr,
                $size_attr,
                esc_attr($name),
                $count_attr,
                esc_html($name)
            );
        }
        echo '</div>';
    }

    public function form( $instance ) {
        $min_count     = $this->get_int($instance, 'min_count', 1, 2);
        $cat_ids = $this->get_int_array($instance, 'filter_category_ids');
        $filter_tags = $this->get_int_array($instance, 'filter_tag_ids');
        $exclude_tag_ids = $this->get_int_array($instance, 'exclude_tag_ids');
        $auto_exclude = $this->get_bool($instance, 'auto_exclude_filter', true);
        $children = $this->get_bool($instance, 'children', true);
        ?>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'min_count' ) ); ?>">Minimum posts per tag:</label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'min_count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'min_count' ) ); ?>"
                   type="number"
                   min="1"
                   step="1"
                   value="<?php echo esc_attr( $min_count ); ?>">
            <small>Only show tags that appear in at least this many matching posts.</small>
        </p>

        <p>
            <label>Filter by Categories (Hold Ctrl to select multiple):</label><br>
            <select name="<?php echo esc_attr( $this->get_field_name( 'filter_category_ids' ) ); ?>[]" multiple class="widefat" style="height:100px;">
                <?php
                $categories = get_categories( array( 'hide_empty' => false ) );
                foreach ( $categories as $cat ) {
                    printf(
                        '<option value="%d" %s>%s</option>',
                        intval( $cat->term_id ),
                        selected( in_array( $cat->term_id, $cat_ids, true ), true, false ),
                        esc_html( $cat->name )
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   <?php checked( $children ); ?>
                   id="<?php echo esc_attr( $this->get_field_id( 'children' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'children' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'children' ) ); ?>">Include posts in child categories</label>
        </p>

        <p>
            <label>Filter by Tags (Optional):</label><br>
            <select name="<?php echo esc_attr( $this->get_field_name( 'filter_tag_ids' ) ); ?>[]" multiple class="widefat" style="height:100px;">
                <?php
                $tags = get_tags( array( 'hide_empty' => false ) );
                foreach ( $tags as $tag ) {
                    printf(
                        '<option value="%d" %s>%s</option>',
                        intval( $tag->term_id ),
                        selected( in_array( $tag->term_id, $filter_tags, true ), true, false ),
                        esc_html( $tag->name )
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <input class="checkbox"
                   type="checkbox"
                   <?php checked( $auto_exclude ); ?>
                   id="<?php echo esc_attr( $this->get_field_id( 'auto_exclude_filter' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'auto_exclude_filter' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'auto_exclude_filter' ) ); ?>">Exclude Filtered Tags from Cloud</label>
        </p>

        <p>
            <label>Manual Tag Exclusions (Tag IDs, comma separated):</label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'exclude_tag_ids_str' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'exclude_tag_ids_str' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( implode( ',', array_map( 'intval', $exclude_tag_ids ) ) ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        
        $instance['min_count'] = $this->get_int($new_instance, 'min_count', 1, 2);
        $instance['filter_category_ids'] =  $this->get_int_array($new_instance, 'filter_category_ids');
        $instance['children'] = $this->get_bool($new_instance, 'children', true);
        $instance['filter_tag_ids'] = $this->get_int_array($new_instance, 'filter_tag_ids');
        $instance['auto_exclude_filter'] = $this->get_bool($new_instance, 'auto_exclude_filter', true);
        $instance['exclude_tag_ids'] =  $this->get_str_as_int_array($new_instance, 'exclude_tag_ids_str');

        return $instance;
    }
}
