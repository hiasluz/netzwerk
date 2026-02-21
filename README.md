# Divi Child-Theme für das Ernährungsnetzwerk Freiburg

Dieses Repository enthält das Child-Theme für die Webseite des Ernährungsnetzwerks Freiburg, basierend auf dem Divi-Theme. Es erweitert die Seite um spezifische Funktionen für die Darstellung von Solidarischen Landwirtschaften (Solawis), deren Verteilpunkten (Depots) und Veranstaltungen.

## Features

- **Interaktive Karte:** Eine auf [Leaflet.js](https://leafletjs.com/) basierende Karte zur dynamischen Anzeige von Solawi-Standorten und Depot-Verteilpunkten.
- **Solawi-Verzeichnis:** Ein filterbares Grid-System für alle Solawis, mit AJAX-gestützter Live-Suche nach:
  - Name der Solawi
  - Lieferort (Depot)
  - Angebotenen Produkten (Produktkategorien)
- **Veranstaltungssystem:** Ein Custom Post Type für Veranstaltungen mit Anzeige kommender Termine, inklusive Unterstützung für mehrtägige Events.
- **Dynamisches Design:** Solawi-spezifische Farben werden automatisch auf Überschriften, Icons und andere Design-Elemente auf der jeweiligen Detailseite angewendet.
- **Strukturierte Daten (CPTs):** Eigene Post-Types für `Solawi`, `Depot` und `Veranstaltung` sorgen für eine saubere und wartbare Datenhaltung.
- **Automatische SEO-Optimierung:** Generiert automatisch valides `FAQPage`- und `Event`-Schema (JSON-LD) aus den Inhalten, was die Sichtbarkeit in Suchmaschinen verbessert.
- **WordPress-Anpassungen & Härtung:**
  - Angepasster Login-Screen im Design der Webseite.
  - Bereinigtes Admin-Menü für eine bessere Nutzererfahrung.
  - Diverse Sicherheitsmaßnahmen zur Härtung des WordPress-Backends (z.B. gegen User-Enumeration).
  - Erlaubt den Upload von SVG-Dateien (mit den bekannten Sicherheitsüberlegungen).
- **Modernes Frontend:** Entwickelt mit SCSS für wartbare und modulare Styles. Alle Icons sind als Data-URIs direkt im CSS eingebettet, um HTTP-Requests zu minimieren.

## Installation

1. **Voraussetzung:** Das **Divi Parent Theme** von Elegant Themes muss im `wp-content/themes` Ordner installiert sein.
2. Klone oder lade dieses Repository als ZIP-Datei herunter.
3. Lade den entpackten Ordner `netz` (oder wie auch immer du ihn nennst) in das Verzeichnis `/wp-content/themes/` deiner WordPress-Installation hoch.
4. Gehe im WordPress-Backend zu `Design` > `Themes` und aktiviere das Child-Theme "ernaehrungfreiburg".

## Konfiguration & Abhängigkeiten

Damit alle Funktionen korrekt arbeiten, sind einige Konfigurationen notwendig.

### 1. Divi Theme

Dieses Theme ist ein **Child-Theme** und funktioniert nur, wenn das Divi Parent Theme installiert ist.

### 2. Advanced Custom Fields (ACF)

Ein Großteil der Daten wird über ACF-Felder verwaltet. Stelle sicher, dass das ACF-Plugin (Pro-Version wird empfohlen) installiert ist und die folgenden Feldgruppen und Felder existieren. Die genauen Feldnamen sind entscheidend!

#### Feldgruppe: "Solawi Details" (zugewiesen an CPT `solawi`)

- `belieferte_depots`: Beziehung (Relationship), Rückgabewert: Post ID
- `status_mitgliedschaft`: Auswahl (Select), Werte: `offen`, `warteliste`, `voll`, `laden`, `kiste`
- `solawi_farbe`: Farbe (Color Picker)
- `geo_lat` / `geo_lng`: Text oder Zahl (für Kartenkoordinaten)
- `mitglieder_aktuell` / `mitglieder_max`: Zahl
- `gruendungsjahr`: Zahl
- `betriebsstatte_adresse`: Textbereich
- `mail`, `telefon`, `homepage`: E-Mail, Text, URL
- `social_media`: Gruppe (Group)
  - `instagram`: URL
  - `facebook`: URL
  - `telegram`: URL
  - `mastodon`: URL
  - `bluesky`: URL
  - `youtube`: URL

#### Feldgruppe: "Depot Daten" (zugewiesen an CPT `depot`)

- `geo_lat` / `geo_lng`: Text oder Zahl
- `anlieferzeit`: Text
- `abholhinweis`: Textbereich

#### Feldgruppe: "Veranstaltungsdetails" (zugewiesen an CPT `veranstaltung`)

- `event_date` / `event_date_2` / `event_date_3`: Datum (Date Picker), Rückgabeformat: `Ymd`
- `event_start` / `event_end` / `event_start_2` / `event_end_2` / `event_start_3` / `event_end_3`: Zeit (Time Picker)
- `event_modus`: Auswahl (Select), Werte: `offline`, `online`
- `event_online_link`: URL
- `strasse`, `plz`, `stadt`: Text
- `event_organizer`, `kosten`: Text

#### Feldgruppe: "Slider-Galerie" (zugewiesen an beliebige Post-Types, z.B. `page` oder `post`)

- `slider_gruppe`: Gruppe (Group). Innerhalb dieser Gruppe können beliebig viele Bild-Felder (Image) angelegt werden. Die Namen der inneren Felder sind nicht relevant.
  - Rückgabewert für die Bild-Felder: Bild-Array (Image Array).

## Shortcodes

Das Theme stellt mehrere Shortcodes zur Verfügung, die im Divi Builder oder anderen Inhaltsbereichen verwendet werden können.

- `[solawi_grid]`
  - **Funktion:** Zeigt das filterbare Verzeichnis aller Solawis an.
  - **Einsatzort:** Hauptseite des Solawi-Verzeichnisses.

- `[solawi_map]`
  - **Funktion:** Zeigt die interaktive Leaflet-Karte an. Auf einer Archivseite zeigt sie alle Orte, auf einer Solawi-Einzelseite nur die Standorte und Depots der jeweiligen Solawi.
  - **Einsatzort:** Beliebige Seiten.

- `[solawi_events]`
  - **Funktion:** Zeigt eine Grid-Ansicht der nächsten 3 anstehenden Veranstaltungen.
  - **Einsatzort:** Beliebige Seiten, z.B. die Startseite.

- `[solawi_event_meta]`
  - **Funktion:** Zeigt die Metadaten (Datum, Zeit, Ort) für eine einzelne Veranstaltung an.
  - **Einsatzort:** Nur auf der Template-Seite für einzelne Veranstaltungen (`veranstaltung`).

- `[solawi_profile_card]`
  - **Funktion:** Zeigt eine "Quartett"-artige Karte mit den Stammdaten einer Solawi (Gründungsjahr, Mitglieder etc.).
  - **Einsatzort:** Nur auf der Template-Seite für einzelne Solawis (`solawi`). Die Icons übernehmen automatisch die Farbe der Solawi.

- `[solawi_single_depots_list]`
  - **Funktion:** Zeigt eine Liste der Depots, die von einer bestimmten Solawi beliefert werden. Die Titel der Depots werden automatisch in der Farbe der Solawi dargestellt.
  - **Einsatzort:** Nur auf der Template-Seite für einzelne Solawis (`solawi`).

- `[solawi_slider]`
  - **Funktion:** Zeigt eine Bilder-Galerie als Slider an. Die Bilder werden aus der ACF-Feldgruppe `slider_gruppe` des jeweiligen Beitrags/Seite geladen.
  - **Einsatzort:** Beliebige Seiten oder Beiträge, auf denen die Feldgruppe "Slider-Galerie" aktiv ist.

## Entwicklung

- **Styling:** Alle Styles werden in `style.scss` geschrieben. Diese Datei muss mit einem SCSS-Compiler (z.B. via Node.js/Gulp, Prepros oder VS Code Extension) in die `style.css` kompiliert werden, damit die Änderungen sichtbar werden.
- **PHP-Logik:** Die Funktionalität ist modular in mehrere PHP-Dateien im `/php/` Verzeichnis aufgeteilt, die von der `functions.php` geladen werden. Funktionen, die sich speziell auf die Einzelansicht einer Solawi beziehen, sind in `php/solawi_profile.php` gebündelt.
- **JavaScript:** Eigene Skripte für die interaktiven Features (Karte, Filter) befinden sich im `/js/` Verzeichnis.

---

**Entwickelt von:** hiasluz
**Kontakt:** <info@luzernenhof.de>
