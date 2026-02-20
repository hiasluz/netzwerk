<?php
/**
 * Shortcode: [solawi_profile_card]
 * Zeigt die Eckdaten einer Solawi im "Quartett-Stil" an.
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
                        if($m_aktuell && $m_max) echo esc_html($m_aktuell) . ' <span class="sep">/</span> ' . esc_html($m_max);
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

        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('solawi_profile_card', 'solawi_profile_card_shortcode_render');
