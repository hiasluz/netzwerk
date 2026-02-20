<?php
/**
 * Shortcode: [solawi_events]
 * Zeigt eine Liste der nächsten Veranstaltungen an.
 * Inklusive Unterstützung für 2-Tages-Events.
 */
function solawi_events_shortcode_render( $atts ) {
    
    // Aktuelles Datum im Format Ymd
    $today = date('Ymd');

    // Query args
    $args = array(
        'post_type'      => 'veranstaltung',
        'posts_per_page' => -1,
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=', 
                'type'    => 'NUMERIC'
            )
        )
    );

    $query = new WP_Query($args);

    ob_start();
    ?>
    <div class="solawi-events-container">
        <?php 
        if ( $query->have_posts() ): 
            // Helper Funktion für saubere Zeit (entfernt :00)
            // Ausserhalb der Schleife definieren für bessere Performance.
            $format_time = function($t) {
                return str_replace(':00', '', $t);
            };
        ?>
            <div class="solawi-events-grid">
                <?php while ( $query->have_posts() ): $query->the_post(); 
                    $post_id = get_the_ID();
                    
                    // --- Tag 1 Felder ---
                    $date_raw    = get_field('event_date', $post_id, false);
                    $event_start = get_field('event_start');
                    $event_end   = get_field('event_end');
                    
                    // --- Tag 2 Felder (NEU) ---
                    $date_raw_2    = get_field('event_date_2', $post_id, false); // Datum Tag 2
                    $event_start_2 = get_field('event_start_2');
                    $event_end_2   = get_field('event_end_2');

                    // --- Tag 3 Felder (NEU) ---
                    $date_raw_3    = get_field('event_date_3', $post_id, false);
                    $event_start_3 = get_field('event_start_3');
                    $event_end_3   = get_field('event_end_3');

                    // --- Location & Bild ---
                    $strasse = get_field('strasse');
                    $plz     = get_field('plz');
                    $stadt   = get_field('stadt');
                    $link    = get_field('event_link');
                    $img_url = get_the_post_thumbnail_url($post_id, 'medium');
                    
                    // Datumsformatierung Tag 1
                    $date_obj = DateTime::createFromFormat('Ymd', $date_raw);
                    if (!$date_obj && $date_raw) { $date_obj = new DateTime($date_raw); }
                    
                    $day   = $date_obj ? $date_obj->format('d') : '';
                    $month = $date_obj ? date_i18n('M', $date_obj->getTimestamp()) : '';
                    
                    // Datumsformatierung Tag 2 (für die Anzeige unten)
                    $date_obj_2 = false;
                    if ($date_raw_2) {
                        $date_obj_2 = DateTime::createFromFormat('Ymd', $date_raw_2);
                        if (!$date_obj_2) { $date_obj_2 = new DateTime($date_raw_2); }
                    }

                    // Datumsformatierung Tag 3 (für die Anzeige unten)
                    $date_obj_3 = false;
                    if ($date_raw_3) {
                        $date_obj_3 = DateTime::createFromFormat('Ymd', $date_raw_3);
                        if (!$date_obj_3) { $date_obj_3 = new DateTime($date_raw_3); }
                    }

                    if(!$img_url) {
                        $img_url = 'https://via.placeholder.com/400x300?text=Event'; 
                    }

                ?>
                    <article class="solawi-event-card">
                        <div class="solawi-event-image" style="background-image: url('<?php echo esc_url($img_url); ?>');">
                            <div class="solawi-event-date-badge">
                                <span class="day"><?php echo $day; ?></span>
                                <span class="month"><?php echo $month; ?></span>
                            </div>
                        </div>
                        <div class="solawi-event-content">
                            <h3 class="solawi-event-title"><?php the_title(); ?></h3>
                            <div class="solawi-event-meta">
                                
                                <?php if($event_start): 
                                    $display_time = $format_time($event_start);
                                    if ($event_end) {
                                        $display_time .= ' - ' . $format_time($event_end);
                                    }
                                ?>
                                    <span class="meta-item">
                                        <i class="dashicons dashicons-clock"></i> 
                                        <?php echo esc_html($display_time); ?> Uhr
                                    </span>
                                <?php endif; ?>

                                <?php if($date_obj_2 && $event_start_2): 
                                     $day_2_label = date_i18n('D, d.m.', $date_obj_2->getTimestamp()); // z.B. Sa, 25.01.
                                     $time_2 = $format_time($event_start_2);
                                     if ($event_end_2) {
                                         $time_2 .= ' - ' . $format_time($event_end_2);
                                     }
                                ?>
                                    <span class="meta-item" style="color: #666; font-size: 0.9em; display:block; margin-top:4px;">
                                        <i class="dashicons dashicons-calendar-alt"></i> 
                                        + <?php echo esc_html($day_2_label); ?>: <?php echo esc_html($time_2); ?> Uhr
                                    </span>
                                <?php endif; ?>

                                <?php if($date_obj_3 && $event_start_3): 
                                     $day_3_label = date_i18n('D, d.m.', $date_obj_3->getTimestamp()); 
                                     $time_3 = $format_time($event_start_3);
                                     if ($event_end_3) {
                                         $time_3 .= ' - ' . $format_time($event_end_3);
                                     }
                                ?>
                                    <span class="meta-item" style="color: #666; font-size: 0.9em; display:block; margin-top:4px;">
                                        <i class="dashicons dashicons-calendar-alt"></i> 
                                        + <?php echo esc_html($day_3_label); ?>: <?php echo esc_html($time_3); ?> Uhr
                                    </span>
                                <?php endif; ?>

                                <?php 
                                    $modus = get_field('event_modus', $post_id); 
                                ?>

                                <?php if($modus == 'online'): ?>
                                    <span class="meta-item">
                                        <i class="dashicons dashicons-video-alt3"></i> Online Event
                                    </span>
                                <?php elseif($stadt): ?>
                                    <span class="meta-item">
                                        <i class="dashicons dashicons-location"></i> 
                                        <?php echo esc_html($stadt); ?><?php echo $strasse ? ', ' . esc_html($strasse) : ''; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="solawi-event-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            <div class="solawi-event-buttons">
                                <a href="<?php the_permalink(); ?>" class="solawi-btn-small">Mehr Infos &rarr;</a>
                                
                                <?php if($link): ?>
                                    <a href="<?php echo esc_url($link); ?>" class="solawi-btn-small solawi-btn-secondary" target="_blank" rel="noopener">Zum Veranstalter</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Aktuell stehen keine Veranstaltungen an.</p>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('solawi_events', 'solawi_events_shortcode_render');


/**
 * Shortcode: [solawi_event_meta]
 * Zeigt Datum, Zeit und Ort für eine einzelne Veranstaltung an.
 * Jetzt auch mit Unterstützung für Tag 2.
 */
function solawi_event_meta_shortcode_render() {
    if ( ! is_singular('veranstaltung') ) return '';

    $post_id = get_the_ID();
    
    // Tag 1
    $date_raw    = get_field('event_date', $post_id, false);
    $event_start = get_field('event_start', $post_id);
    $event_end   = get_field('event_end', $post_id);
    
    // Tag 2
    $date_raw_2    = get_field('event_date_2', $post_id, false);
    $event_start_2 = get_field('event_start_2', $post_id);
    $event_end_2   = get_field('event_end_2', $post_id);

    // Tag 3
    $date_raw_3    = get_field('event_date_3', $post_id, false);
    $event_start_3 = get_field('event_start_3', $post_id);
    $event_end_3   = get_field('event_end_3', $post_id);
    
    // Ort
    $strasse = get_field('strasse', $post_id);
    $stadt   = get_field('stadt', $post_id);

    // Helper Time
    $format_time = function($t) { return str_replace(':00', '', $t); };

    // --- AUFBAU STRING TAG 1 ---
    $string_day_1 = '';
    
    if ($date_raw) {
        $date_obj = DateTime::createFromFormat('Ymd', $date_raw);
        $string_day_1 .= $date_obj ? $date_obj->format('d.m.Y') : '';
    }
    
    if ($event_start) {
        $string_day_1 .= ', ' . $format_time($event_start);
        if ($event_end) {
            $string_day_1 .= '-' . $format_time($event_end);
        }
        $string_day_1 .= ' Uhr';
    }

    // --- AUFBAU STRING TAG 2 ---
    $string_day_2 = '';
    if ($date_raw_2 && $event_start_2) {
        $date_obj_2 = DateTime::createFromFormat('Ymd', $date_raw_2);
        
        $string_day_2 .= ' & '; // Trenner
        $string_day_2 .= $date_obj_2 ? $date_obj_2->format('d.m.') : ''; // Datum Tag 2 (ohne Jahr reicht meist)
        $string_day_2 .= ' ' . $format_time($event_start_2);
        
        if ($event_end_2) {
            $string_day_2 .= '-' . $format_time($event_end_2);
        }
        $string_day_2 .= ' Uhr';
    }

    // --- AUFBAU STRING TAG 3 ---
    $string_day_3 = '';
    if ($date_raw_3 && $event_start_3) {
        $date_obj_3 = DateTime::createFromFormat('Ymd', $date_raw_3);
        
        $string_day_3 .= ' & '; // Trenner
        $string_day_3 .= $date_obj_3 ? $date_obj_3->format('d.m.') : ''; 
        $string_day_3 .= ' ' . $format_time($event_start_3);
        
        if ($event_end_3) {
            $string_day_3 .= '-' . $format_time($event_end_3);
        }
        $string_day_3 .= ' Uhr';
    }

    // --- ORT ---
    $modus = get_field('event_modus', $post_id);
    $location_display = '';

    if ($modus === 'online') {
        $location_display = 'Online Event';
    } elseif ($stadt) {
        $location_display = esc_html($stadt);
        if ($strasse) {
            $location_display .= ', ' . esc_html($strasse);
        }
    }

    // Build output with icons
    $html = '<div class="solawi-event-meta">';
    
    $full_date_string = $string_day_1 . $string_day_2 . $string_day_3;

    if ($full_date_string) {
        $html .= '<span class="meta-item"><i class="dashicons dashicons-calendar-alt"></i> ' . esc_html($full_date_string) . '</span>';
    }
    
    if ($location_display) {
        $html .= '<span class="meta-item"><i class="dashicons dashicons-location"></i> ' . esc_html($location_display) . '</span>';
    }
    
    $html .= '</div>';

    return $html;
}
add_shortcode('solawi_event_meta', 'solawi_event_meta_shortcode_render');