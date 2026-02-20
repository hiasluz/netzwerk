<?php
/**
 * FAQ Schema Generator für Divi Accordions
 * 
 * Analysiert das gerenderte HTML im Footer nach Divi Accordion-Elementen
 * und generiert automatisch ein FAQPage JSON-LD Schema.
 * Funktioniert nur auf Seiten, die mindestens ein Accordion enthalten.
 */

function solawi_faq_schema_output() {
    // Nur im Frontend
    if (is_admin()) return;
    
    // Output Buffer starten, um den gerenderten HTML-Content zu erfassen
    // Das machen wir früh im Template, bevor irgendwas ausgegeben wird
    ob_start();
}
add_action('template_redirect', 'solawi_faq_schema_output', 1);

function solawi_faq_schema_inject() {
    // Nur im Frontend
    if (is_admin()) return;
    
    // Hole den gepufferten Content
    $content_html = ob_get_contents();
    
    // Performance-Check: Nur fortfahren, wenn ein Accordion/Toggle im HTML gefunden wird.
    // Das verhindert unnötiges Parsen auf Seiten ohne FAQs.
    if (empty($content_html) || strpos($content_html, 'et_pb_toggle') === false) {
        return;
    }
    
    // Schema-Datenstruktur vorbereiten
    $schema_data = array(
        "@context" => "https://schema.org",
        "@type" => "FAQPage",
        "mainEntity" => array()
    );
    
    // HTML parsen mit DOMDocument
    $dom = new DOMDocument();
    
    // Fehler unterdrücken (HTML-Fragmente sind keine kompletten Dokumente)
    libxml_use_internal_errors(true);
    
    // UTF-8 Encoding sicherstellen (wichtig für Umlaute!)
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content_html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Suche alle Divi Toggle/Accordion Items
    $items = $xpath->query("//*[contains(@class, 'et_pb_toggle')]");
    
    foreach ($items as $item) {
        // Titel suchen (Klasse: et_pb_toggle_title)
        $titles = $xpath->query(".//*[contains(@class, 'et_pb_toggle_title')]", $item);
        // Inhalt suchen (Klasse: et_pb_toggle_content)
        $contents = $xpath->query(".//*[contains(@class, 'et_pb_toggle_content')]", $item);
        
        if ($titles->length > 0 && $contents->length > 0) {
            $frage = $titles->item(0)->textContent;
            $antwort = $contents->item(0)->textContent;
            
            // Bereinigen (Leerzeichen trimmen)
            $frage = trim($frage);
            $antwort = trim($antwort);
            
            // Mehrfache Leerzeichen und Zeilenumbrüche in der Antwort bereinigen
            $antwort = preg_replace('/\s+/', ' ', $antwort);
            
            if (!empty($frage) && !empty($antwort)) {
                $schema_data['mainEntity'][] = array(
                    "@type" => "Question",
                    "name" => $frage,
                    "acceptedAnswer" => array(
                        "@type" => "Answer",
                        "text" => $antwort
                    )
                );
            }
        }
    }
    
    // Nur ausgeben, wenn FAQs gefunden wurden
    if (!empty($schema_data['mainEntity'])) {
        echo "\n" . '<!-- Solawi FAQ Schema -->' . "\n";
        echo '<script type="application/ld+json">';
        echo json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo '</script>' . "\n";
    }
}
add_action('wp_footer', 'solawi_faq_schema_inject', 999);
