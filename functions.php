<?php
#region Clean Up WP Admin Bar
function remove_admin_bar_links() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');          // Remove the Wordpress logo + sub links
	$wp_admin_bar->remove_menu('new-content');      // Remove the content link
}
add_action( 'wp_before_admin_bar_render', 'remove_admin_bar_links' );
#endregion

#region Enqueue Styles & Scripts
add_action( 'wp_enqueue_scripts', 'ernaehrung_enqueue_assets' );
function ernaehrung_enqueue_assets() {
	// Enqueue Parent and Child Theme Stylesheets
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'parent-style' ),
		wp_get_theme()->get( 'Version' )
	);

	// Enqueue Custom Scripts
	wp_enqueue_script(
		'ernaehrung-header-scroll', // Einzigartiger Name (Handle) für das Skript
		get_stylesheet_directory_uri() . '/js/header-scroll.js', // Pfad zur Datei
		array(), // Abhängigkeiten (hier keine)
		wp_get_theme()->get( 'Version' ), // Version (für Cache-Busting)
		true // Im Footer laden
	);
}
#endregion

#region Replace URL for Logo on Login Screen
function ernaehrung_url_login_logo(){
	return get_bloginfo( 'wpurl' );
}
add_filter('login_headerurl', 'ernaehrung_url_login_logo');
#endregion

#region Change title tag for Login Link
function ernaehrung_login_logo_url_title() {
	return 'Zurück zu ernaehrungfreiburg.de';
}
add_filter( 'login_headertext', 'ernaehrung_login_logo_url_title' );
#endregion

#region Replace Logo on WP Login Screen
function ernaehrung_custom_login_logo() {
	$logo_url = get_stylesheet_directory_uri() . '/assets/images/Logo-Solawi-Freiburg.png';
	$primary_color = '#3A6B46';
	$primary_color_hover = '#315a3b'; 
	?>
	<style type="text/css">
		#login h1 a, .login h1 a {
			background-image: url(<?php echo esc_url( $logo_url ); ?>);
			height: 80px; /* <- Höhe deines Logos anpassen */
			width: 320px; /* <- Breite deines Logos anpassen */
			background-size: contain;
			background-repeat: no-repeat;
		}

		/* Sprachauswahl ausblenden */
		.login .language-switcher {
			display: none;
		}

		/* Login-Button an die Hauptfarbe anpassen */
		.wp-core-ui .button-primary {
			background: <?php echo $primary_color; ?> !important;
			border-color: <?php echo $primary_color; ?> !important;
			box-shadow: none !important;
			text-shadow: none !important;
		}
		.wp-core-ui .button-primary:hover,
		.wp-core-ui .button-primary:focus {
			background: <?php echo $primary_color_hover; ?> !important;
			border-color: <?php echo $primary_color_hover; ?> !important;
		}

		/* "Passwort anzeigen"-Button anpassen */
		.login .wp-hide-pw.button-secondary {
			color: <?php echo $primary_color; ?> !important;
			border-color: <?php echo $primary_color; ?> !important;
		}
		.login .wp-hide-pw.button-secondary:hover,
		.login .wp-hide-pw.button-secondary:focus {
			color: <?php echo $primary_color_hover; ?> !important;
			border-color: <?php echo $primary_color_hover; ?> !important;
		}

		/* Link-Hover-Farbe anpassen ("Passwort vergessen?", "Zurück zu...") */
		.login #backtoblog a:hover,
		.login #nav a:hover {
			color: <?php echo $primary_color; ?> !important;
		}
	</style>
	<?php
}
add_action( 'login_head', 'ernaehrung_custom_login_logo' );
#endregion

#region Add Widget with Developer Info in WP Dashboard
function ernaehrung_add_dashboard_widgets() {
	wp_add_dashboard_widget('ernaehrung_dashboard_widget', 'Designer & Developer Info', 'ernaehrung_theme_info');
}
add_action('wp_dashboard_setup', 'ernaehrung_add_dashboard_widgets' );

function ernaehrung_theme_info() {
	echo '<ul>
	<li><strong>Entwickelt von:</strong> <a href="https://ernaehrungfreiburg.de" target="_blank" rel="noopener">NetzFr</a></li>
	<li><strong>E-Mail:</strong> <a href="mailto:info@ernaehrungfreiburg.de">info@ernaehrungfreiburg.de</a></li>
	</ul>';
}
#endregion

#region Load Child Theme Language Files
function ernaehrung_translations() {
	// load custom translation file for the parent theme
	load_child_theme_textdomain( 'Divi', get_stylesheet_directory() . '/lang' );
	load_child_theme_textdomain( 'et_builder', get_stylesheet_directory() . '/lang/builder' );
}
add_action( 'after_setup_theme', 'ernaehrung_translations');
#endregion

#region Prevent Thumbnail Generation
function ernaehrung_disable_image_sizes($sizes) {
	// Remove thumbnail and medium sizes
	unset($sizes['thumbnail']);    // 150x150
	unset($sizes['medium']);       // 300x300
	
	// Keep other sizes
	return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'ernaehrung_disable_image_sizes');
#endregion Prevent Thumbnail Generation

#region Allow SVG File Upload
function ernaehrung_allow_svg($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'ernaehrung_allow_svg');

function ernaehrung_really_allow_svg($checked, $file, $filename, $mimes){
	if(!$checked['type']){
		$wp_filetype = wp_check_filetype( $filename, $mimes );
		$ext = $wp_filetype['ext'];
		$type = $wp_filetype['type'];
		$proper_filename = $filename;
		if($type && 0 === strpos($type, 'image/') && $ext !== 'svg'){
			$ext = $type = false;
		}	
		$checked = compact('ext','type','proper_filename');
	}
	return $checked;
}
add_filter('wp_check_filetype_and_ext', 'ernaehrung_really_allow_svg', 10, 4);
#endregion

#region Disable Gutenberg Widget Editing
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );
// Disables the block editor from managing widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );
#endregion

#region Sanitize names of uploaded files
function ernaehrung_sanitize_upload_name($filename) {
	$sanitized_filename = remove_accents($filename); // Convert to ASCII

	// Standard replacements
	$invalid = array(
	' ' => '-',
	'%20' => '-',
	'_' => '-',
	);
	$sanitized_filename = str_replace(array_keys($invalid), array_values($invalid), $sanitized_filename);

	// Remove all non-alphanumeric except .
	$sanitized_filename = preg_replace('/[^A-Za-z0-9-\. ]/', '', $sanitized_filename);
	// Remove all but last .
	$sanitized_filename = preg_replace('/\.(?=.*\.)/', '-', $sanitized_filename);
	// Replace any more than one - in a row
	$sanitized_filename = preg_replace('/-+/', '-', $sanitized_filename);
	// Remove last - if at the end
	$sanitized_filename = str_replace('-.', '.', $sanitized_filename);
	// Lowercase
	$sanitized_filename = strtolower($sanitized_filename);
	return $sanitized_filename;
}	
add_filter("sanitize_file_name", "ernaehrung_sanitize_upload_name", 10, 1);
#endregion Sanitize names of uploaded files

#region Enable Zoom for Divi Theme
function ernaehrung_enable_zoom() {
	remove_action( 'wp_head', 'et_add_viewport_meta' );
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
}
add_action('wp_head', 'ernaehrung_enable_zoom', 1);
#endregion Enable Zoom for Divi Theme

#region Include Custom PHP Files
foreach ( glob( get_stylesheet_directory() . '/php/*.php' ) as $file ) {
    require_once $file;
}
#endregion

add_filter('site_status_should_suggest_persistent_object_cache', '__return_false');

#region Harden WordPress Security
// Customize login error messages
function ernaehrung_login_error_message() {
	return 'Die eingegebenen Anmeldedaten sind nicht korrekt.';
}
add_filter('login_errors', 'ernaehrung_login_error_message');

// Remove detailed password reset messages
function ernaehrung_remove_reset_messages($errors) {
	$errors->remove('invalid_email');
	$errors->remove('empty_username');
	$errors->add('invalid_combination', 'Wenn ein Konto mit den angegebenen Daten existiert, erhalten Sie eine E-Mail mit weiteren Anweisungen.');
	return $errors;
}
add_filter('lostpassword_errors', 'ernaehrung_remove_reset_messages');

// Disable user enumeration
function ernaehrung_disable_user_enumeration() {
	// Block author query var
	if (isset($_REQUEST['author']) && !is_admin()) {
		wp_redirect(home_url(), 301);
		exit;
	}
	// Block author URLs
	if (preg_match('/author=([0-9]*)/i', $_SERVER['QUERY_STRING'])) {
		wp_redirect(home_url(), 301);
		exit;
	}
	
	// Block author feeds
	if (preg_match('/wp-json\/wp\/v2\/users/i', $_SERVER['REQUEST_URI'])) {
		wp_redirect(home_url(), 301);
		exit;
	}
	
	// Block ALL author archives, regardless of whether they exist
	if (preg_match('/\/author\/.*/', $_SERVER['REQUEST_URI'])) {
		wp_redirect(home_url(), 301);
		exit;
	}
}
add_action('template_redirect', 'ernaehrung_disable_user_enumeration');

// Generic registration error messages
function ernaehrung_registration_privacy($errors) {
	// Clear any existing messages about email or username existence
	$errors->remove('email_exists');
	$errors->remove('username_exists');
	
	// Add a generic message that's shown for ALL registration attempts
	$errors->add('registration_notice', 'Wenn Sie sich registrieren möchten, erhalten Sie eine E-Mail mit weiteren Anweisungen. Wenn Sie bereits ein Konto haben, nutzen Sie bitte die Anmeldeseite.');
	
	return $errors;
}
add_filter('registration_errors', 'ernaehrung_registration_privacy');

// Protect AJAX registration checks
function ernaehrung_check_email_privacy() {
	wp_send_json_success(array(
		'msg' => 'Bitte fahren Sie mit der Registrierung fort.'
	));
	exit;
}
add_action('wp_ajax_check_email', 'ernaehrung_check_email_privacy');
add_action('wp_ajax_nopriv_check_email', 'ernaehrung_check_email_privacy');
#endregion Harden WordPress Security
function solawi_single_depots_list_render() {
    // Nur auf Einzelansicht ausführen
    if ( ! is_singular('solawi') ) return '';

    $post_id = get_the_ID();
    
    // Die IDs der verknüpften Depots holen
    // Da wir Rückgabeformat "Post ID" eingestellt haben, bekommen wir ein Array von IDs
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
             $output .= '<div class="depot-info">' . esc_html($info) . '</div>';
        }
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('solawi_single_depots_list', 'solawi_single_depots_list_render');