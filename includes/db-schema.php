<?php
// Visszaadja az összes tábla létrehozó SQL utasítást dbDelta számára
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
            KEY nap (nap)
        ) $charset_collate;",

        // szozat_kitoltesek
        "CREATE TABLE {$prefix}szozat_kitoltesek (
            kitoltes_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            felhasznalo_id BIGINT(20) UNSIGNED NOT NULL,
            feladvany_id INT(11) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            kiserletszam INT(2) NOT NULL DEFAULT 0,
            sikeres TINYINT(1) DEFAULT NULL,
            valaszok LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            PRIMARY KEY (kitoltes_id),
            FOREIGN KEY (felhasznalo_id) REFERENCES {$prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (feladvany_id) REFERENCES {$prefix}szozat_feladvanyok(feladvany_id) ON DELETE CASCADE
        ) $charset_collate;",

        // szozat_legalisszavak
        "CREATE TABLE {$prefix}szozat_legalisszavak (
            szo VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            KEY (szo)
        ) $charset_collate;"
    ];
}
