<?php
/**
 * Shortcode: [solawi_map]
 * Zeigt eine Leaflet Karte mit allen Depots an.
 * Fügt diesen Code in die functions.php ein.
 */

function solawi_map_shortcode_render() {
    // 1. Enqueue necessary assets only when the shortcode is used.
    // We are now loading Leaflet from a local directory to avoid external requests.
    $theme_dir = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'leaflet-local-css',
        $theme_dir . '/assets/leaflet/leaflet.css',
        array(),
        '1.9.4'
    );
    wp_enqueue_script(
        'leaflet-local-js',
        $theme_dir . '/assets/leaflet/leaflet.js',
        array(),
        '1.9.4',
        true
    );

    // Custom assets for the map
    $theme = wp_get_theme();
    wp_enqueue_script( 'solawi-map-script', get_stylesheet_directory_uri() . '/js/solawi-map.js', array('leaflet-local-js'), $theme->get('Version'), true );

    // 2. Collect data from WordPress
    $map_data = array();

    // A. DEPOT PINS (Verteilpunkte)
    $depots_args = array(
        'post_type'      => 'depot',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );

    if ( is_singular('solawi') ) {
        $connected_depot_ids = get_field('belieferte_depots', get_the_ID());
        if ( ! empty($connected_depot_ids) ) {
            $depots_args['post__in'] = $connected_depot_ids;
            $depots_args['orderby'] = 'post__in'; 
        } else {
            $depots_args['post__in'] = array(0); 
        }
    }

    $depots_query = new WP_Query( $depots_args );

    if ( $depots_query->have_posts() ) {
        while ( $depots_query->have_posts() ) {
            $depots_query->the_post();
            $depot_id = get_the_ID();

            $lat = get_field( 'geo_lat', $depot_id );
            $lng = get_field( 'geo_lng', $depot_id );
            $zeiten = get_field( 'anlieferzeit', $depot_id );

            if ( ! $lat || ! $lng ) continue;

            $connected_solawis = get_posts( array(
                'post_type'  => 'solawi',
                'meta_query' => array(
                    array(
                        'key'     => 'belieferte_depots',
                        'value'   => '"' . $depot_id . '"',
                        'compare' => 'LIKE'
                    )
                )
            ));

            $solawi_list = array();
            $status_levels = ['none' => 0, 'voll' => 1, 'warteliste' => 2, 'offen' => 3, 'laden' => 4, 'kiste' => 5];
            $highest_status_level = 0;
            $solawi_colors = [];

            foreach ( $connected_solawis as $solawi ) {
                $terms = get_the_terms($solawi->ID, 'produktkategorie');
                $term_ids = [];
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $term_ids[] = $term->term_id;
                    }
                }

                $solawi_list[] = array(
                    'name' => $solawi->post_title,
                    'id'   => $solawi->ID,
                    'url'  => get_permalink( $solawi->ID ),
                    'logo' => get_the_post_thumbnail_url( $solawi->ID, 'thumbnail' ),
                    'cats' => $term_ids
                );

                $color = get_field('solawi_farbe', $solawi->ID);
                if ($color) $solawi_colors[] = $color;

                $solawi_status = get_field('status_mitgliedschaft', $solawi->ID);
                if (isset($status_levels[$solawi_status]) && $status_levels[$solawi_status] > $highest_status_level) {
                    $highest_status_level = $status_levels[$solawi_status];
                }
            }

            $final_pin_color = null;
            if (count($solawi_colors) === 1) {
                $final_pin_color = $solawi_colors[0];
            } elseif (count(array_unique($solawi_colors)) === 1 && !empty($solawi_colors)) {
                $final_pin_color = $solawi_colors[0];
            }

            $depot_status_class = 'pin-default';
            if ($highest_status_level === $status_levels['offen']) $depot_status_class = 'pin-offen';
            elseif ($highest_status_level === $status_levels['warteliste']) $depot_status_class = 'pin-warteliste';
            elseif ($highest_status_level === $status_levels['voll']) $depot_status_class = 'pin-voll';
            elseif ($highest_status_level === $status_levels['laden']) $depot_status_class = 'pin-laden';
            elseif ($highest_status_level === $status_levels['kiste']) $depot_status_class = 'pin-kiste';

            $map_data[] = array(
                'id'      => $depot_id,
                'name'    => get_the_title(),
                'lat'     => $lat,
                'lng'     => $lng,
                'type'    => 'depot',
                'zeiten'  => $zeiten,
                'solawis' => $solawi_list,
                'status'  => $depot_status_class,
                'color'   => $final_pin_color
            );
        }
        wp_reset_postdata();
    }

    // B. SOLAWI PINS (Hofstellen, Läden, Kisten)
    $solawis_args = array(
        'post_type'      => 'solawi',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );

    // Auf Einzelseite nur diese eine Solawi
    if ( is_singular('solawi') ) {
        $solawis_args['p'] = get_the_ID();
    }

    $solawis_query = new WP_Query( $solawis_args );

    if ( $solawis_query->have_posts() ) {
        while ( $solawis_query->have_posts() ) {
            $solawis_query->the_post();
            $solawi_id = get_the_ID();

            $lat = get_field( 'geo_lat', $solawi_id );
            $lng = get_field( 'geo_lng', $solawi_id );
            
            if ( ! $lat || ! $lng ) continue;

            $status_key = get_field('status_mitgliedschaft', $solawi_id);
            $type = 'hof';
            if ($status_key === 'laden') $type = 'laden';
            if ($status_key === 'kiste') $type = 'kiste';

            $color = get_field('solawi_farbe', $solawi_id);
            
            $terms = get_the_terms($solawi_id, 'produktkategorie');
            $term_ids = [];
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $term_ids[] = $term->term_id;
                }
            }

            $map_data[] = array(
                'id'      => 'solawi-' . $solawi_id,
                'name'    => get_the_title() . ' (' . ($type === 'hof' ? 'Solawi' : ($type === 'laden' ? 'Bioladen' : 'Biokiste')) . ')',
                'lat'     => $lat,
                'lng'     => $lng,
                'type'    => $type,
                'solawis' => array(array(
                    'name' => get_the_title(),
                    'id'   => $solawi_id,
                    'url'  => get_permalink(),
                    'logo' => get_the_post_thumbnail_url( $solawi_id, 'thumbnail' ),
                    'cats' => $term_ids
                )),
                'status'  => 'pin-' . $status_key,
                'color'   => $color ?: '#3A6B46'
            );
        }
        wp_reset_postdata();
    }

    // 3. Pass PHP data to the JavaScript file
    $localized_data = array(
        'locations' => $map_data,
        'mapCenter' => array(47.99, 7.84), // Zentrum auf Freiburg
        'tileUrl'   => $theme_dir . '/assets/tiles/{z}/{x}/{y}.png'
    );
    wp_localize_script( 'solawi-map-script', 'solawiMapData', $localized_data );

    // 4. Return only the HTML container for the map
    return '<div id="solawi-map" style="height: 500px; width: 100%; z-index: 1;" role="region" aria-label="Interaktive Karte der Solawi Verteilpunkte und Höfe in Freiburg"></div>';
}

add_shortcode( 'solawi_map', 'solawi_map_shortcode_render' );
?>