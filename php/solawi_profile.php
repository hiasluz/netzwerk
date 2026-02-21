<?php
/**
 * Funktionen für die Einzelansicht einer Solawi (single-solawi.php).
 * - Shortcode: [solawi_profile_card]
 * - Shortcode: [solawi_single_depots_list]
 * - Globale CSS-Variable für Solawi-Farbe
 * Nur auf Solawi-Einzelseiten sinnvoll.
 */

function solawi_profile_card_shortcode_render() {
    if ( ! is_singular('solawi') ) return '';

    $post_id = get_the_ID();
    
    // Daten abrufen
    $status_key = get_field('status_mitgliedschaft', $post_id);
    $m_aktuell  = get_field('mitglieder_aktuell', $post_id);
    $m_max      = get_field('mitglieder_max', $post_id);
    $jahr       = get_field('gruendungsjahr', $post_id);
    $adresse    = get_field('betriebsstatte_adresse', $post_id);
    $mail       = get_field('mail', $post_id);
    $telefon    = get_field('telefon', $post_id);
    $homepage   = get_field('homepage', $post_id);
    $social_media = get_field('social_media', $post_id);
    
    // Status Label & Farbe
    $status_label = '';
    switch ($status_key) {
        case 'offen': $status_label = 'Plätze frei'; break;
        case 'warteliste': $status_label = 'Warteliste'; break;
        case 'voll': $status_label = 'Voll belegt'; break;
        case 'laden': $status_label = 'Bioladen'; break;
        case 'kiste': $status_label = 'Biokiste'; break;
    }

    ob_start();
    ?>
    <div class="solawi-profile-card">
        <div class="profile-card-header">
            <span class="profile-card-badge status-<?php echo esc_attr($status_key); ?>">
                <?php echo esc_html($status_label); ?>
            </span>
        </div>
        
        <div class="profile-stats-grid">
            
            <?php if($m_aktuell || $m_max): ?>
            <div class="stat-item stat-members">
                <div class="stat-label">Mitglieder</div>
                <div class="stat-value">
                    <?php 
                        if ($m_max == '-1') {
                            if($m_aktuell) {
                                echo esc_html($m_aktuell) . ' <span class="sep">/</span> unbegrenzt';
                            } else {
                                echo 'unbegrenzt';
                            }
                        }
                        elseif($m_aktuell && $m_max) echo esc_html($m_aktuell) . ' <span class="sep">/</span> ' . esc_html($m_max);
                        elseif($m_aktuell) echo esc_html($m_aktuell);
                        elseif($m_max) echo esc_html($m_max) . ' (Max)';
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($jahr): ?>
            <div class="stat-item stat-year">
                <div class="stat-label">Gründung</div>
                <div class="stat-value"><?php echo esc_html($jahr); ?></div>
            </div>
            <?php endif; ?>

            <?php if($adresse): ?>
            <div class="stat-item stat-address">
                <div class="stat-label">Adresse</div>
                <div class="stat-value"><?php echo nl2br(esc_html($adresse)); ?></div>
            </div>
            <?php endif; ?>

            <?php if($mail): ?>
            <div class="stat-item stat-mail">
                <div class="stat-label">E-Mail</div>
                <div class="stat-value"><a href="mailto:<?php echo antispambot($mail); ?>"><?php echo antispambot($mail); ?></a></div>
            </div>
            <?php endif; ?>

            <?php if($telefon): ?>
            <div class="stat-item stat-phone">
                <div class="stat-label">Telefon</div>
                <div class="stat-value"><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $telefon); ?>"><?php echo esc_html($telefon); ?></a></div>
            </div>
            <?php endif; ?>

            <?php if($homepage): ?>
            <div class="stat-item stat-web">
                <div class="stat-label">Webseite</div>
                <div class="stat-value">
                    <?php 
                        $display_url = str_replace(array('https://', 'http://', 'www.'), '', $homepage);
                        $display_url = rtrim($display_url, '/');
                    ?>
                    <a href="<?php echo esc_url($homepage); ?>" target="_blank"><?php echo esc_html($display_url); ?></a>
                </div>
            </div>
            <?php endif; ?>

            <?php 
            // Filter out empty values from the social media array
            $active_socials = is_array($social_media) ? array_filter($social_media) : [];
            if( !empty($active_socials) ): 
            ?>
            <div class="stat-item stat-social">
                <div class="stat-label">Social Media</div>
                <div class="stat-value social-icons">
                    <?php foreach($active_socials as $network => $url): ?>
                        <a href="<?php echo esc_url($url); ?>" 
                           class="social-icon social-icon-<?php echo esc_attr($network); ?>" 
                           target="_blank" 
                           rel="noopener" 
                           aria-label="<?php echo esc_attr(ucfirst($network)); ?>"></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('solawi_profile_card', 'solawi_profile_card_shortcode_render');


/**
 * Shortcode: [solawi_single_depots_list]
 * Zeigt eine Liste der Depots an, die von der aktuellen Solawi beliefert werden.
 */
function solawi_single_depots_list_render() {
    // Nur auf Einzelansicht ausführen
    if ( ! is_singular('solawi') ) return '';

    $post_id = get_the_ID();
    
    // Die IDs der verknüpften Depots holen
    $depot_ids = get_field('belieferte_depots', $post_id);

    if ( ! $depot_ids ) {
        return '<p>Keine Depots angegeben.</p>';
    }

    $output = '<div class="solawi-depot-list">';
    
    foreach ( $depot_ids as $depot_id ) {
        // Daten des Depots holen
        $title = get_the_title( $depot_id );
        $zeit  = get_field( 'anlieferzeit', $depot_id );
        $info  = get_field( 'abholhinweis', $depot_id );

        $output .= '<div class="single-depot-item">';
        $output .= '<h4 class="depot-title">' . esc_html($title) . '</h4>';
        
        if($zeit) {
            $output .= '<div class="depot-time">' . esc_html($zeit) . '</div>';
        }
        
        $output .= '<a href="#solawi-map" class="solawi-map-focus-trigger depot-map-link" data-depot-id="' . esc_attr($depot_id) . '" aria-label="Depot ' . esc_attr($title) . ' auf Karte zeigen">Auf Karte zeigen</a>';
        
        if($info) {
             $lines = preg_split('/\r\n|\r|\n/', trim($info));
             $lines_html = '';
             foreach($lines as $line) {
                 $line = trim($line);
                 if($line === '') continue;
                 $lines_html .= '<div class="depot-info-line">' . make_clickable(esc_html($line)) . '</div>';
             }
             $output .= '<div class="depot-info"><span>' . $lines_html . '</span></div>';
        }
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('solawi_single_depots_list', 'solawi_single_depots_list_render');

/**
 * Stellt die individuelle Solawi-Farbe als globale CSS-Variable (--solawi-color)
 * auf den Einzel-Seiten der Solawis zur Verfügung.
 */
function solawi_global_color_variable() {
    if ( is_singular('solawi') ) {
        $solawi_color = get_field('solawi_farbe', get_the_ID());
        if ( $solawi_color ) {
            $custom_css = 'body.single-solawi { --solawi-color: ' . esc_attr($solawi_color) . '; }';
            wp_add_inline_style( 'child-style', $custom_css );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'solawi_global_color_variable', 20 );
