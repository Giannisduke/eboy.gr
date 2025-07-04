<?php

class FacetWP_Facet_Checkboxes extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Checkboxes', 'fwp' );
        $this->fields = [ 'parent_term', 'modifiers', 'hierarchical', 'show_expanded',
            'ghosts', 'operator', 'orderby', 'count', 'soft_limit' ];
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $from_clause = $wpdb->prefix . 'facetwp_index f';
        $where_clause = $params['where_clause'];

        // Orderby
        $orderby = $this->get_orderby( $facet );

        // Limit
        $limit = $this->get_limit( $facet );

        // Use "OR" mode when necessary
        $is_single = FWP()->helper->facet_is( $facet, 'multiple', 'no' );
        $using_or = FWP()->helper->facet_is( $facet, 'operator', 'or' );

        // Facet in "OR" mode
        if ( $is_single || $using_or ) {
            $where_clause = $this->get_where_clause( $facet );
        }

        $orderby = apply_filters( 'facetwp_facet_orderby', $orderby, $facet );
        $from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
        $where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

        $sql = "
        SELECT f.facet_value, f.facet_display_value, f.term_id, f.parent_id, f.depth, COUNT(DISTINCT f.post_id) AS counter
        FROM $from_clause
        WHERE f.facet_name = '{$facet['name']}' $where_clause
        GROUP BY f.facet_value
        ORDER BY $orderby
        LIMIT $limit";

        $output = $wpdb->get_results( $sql, ARRAY_A );

        // Show "ghost" facet choices
        // For performance gains, only run if facets are in use
        $show_ghosts = FWP()->helper->facet_is( $facet, 'ghosts', 'yes' );

        if ( $show_ghosts && FWP()->is_filtered ) {
            $raw_post_ids = implode( ',', FWP()->unfiltered_post_ids );

            $sql = "
            SELECT f.facet_value, f.facet_display_value, f.term_id, f.parent_id, f.depth, 0 AS counter
            FROM $from_clause
            WHERE f.facet_name = '{$facet['name']}' AND post_id IN ($raw_post_ids)
            GROUP BY f.facet_value
            ORDER BY $orderby
            LIMIT $limit";

            $ghost_output = $wpdb->get_results( $sql, ARRAY_A );
            $tmp = [];

            $preserve_ghosts = FWP()->helper->facet_is( $facet, 'preserve_ghosts', 'yes' );
            $orderby_count = FWP()->helper->facet_is( $facet, 'orderby', 'count' );

            // Keep the facet placement intact
            if ( $preserve_ghosts && ! $orderby_count ) {
                foreach ( $ghost_output as $row ) {
                    $tmp[ $row['facet_value'] . ' ' ] = $row;
                }

                foreach ( $output as $row ) {
                    $tmp[ $row['facet_value'] . ' ' ] = $row;
                }

                $output = $tmp;
            }
            else {
                // Make the array key equal to the facet_value (for easy lookup)
                foreach ( $output as $row ) {
                    $tmp[ $row['facet_value'] . ' ' ] = $row; // Force a string array key
                }

                $output = $tmp;

                foreach ( $ghost_output as $row ) {
                    $facet_value = $row['facet_value'];
                    if ( ! isset( $output[ "$facet_value " ] ) ) {
                        $output[ "$facet_value " ] = $row;
                    }
                }
            }

            $output = array_splice( $output, 0, $limit );
            $output = array_values( $output );
        }

        return $output;
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        $facet = $params['facet'];

        if ( FWP()->helper->facet_is( $facet, 'hierarchical', 'yes' ) ) {
            return $this->render_hierarchy( $params );
        }

        $output = '';
        $values = (array) $params['values'];
        $soft_limit = empty( $facet['soft_limit'] ) ? 0 : (int) $facet['soft_limit'];

        $key = 0;
        foreach ( $values as $key => $row ) {
            if ( 0 < $soft_limit && $key == $soft_limit ) {
                $output .= '<div class="facetwp-overflow facetwp-hidden">';
            }
            $output .= $this->render_choice( $row, $params );
        }

        if ( 0 < $soft_limit && $soft_limit <= $key ) {
            $output .= '</div>';
            $output .= '<a class="facetwp-toggle">' . facetwp_i18n( __( 'See {num} more', 'fwp-front' ) ) . '</a>';
            $output .= '<a class="facetwp-toggle facetwp-hidden">' . facetwp_i18n( __( 'See less', 'fwp-front' ) ) . '</a>';
        }

        return $output;
    }


    /**
     * Generate the facet HTML (hierarchical taxonomies)
     */
    function render_hierarchy( $params ) {

        $output = '';
        $facet = $params['facet'];
        $values = FWP()->helper->sort_taxonomy_values( $params['values'], $facet['orderby'] );

        $init_depth = -1;
        $last_depth = -1;

        foreach ( $values as $row ) {
            $depth = (int) $row['depth'];

            if ( -1 == $last_depth ) {
                $init_depth = $depth;
            }
            elseif ( $depth > $last_depth ) {
                $output .= '<div class="facetwp-depth">';
            }
            elseif ( $depth < $last_depth ) {
                for ( $i = $last_depth; $i > $depth; $i-- ) {
                    $output .= '</div>';
                }
            }

            $output .= $this->render_choice( $row, $params );

            $last_depth = $depth;
        }

        for ( $i = $last_depth; $i > $init_depth; $i-- ) {
            $output .= '</div>';
        }

        return $output;
    }


    /**
     * Render a single facet choice
     */
    function render_choice( $row, $params ) {
        $label = esc_html( $row['facet_display_value'] );

        $output = '';
        $selected_values = (array) $params['selected_values'];
        $selected = in_array( $row['facet_value'], $selected_values ) ? ' checked' : '';
        $selected .= ( '' != $row['counter'] && 0 == $row['counter'] && '' == $selected ) ? ' disabled' : '';
        $output .= '<div class="facetwp-checkbox test' . $selected . '" data-value="' . esc_attr( $row['facet_value'] ) . '">';
        $output .= '<span class="facetwp-display-value">';
        $output .= apply_filters( 'facetwp_facet_display_value', $label, [
            'selected' => ( '' !== $selected ),
            'facet' => $params['facet'],
            'row' => $row
        ]);
        $output .= '</span>';
        $output .= '<span class="facetwp-counter">(' . $row['counter'] . ')</span>';
        $output .= '</div>';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $output = [];
        $facet = $params['facet'];
        $selected_values = $params['selected_values'];

        $sql = $wpdb->prepare( "SELECT DISTINCT post_id
            FROM {$wpdb->prefix}facetwp_index
            WHERE facet_name = %s",
            $facet['name']
        );

        // Match ALL values
        if ( 'and' == $facet['operator'] ) {
            foreach ( $selected_values as $key => $value ) {
                $results = facetwp_sql( $sql . " AND facet_value IN ('$value')", $facet );
                $output = ( $key > 0 ) ? array_intersect( $output, $results ) : $results;

                if ( empty( $output ) ) {
                    break;
                }
            }
        }
        // Match ANY value
        else {
            $selected_values = implode( "','", $selected_values );
            $output = facetwp_sql( $sql . " AND facet_value IN ('$selected_values')", $facet );
        }

        return $output;
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->json['expand'] = '[+]';
        FWP()->display->json['collapse'] = '[-]';
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        $expand = empty( $params['facet']['show_expanded'] ) ? 'no' : $params['facet']['show_expanded'];
        return [ 'show_expanded' => $expand ];
    }
}
