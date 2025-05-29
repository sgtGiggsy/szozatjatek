<?php
/**
 * @package Szozat
 */

/**
 * Visszaadja a táblák létrehozásához szükséges SQL sémákat.
 *
 * @param string $prefix
 * @param string $charset_collate
 * @return array
 */

function szozat_get_table_schemas($prefix, $charset_collate) {
    return [
        // szozat_feladvanyok
        "CREATE TABLE {$prefix}szozat_feladvanyok (
            feladvany_id INT(11) NOT NULL AUTO_INCREMENT,
            feladvany_szoveg VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            nap DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            egyszavas TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (feladvany_id),
            UNIQUE KEY feladvany_szoveg (feladvany_szoveg),
            KEY nap_index (nap)
        ) $charset_collate;",

        // szozat_kitoltesek – nincsenek FOREIGN KEY-ek, hogy dbDelta kezelni tudja
        "CREATE TABLE {$prefix}szozat_kitoltesek (
            kitoltes_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            felhasznalo_id BIGINT(20) UNSIGNED NOT NULL,
            feladvany_id INT(11) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            kiserletszam INT(2) NOT NULL DEFAULT 0,
            sikeres TINYINT(1) DEFAULT NULL,
            valaszok LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            PRIMARY KEY (kitoltes_id),
            KEY felhasznalo_idx (felhasznalo_id),
            KEY feladvany_idx (feladvany_id)
        ) $charset_collate;",

        // szozat_legalisszavak – megnevezett index
        "CREATE TABLE {$prefix}szozat_legalisszavak (
            szo VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            KEY szo_index (szo)
        ) $charset_collate;"
    ];
}

function populate_legalisszavak() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    $locale = determine_locale();

    if (strpos($locale, 'hu_') === 0) {
        include_once SZOZAT_PLUGIN_DIR . 'languages/hun-db.php';
    } else {
        include_once SZOZAT_PLUGIN_DIR . 'languages/eng-db.php';
    }
}
