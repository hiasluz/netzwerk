<?php
/**
 * Shortcode to display a gallery slider from an ACF group field named 'slider_gruppe'
 * Usage: [solawi_slider]
 */

function solawi_slider_shortcode() {
    
    // Check if we are in the loop of a post and fetch the field
    global $post;
    if ( ! $post ) return '';

    // 1. Die ganze Gruppe holen
    $slider_data = get_field('slider_gruppe', $post->ID);

    // If no data, return nothing
    if( empty($slider_data) || ! is_array($slider_data) ) {
        return '';
    }

    // Prepare slides content
    $slides_html = '';
    $has_images = false;
    $count = 0;

    foreach( $slider_data as $sub_field_name => $image_array ) {
        // Prüfung: Ist das Feld wirklich ein Bild und nicht leer?
        if( !empty($image_array) && is_array($image_array) ) {
            $has_images = true;
            $count++;
            
            // Get URLs and Alt text
            // Prefer 'large' size, fallback to original URL
            $img_src = isset($image_array['sizes']['large']) ? $image_array['sizes']['large'] : $image_array['url'];
            $img_alt = isset($image_array['alt']) ? $image_array['alt'] : '';

            $active_class = ($count === 1) ? 'active' : '';

            $slides_html .= '<div class="solawi-slide ' . $active_class . '">';
            // Neuer Wrapper für die Zentrierung des Bildes (ersetzt object-fit)
            $slides_html .= '<div class="solawi-slide-img-wrapper">';
            $slides_html .= '<img src="' . esc_url($img_src) . '" alt="' . esc_attr($img_alt) . '" />';
            $slides_html .= '</div>';
            // Optional: Image Caption if user wants it (ACF image 'caption' or 'title')
            // $slides_html .= '<div class="text">Caption Text</div>';

            $slides_html .= '</div>';
        }
    }

    if ( ! $has_images ) {
        return '';
    }

    // Enqueue scripts only when shortcode is used
    wp_enqueue_script( 'solawi-slider-js', get_stylesheet_directory_uri() . '/js/slider.js', array(), '1.0', true );

    // Build the final output
    $output = '<div class="solawi-slider-container">';
    $output .= '<div class="solawi-slider-wrapper">';
    $output .= $slides_html;
    $output .= '</div>';
    
    // Add navigation buttons if more than one slide
    if ( $count > 1 ) {
        $output .= '<button class="solawi-slider-prev" aria-label="Vorheriges Bild"></button>';
        $output .= '<button class="solawi-slider-next" aria-label="Nächstes Bild"></button>';
    }
    
    $output .= '</div>';

    return $output;
}
add_shortcode('solawi_slider', 'solawi_slider_shortcode');
