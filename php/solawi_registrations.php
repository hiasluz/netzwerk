<?php
/**
 * Registrierung der Custom Post Types und Taxonomien.
 * Code für die functions.php des Child-Themes.
 */

function solawi_register_post_types() {

    // 1. CPT: Solawis
    $labels_solawi = array(
        'name'                  => 'Solawis',
        'singular_name'         => 'Solawi',
        'menu_name'             => 'Solawis',
        'add_new'               => 'Neue Solawi',
        'add_new_item'          => 'Neue Solawi hinzufügen',
        'edit_item'             => 'Solawi bearbeiten',
        'new_item'              => 'Neue Solawi',
        'view_item'             => 'Solawi ansehen',
        'all_items'             => 'Alle Solawis',
        'search_items'          => 'Solawis durchsuchen',
        'not_found'             => 'Keine Solawis gefunden',
        'featured_image'        => 'Solawi Logo/Bild',
        'set_featured_image'    => 'Logo festlegen',
    );

    $args_solawi = array(
        'labels'             => $labels_solawi,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'solawi' ), // URL: deinseite.de/solawi/hofname
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-carrot', // Karotten-Icon
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ), // Editor für Beschreibung, Thumbnail für Logo
        'show_in_rest'       => true, // Wichtig für Block Editor / Divi
    );

    register_post_type( 'solawi', $args_solawi );

    // 2. CPT: Depots (Verteilstationen)
    $labels_depot = array(
        'name'                  => 'Depots',
        'singular_name'         => 'Depot',
        'menu_name'             => 'Depots',
        'add_new'               => 'Neues Depot',
        'add_new_item'          => 'Neues Depot hinzufügen',
        'edit_item'             => 'Depot bearbeiten',
        'all_items'             => 'Alle Depots',
        'menu_icon'             => 'dashicons-location',
    );

    $args_depot = array(
        'labels'             => $labels_depot,
        'public'             => true, // Muss public sein, damit wir sie abfragen können
        'show_ui'            => true,
        'show_in_menu'       => true, // Untermenü von Solawis, damit das Menü sauber bleibt
        'rewrite'            => array( 'slug' => 'depot' ),
        'capability_type'    => 'post',
        'has_archive'        => false, // Depots brauchen meist keine eigene Archivseite
        'hierarchical'       => false,
        'menu_position'      => 6,
        'supports'           => array( 'title' ), // Nur Titel, Rest via ACF
        'show_in_rest'       => true,
    );

    register_post_type( 'depot', $args_depot );

    // 3. Taxonomie: Produktkategorien (Besser als ACF Checkbox für Filterung!)
    $labels_tax = array(
        'name'              => 'Produktkategorien',
        'singular_name'     => 'Produktkategorie',
        'search_items'      => 'Kategorien durchsuchen',
        'all_items'         => 'Alle Kategorien',
        'edit_item'         => 'Kategorie bearbeiten',
        'update_item'       => 'Kategorie aktualisieren',
        'add_new_item'      => 'Neue Kategorie hinzufügen',
        'new_item_name'     => 'Neuer Kategoriename',
        'menu_name'         => 'Produkte',
    );

    $args_tax = array(
        'hierarchical'      => true, // Wie Checkboxen
        'labels'            => $labels_tax,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'produkte' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'produktkategorie', array( 'solawi' ), $args_tax );

    // 4. CPT: Veranstaltungen (Events)
    $labels_event = array(
        'name'                  => 'Veranstaltungen',
        'singular_name'         => 'Veranstaltung',
        'menu_name'             => 'Veranstaltungen',
        'add_new'               => 'Neue Veranstaltung',
        'add_new_item'          => 'Neue Veranstaltung hinzufügen',
        'edit_item'             => 'Veranstaltung bearbeiten',
        'new_item'              => 'Neue Veranstaltung',
        'view_item'             => 'Veranstaltung ansehen',
        'all_items'             => 'Alle Veranstaltungen',
        'search_items'          => 'Veranstaltungen durchsuchen',
        'not_found'             => 'Keine Veranstaltungen gefunden',
        'not_found_in_trash'    => 'Keine Veranstaltungen im Papierkorb gefunden',
        'featured_image'        => 'Veranstaltungsbild',
        'set_featured_image'    => 'Bild festlegen',
        'remove_featured_image' => 'Bild entfernen',
        'use_featured_image'    => 'Als Bild verwenden',
    );

    $args_event = array(
        'labels'             => $labels_event,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'veranstaltung' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 7,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'veranstaltung', $args_event );
}

add_action( 'init', 'solawi_register_post_types' );

/*
 * HINWEIS ZU ACF FELDERN:
 * Erstelle in ACF nun folgende Felder für die Funktionalität:
 *
 * 1. Feldgruppe "Solawi Details" (für Post Type: solawi):
 * - Feld "belieferte_depots" (Typ: Beziehung / Relationship).
 * - Filter nach Post Type: Depot.
 * - Rückgabewert: Post ID.
 * - Feld "homepage" (Typ: URL).
 * - Feld "mitglieder" (Typ: Zahl).
 *
 * 2. Feldgruppe "Depot Daten" (für Post Type: depot):
 * - Feld "geo_lat" (Typ: Text/Zahl) -> Breitengrad (z.B. 52.5200)
 * - Feld "geo_lng" (Typ: Text/Zahl) -> Längengrad (z.B. 13.4050)
 * (Oder ein Google Maps Feld, wenn API Key vorhanden, hier im Code nutze ich Lat/Lng Felder)
 * - Feld "anlieferzeit" (Typ: Text).
 * - Feld "abholhinweis" (Typ: Textarea).
 */
?>