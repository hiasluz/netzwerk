<?php
/**
 * Automatisches Event-Schema (Solawi)
 * Features: Offline/Online, SubEvents, Organizer, Kosten
 */
function add_dynamic_event_schema_json() {
 
    // Dieses Skript generiert eine Liste aller Events für Suchmaschinen.
    // Es soll nur auf der Haupt-Übersichtsseite für Veranstaltungen laufen.
    // Wir prüfen daher, ob es die manuelle Seite 'veranstaltungen' ODER die
    // automatische Archiv-Seite des Post-Types 'veranstaltung' ist.
    if ( ! is_page('veranstaltungen') && ! is_post_type_archive('veranstaltung') ) {
        return;
    }

    $args = array(
        'post_type'      => 'veranstaltung',
        'posts_per_page' => -1,
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => 'event_date',
                'value'   => date('Ymd'),
                'compare' => '>=', 
                'type'    => 'NUMERIC'
            )
        )
    );

    $events_query = new WP_Query( $args );
    $schema_events = array();

    // Zeit-Helper (Außerhalb der Schleife definiert)
    $build_iso_time_v3 = function($date_ymd, $time_str) {
        if (!$date_ymd) return '';
        $d = DateTime::createFromFormat('Ymd', $date_ymd);
        if (!$d) return '';
        $clean_time = $time_str ? preg_replace('/[^0-9:]/', '', $time_str) : '';
        return $d->format('Y-m-d') . ($clean_time ? 'T' . $clean_time : '');
    };

    if ( $events_query->have_posts() ) {
        while ( $events_query->have_posts() ) {
            $events_query->the_post();
            $post_id = get_the_ID();

            // --- FELDER AUSLESEN ---
            $raw_date    = get_field('event_date', $post_id, false);
            $event_start = get_field('event_start', $post_id);
            $event_end   = get_field('event_end', $post_id);
            
            // Tag 2
            $raw_date_2    = get_field('event_date_2', $post_id, false);
            $event_start_2 = get_field('event_start_2', $post_id);
            $event_end_2   = get_field('event_end_2', $post_id);

            // Modus (Neu)
            $modus         = get_field('event_modus', $post_id); // 'offline' oder 'online'
            $online_link   = get_field('event_online_link', $post_id);

            // Adresse
            $strasse       = get_field('strasse', $post_id);
            $plz           = get_field('plz', $post_id);
            $stadt         = get_field('stadt', $post_id);
            
            // Meta
            $organizer_acf = get_field('event_organizer', $post_id);
            $kosten_acf    = get_field('kosten', $post_id);
            $valid_from    = get_the_date('c', $post_id);
            $description   = get_the_excerpt();
            $image_url     = get_the_post_thumbnail_url($post_id, 'large');

            // --- LOGIK: ONLINE ODER OFFLINE? ---
            
            $attendance_mode = 'https://schema.org/OfflineEventAttendanceMode'; // Default
            $location_schema = array(); // Leer initialisieren

            if ($modus === 'online') {
                // ONLINE SETUP
                $attendance_mode = 'https://schema.org/OnlineEventAttendanceMode';
                $location_schema = array(
                    '@type' => 'VirtualLocation',
                    'url'   => $online_link ? $online_link : get_permalink() // Fallback auf Beitrags-URL
                );
            } else {
                // OFFLINE SETUP (Default)
                $location_schema = array(
                    '@type' => 'Place',
                    'name'  => $stadt ? $stadt : 'Veranstaltungsort',
                    'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $strasse ?: '',
                        'addressLocality' => $stadt ?: 'Freiburg',
                        'postalCode' => $plz ?: '',
                        'addressCountry' => 'DE'
                    )
                );
            }

            // --- RESTLICHE STRUKTUREN ---
            $organizer_schema = array(
                '@type' => 'Organization',
                'name'  => $organizer_acf ? $organizer_acf : 'Ernährungsrat Freiburg & Region',
                'url'   => home_url()
            );

            $offers_schema = array(
                '@type'         => 'Offer',
                'price'         => ($kosten_acf !== '' && $kosten_acf !== null) ? $kosten_acf : '0',
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'validFrom'     => $valid_from,
                'url'           => get_permalink()
            );

            $iso_start_1 = $build_iso_time_v3($raw_date, $event_start);
            $iso_end_1   = $build_iso_time_v3($raw_date, $event_end);
            
            $has_second_day = ($raw_date_2 && $event_start_2);
            $iso_start_2 = $has_second_day ? $build_iso_time_v3($raw_date_2, $event_start_2) : '';
            $iso_end_2   = $has_second_day ? $build_iso_time_v3($raw_date_2, $event_end_2) : '';

            $main_end = $iso_end_2 ? $iso_end_2 : $iso_end_1;

            // --- HAUPT EVENT ---
            $event_data = array(
                '@context' => 'https://schema.org',
                '@type'    => 'Event',
                'name'     => get_the_title(),
                'startDate'=> $iso_start_1,
                'endDate'  => $main_end,
                'eventAttendanceMode' => $attendance_mode, // <--- DYNAMISCH
                'eventStatus' => 'https://schema.org/EventScheduled',
                'location'    => $location_schema,         // <--- DYNAMISCH
                'description' => $description,
                'url'         => get_permalink(),
                'organizer'   => $organizer_schema,
                'offers'      => $offers_schema
            );

            if ($image_url) $event_data['image'] = $image_url;

            // --- SUB EVENTS ---
            if ($has_second_day) {
                $sub_template = array(
                    '@type' => 'Event',
                    'location' => $location_schema, // Erbt Virtual oder Place
                    'organizer' => $organizer_schema,
                    'offers' => $offers_schema,
                    'description' => $description,
                    'eventAttendanceMode' => $attendance_mode, // Auch SubEvents sind dann online
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'url' => get_permalink()
                );
                if ($image_url) $sub_template['image'] = $image_url;

                $day1 = $sub_template;
                $day1['name'] = get_the_title() . ' - Tag 1';
                $day1['startDate'] = $iso_start_1;
                $day1['endDate'] = $iso_end_1;

                $day2 = $sub_template;
                $day2['name'] = get_the_title() . ' - Tag 2';
                $day2['startDate'] = $iso_start_2;
                $day2['endDate'] = $iso_end_2;

                $event_data['subEvent'] = array($day1, $day2);
            }

            $schema_events[] = $event_data;
        }
        wp_reset_postdata();
    }

    if ( ! empty( $schema_events ) ) {
        echo '<script type="application/ld+json">' . json_encode( $schema_events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
    }
}
add_action( 'wp_footer', 'add_dynamic_event_schema_json' );