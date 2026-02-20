<?php
/**
 * -------------------------------------------------------------------------
 * TEIL 3 (Update): Solawi Grid Shortcode & AJAX Filterung
 * -------------------------------------------------------------------------
 * Features:
 * 1. Textsuche (Nach Name der Solawi)
 * 2. Dropdown (Nach Depot-Ort filtern)
 * 3. Checkboxen (Nach Produkten filtern)
 */

// 1. Hilfsfunktion: HTML für eine einzelne Solawi-Karte rendern
if ( ! function_exists( 'solawi_render_card_html' ) ) {
    function solawi_render_card_html( $post_id ) {
        $permalink = get_permalink( $post_id );
        $title     = get_the_title( $post_id );
        $img_url   = get_the_post_thumbnail_url( $post_id, 'medium' );
        $excerpt   = get_the_excerpt( $post_id );
        
        // Fallback Bild
        if ( ! $img_url ) { $img_url = 'https://via.placeholder.com/400x300?text=Solawi'; }
    
        // Kategorien
        $terms = get_the_terms( $post_id, 'produktkategorie' );
        $term_list = '';
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $term_list .= '<span class="solawi-cat-badge solawi-cat-' . esc_attr($term->slug) . '">' . esc_html( $term->name ) . '</span> ';
            }
        }
    
        // --- NEU: Status Ampel Logik ---
        $status_key = get_field( 'status_mitgliedschaft', $post_id ); // Values: offen, warteliste, voll
        
        // Baue die CSS-Klasse direkt aus dem Status-Wert (z.B. "status-offen")
        $status_class = $status_key ? 'status-' . $status_key : '';
    
        // Hole das passende Label für das Badge
        $status_label = '';
        switch ($status_key) {
            case 'offen': $status_label = 'Freie Plätze'; break;
            case 'warteliste': $status_label = 'Warteliste'; break;
            case 'voll': $status_label = 'Aufnahmestopp'; break;
            case 'laden': $status_label = 'Bioladen'; break;
            case 'kiste': $status_label = 'Biokiste'; break;
        }
        // -------------------------------
    
        // NEU: Eigene Farbe der Solawi
        $custom_color = get_field('solawi_farbe', $post_id);
        $style_attr = '';
        if ($custom_color) {
            $style_attr = 'style="--solawi-color: ' . esc_attr($custom_color) . ';"';
        }
    
        ob_start();
        ?>
        <article class="solawi-card <?php echo esc_attr( $status_class ); ?>" <?php echo $style_attr; ?>>
            <a href="<?php echo esc_url( $permalink ); ?>" class="solawi-card-link">
                <div class="solawi-card-image" style="background-image: url('<?php echo esc_url( $img_url ); ?>');">
                    <?php if($status_label): ?>
                        <span class="solawi-status-badge <?php echo $status_class; ?>">
                            <?php echo $status_label; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="solawi-card-content">
                    <h3 class="solawi-card-title"><?php echo esc_html( $title ); ?></h3>
                    <div class="solawi-card-cats"><?php echo $term_list; ?></div>
                    <div class="solawi-card-excerpt"><?php echo wp_trim_words( $excerpt, 12, '...' ); ?></div>
                    <span class="solawi-btn"><?php echo esc_html( $title ); ?> entdecken &rarr;</span>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
}

// 2. Der Shortcode [solawi_grid]
function solawi_grid_shortcode_render() {
    
    // Enqueue assets for the grid
    $theme = wp_get_theme();
    wp_enqueue_script( 'solawi-grid-script', get_stylesheet_directory_uri() . '/js/solawi-searchgrid.js', array(), $theme->get('Version'), true );

    // Pass data to JS (AJAX URL and nonce for security)
    wp_localize_script( 'solawi-grid-script', 'solawiGridData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'solawi_filter_nonce' )
    ));
    
    // Daten für Filter vorbereiten
    // A. Produkte
    $terms = get_terms( array( 'taxonomy' => 'produktkategorie', 'hide_empty' => true ) );
    
    // B. Depots (für das Dropdown "Wer liefert wohin?")
    $depots = get_posts( array( 'post_type' => 'depot', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );

    // C. Solawis (für das Dropdown "Solawis")
    $all_solawis = get_posts( array( 'post_type' => 'solawi', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );

    ob_start();
    ?>
    <div class="solawi-archive-wrapper">
        
        <form id="solawi-filter-form" class="solawi-filters">
            <div class="filter-row-top">
                <div class="filter-group search-group">
                    <label for="filter-search">Ernährungsnetzwerk:</label>
                    <select id="filter-search" name="search">
                        <option value="">Alle im Netzwerk</option>
                        <?php foreach($all_solawis as $solawi_post): ?>
                            <option value="<?php echo $solawi_post->ID; ?>"><?php echo esc_html($solawi_post->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group depot-group">
                    <label for="filter-depot">Liefert nach (Verteilpunkt):</label>
                    <select id="filter-depot" name="depot">
                        <option value="">Alle Standorte</option>
                        <?php foreach($depots as $depot): ?>
                            <option value="<?php echo $depot->ID; ?>"><?php echo esc_html($depot->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-row-bottom">
                <label>Produkte:</label>
                <div class="filter-checkboxes">
                    <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                        <?php foreach ( $terms as $term ) : ?>
                            <label class="filter-item">
                                <input type="checkbox" name="produktkategorie[]" value="<?php echo esc_attr( $term->term_id ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div id="solawi-loader" style="display:none; text-align:center; padding: 20px;">
            <span class="dashicons dashicons-update" style="animation: spin 2s infinite linear;"></span> Aktualisiere...
        </div>

        <div id="solawi-grid-container" class="solawi-grid">
            <?php
            $args = array(
                'post_type'      => 'solawi',
                'posts_per_page' => -1,
                'status'         => 'publish',
                'orderby'        => 'rand' // Zufällige Reihenfolge
                'orderby'        => 'title', // 'rand' ist sehr langsam, 'title' ist performanter
                'order'          => 'ASC'
            );
            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    echo solawi_render_card_html( get_the_ID() );
                }
                wp_reset_postdata();
            } else {
                echo '<p>Keine Solawis gefunden.</p>';
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'solawi_grid', 'solawi_grid_shortcode_render' );


// 3. Der AJAX Handler (Backend-Logik) - UPDATE
function solawi_ajax_filter_handler() {
    
    // Security check: Verify the nonce
    check_ajax_referer( 'solawi_filter_nonce', 'nonce' );

    $term_ids   = isset( $_POST['terms'] ) ? $_POST['terms'] : array();
    $search_val = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $depot_id   = isset( $_POST['depot'] ) ? intval( $_POST['depot'] ) : '';

    $args = array(
        'post_type'      => 'solawi',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'rand',
        'order'          => 'ASC'
    );

    // FILTER 1: Produktsuche (Tax Query)
    if ( ! empty( $term_ids ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'produktkategorie',
                'field'    => 'term_id',
                'terms'    => $term_ids,
                'operator' => 'IN',
            ),
        );
    }

    // FILTER 2: Solawi Filter (neu: Dropdown -> ID)
    if ( ! empty( $search_val ) ) {
        // Wenn es eine Nummer ist, suchen wir die spezifische Solawi ID
        if ( is_numeric( $search_val ) ) {
            $args['p'] = intval( $search_val );
        } else {
            // Fallback: Textsuche (falls jemals wieder Input genutzt wird)
            $args['s'] = $search_val;
        }
    }

    // FILTER 3: Depot (Meta Query auf Relationship Feld)
    if ( ! empty( $depot_id ) ) {
        // Wir suchen in Solawis, die im ACF Feld "belieferte_depots" die ID des gewählten Depots haben.
        // ACF speichert Relationships als serialisiertes Array in der DB (z.B. a:1:{i:0;s:2:"15";})
        // ODER einfach als ID, wenn nur 1 erlaubt ist. Bei Relationships ist 'LIKE' oft der sicherste Weg.
        $args['meta_query'][] = array(
            'key'     => 'belieferte_depots',
            'value'   => '"' . $depot_id . '"', // Suche nach der ID in Anführungszeichen (String Search im Array)
            'compare' => 'LIKE'
        );
    }

    $query = new WP_Query( $args );

    ob_start();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            echo solawi_render_card_html( get_the_ID() );
        }
    } else {
        $message = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">';
        $message .= '<h4>Keine Solawis gefunden</h4>';
        $message .= '<p>Leider gibt es keine Ergebnisse für diese Kombination.</p>';
        $message .= '</div>';
        echo $message;
    }
    $html = ob_get_clean();

    wp_reset_postdata();
    wp_send_json_success( $html );
}

// Hooks bleiben gleich (nur sicherstellen, dass sie nicht doppelt vorkommen)
if( !has_action('wp_ajax_filter_solawis') ) {
    add_action( 'wp_ajax_filter_solawis', 'solawi_ajax_filter_handler' );
    add_action( 'wp_ajax_nopriv_filter_solawis', 'solawi_ajax_filter_handler' );
}
?>