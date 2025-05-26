<?php

Class Szozat
{
    public static function plugin_activation() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once plugin_dir_path(__FILE__) . 'includes/db-schema.php';

        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();

        // Táblák létrehozása
        $schemas = szozat_get_table_schemas($prefix, $charset_collate);
        foreach ($schemas as $sql) {
            dbDelta($sql);
        }

        // Alapértelmezett szavak betöltése, ha még üres a tábla
        $table = "{$prefix}szozat_legalisszavak";
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if ($count === 0) {
            $szavak = self::betolto_szavak_fajlbol();
            foreach ($szavak as $szo) {
                $wpdb->insert($table, ['szo' => $szo], ['%s']);
            }
        }
    }

    public static function plugin_deactivation()
    {
        //flush_rewrite_rules();
    }

    public static function plugin_uninstall() {
        global $wpdb;
        $prefix = $wpdb->prefix;

        // Eltávolításkor minden tisztítás
        delete_option('szozat_settings');
        // Táblák törlése
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_feladvanyok");
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_kitoltesek");
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_legalisszavak");
        // Ha saját adatbázistábla lenne: DROP TABLE stb.
    }

    public static function betolto_szavak_fajlbol() {
        $file = plugin_dir_path(__FILE__) . 'includes/szavak.txt';

        if (!file_exists($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $szavak = [];

        foreach ($lines as $szo) {
            $szo = trim($szo);
            if (!empty($szo)) {
                $szavak[] = mb_strtoupper($szo);
            }
        }

        return $szavak;
    }

    public static function register_shortcodes() {
        add_shortcode('szozat_jatek', [__CLASS__, 'render_game']);
    }

    public static function register_query_var($vars) {
        $vars[] = 'szozat_page';
        return $vars;
    }

    public static function render_game() {
        ob_start();

        // Ha a views könyvtár a plugin gyökér alatt van, akkor így adod meg az útvonalat:
        include SZOZAT_PLUGIN_DIR . 'views/szozat-view.php';

        return ob_get_clean();
    }

    public static function handle_request() {
        if (get_query_var('szozat_page')) {
            self::init(); // pl. session indítás
            include plugin_dir_path(__FILE__) . 'views/szozat-view.php';
            exit;
        }
    }

    public static function init() {
        if (!session_id()) {
            session_start();
        }
    }

    public static function admin_menu() {
        add_menu_page(
            'Szozat Beállítások',       // Oldal címe (title)
            'Szozat',                   // Menü szöveg
            'manage_options',           // Jogosultság
            'szozat_settings',          // Slug (URL-ben)
            ['Szozat', 'admin_page'],   // Callback (megjelenítő függvény)
            'dashicons-games',          // Ikon
            80                          // Pozíció
        );
    }

    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>Szozat Beállítások</h1>
            <p>Itt jön majd a játék beállításainak admin felülete.</p>
            <!-- Itt később beilleszthető form, opciók mentése stb. -->
        </div>
        <?php
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'szozat-style',
            plugin_dir_url(__FILE__) . 'includes/szozat-view.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'szozat-frontend',
            plugins_url('includes/szozat-frontend.js', __FILE__),
            [],
            '1.0',
            true
        );

        wp_localize_script('szozat-frontend', 'SzozatAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('szozat_nonce'),
        ]);
    }

    public static function register_ajax_hooks() {
        add_action('wp_ajax_szozat_megoldas', [__CLASS__, 'handle_megoldas']);
        add_action('wp_ajax_nopriv_szozat_megoldas', [__CLASS__, 'handle_megoldas']);
    }

    public static function handle_megoldas() {
        global $wpdb;
        $feladvany_id = get_user_meta(get_current_user_id(), 'szozat_feladvany_id', true);
        // Biztonsági ellenőrzések lefuttatása
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'szozat_nonce')) {
            wp_send_json_error(['error' => 'Jogosulatlan kérés']);
            wp_die();
        }

        // A játék megoldásának feldolgozása
        $ret = array();
        $megtalalt = $inputszo = "";
        $index = $betuszam = $valaszertek = $kiserletszam = $sikeres = 0;

        // Bemenet string-gé alakítása
        foreach($_POST['jatekmezoinput'] as $value)
        {
            $inputszo .= mb_strtoupper($value);
        }

        $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}szozat_legalisszavak WHERE szo = %s", $inputszo);

        // Ha nincs találat az ismert szavak között, hiba dobása
        if((int) $wpdb->get_var($sql) != 1)
        {
            $retcode = 406;
        }
        else
        {
            $sql = $wpdb->prepare("SELECT feladvany_szoveg FROM {$wpdb->prefix}szozat_feladvanyok WHERE feladvany_id = %d", $feladvany_id);
            $jelenszo = mb_strtoupper($wpdb->get_var($sql));
            $betuszam = mb_strlen($jelenszo);

            $sql = $wpdb->prepare(
                "SELECT kitoltes_id, valaszok
                FROM {$wpdb->prefix}szozat_kitoltesek
                WHERE felhasznalo_id = %d
                    AND feladvany_id = %d
                    AND sikeres IS NULL
                ORDER BY kitoltes_id DESC
                LIMIT 1",
                get_current_user_id(),
                $feladvany_id
            );

            $jelenkitoltes = $wpdb->get_row($sql, ARRAY_A);

            if(!$jelenkitoltes)
            {
                //TODO: Esetleg igény lehet ennél bővebb hibakezelésre
                $retcode = 204;
            }
            else
            {
                $kitoltesid = $jelenkitoltes['kitoltes_id'];
                if($jelenkitoltes['valaszok'])
                    $jelenkitoltesvalaszok = json_decode($jelenkitoltes['valaszok']);
                else
                    $jelenkitoltesvalaszok = array();

                $jelenkitoltesvalaszok[] = $_POST['jatekmezoinput'];
                $kiserletszam = count($jelenkitoltesvalaszok);
                $jelenkitoltesvalaszok = json_encode($jelenkitoltesvalaszok, JSON_UNESCAPED_UNICODE);
                $sql = $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}szozat_kitoltesek
                    SET valaszok = %s
                    WHERE kitoltes_id = %d",
                    $jelenkitoltesvalaszok,
                    $kitoltesid
                );

                $wpdb->query($sql);

                // Első pass, helyes betű a helyes helyen
                foreach($_POST['jatekmezoinput'] as $value)
                {
                    $value = mb_strtoupper($value);
                    $karakter = mb_substr($jelenszo, $index, 1, 'UTF-8');

                    if($karakter == $value)
                    {
                        $ret[$index] = 2;
                        $megtalalt .= $value;
                        $valaszertek += 2;
                    }
                    $index++;
                }
                $index = 0;

                // Második pass, helyes betű a helytelen helyen
                foreach($_POST['jatekmezoinput'] as $value)
                {
                    $value = mb_strtoupper($value);
                    $karakter = mb_substr($jelenszo, $index, 1, 'UTF-8');
                    if($value != $karakter)
                    {
                        if(str_contains($jelenszo, $value)
                            && substr_count($jelenszo, $value) != substr_count($megtalalt, $value))
                        {
                            $ret[$index] = 1;
                            $megtalalt .= $value;
                        }
                        else
                        {
                            $ret[$index] = 0;
                        }
                    }
                    $index++;
                }

                if($valaszertek == $betuszam * 2)
                    $sikeres = 1;
                
                if($valaszertek == $betuszam * 2 || $kiserletszam == 8)
                {
                    $sql = $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}szozat_kitoltesek
                        SET sikeres = %d, kiserletszam = %d
                        WHERE kitoltes_id = %d",
                        $sikeres, $kiserletszam,
                        $kitoltesid
                    );

                    $wpdb->query($sql);


                    if($sikeres)
                        $retcode = 202;
                    else
                        $retcode = 204;
                }
                else
                    $retcode = 200;
                // Kulcs szerint rendezés, mert a JSON nem garantálja a kulcsok sorrendjét
                ksort($ret);
            }
        }

        switch($retcode)
        {
            case 200:
                $uzenet = "Sikeres beküldés!";
                break;
            case 202:
                $uzenet = "Gratulálok, megoldottad a feladványt!";
                break;
            case 204:
                $uzenet = "A feladvány megoldása sikertelen!";
                break;
            case 403:
                $uzenet = "Nincs jogosultságod ehhez az oldalhoz!";
                break;
            case 406:
                $uzenet = "Kérlek létező magyar szót adj meg!";
                break;
            default:
                http_response_code(500);
        }

        wp_send_json_success([
            'retcode' => $retcode,
            'uzenet' => $uzenet,
            'eredmeny' => $ret
        ]);
    }

}