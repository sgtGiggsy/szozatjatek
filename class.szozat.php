<?php
Class Szozat
{
//? Konstansok
    public static $orderby = array(
        'felhasznalo_id'    => 'u.id',
        'felhasznalonev'    => 'u.user_login',
        'bekuldottszavak'   => 'st.bekuldottszo',
        'sikeres'           => 'st.megoldott',
        'feladvanyok'       => 'st.feladvanyok',
        'sikerrata'         => 'st.sikerrata',
        'sorozathossz'      => 'ms.max_sorozat_hossz',
        'sikersorozat'      => 'ms.max_sikeres_sorozat'
    );

    private static $uzenet = [];

    public static function init_uzenetek() {
        self::$uzenet = array(
            200 => __('Sikeres beküldés!', 'szozat'),
            202 => __('Gratulálok, megoldottad a feladványt!', 'szozat'),
            204 => __('A feladvány megoldása sikertelen!', 'szozat'),
            403 => __('Nincs jogosultságod ehhez az oldalhoz!', 'szozat'),
            404 => __('A kért feladvány nem létezik!', 'szozat'),
            406 => __('Kérlek létező magyar szót adj meg!', 'szozat'),
            423 => __('A feladványt nem lehet újra kitölteni!', 'szozat'),
        );
    }

//? Plugin betöltése
    public static function init() {
        if (is_admin()) {
            add_action('admin_init', [self::class, 'handle_admin_post']);
        }

        add_action('init', [self::class, 'register_shortcodes']);
        add_action('init', [self::class, 'register_ajax_hooks']);
        add_action('init', [self::class, 'add_rewrite_rule']);
        add_action('widgets_init', [self::class, 'register_widgets']);
    }
    
//? Install, deaktivációs és eltávolítási hookok
    public static function plugin_activation() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once SZOZAT_PLUGIN_DIR . 'includes/db-schema.php';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();

        // Táblák létrehozása
        $schemas = szozat_get_table_schemas($prefix, $charset_collate);
        foreach ($schemas as $sql) {
            dbDelta($sql);
        }

        $wpdb->query("ALTER TABLE {$prefix}szozat_kitoltesek
            ADD FOREIGN KEY (felhasznalo_id) REFERENCES {$prefix}users(ID) ON DELETE CASCADE,
            ADD FOREIGN KEY (feladvany_id) REFERENCES {$prefix}szozat_feladvanyok(feladvany_id) ON DELETE CASCADE;");

        // Alapértelmezett szavak betöltése, ha még üres a tábla
        $table = "{$prefix}szozat_legalisszavak";
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if ($count === 0) {
            populate_legalisszavak();
        }
        //flush_rewrite_rules();
    }

    public static function plugin_deactivation() {
        flush_rewrite_rules();
    }

    public static function plugin_uninstall() {
        global $wpdb;
        $prefix = $wpdb->prefix;

        // Eltávolításkor minden tisztítás
        delete_option('szozat_settings');
        // Táblák törlése
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_kitoltesek");
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_legalisszavak");
        $wpdb->query("DROP TABLE IF EXISTS {$prefix}szozat_feladvanyok");
        flush_rewrite_rules();
    }

//? Alap működéshez szükséges metódusok
    public static function register_shortcodes($atts) {
        add_shortcode('szozat_jatek', [__CLASS__, 'render_game']);
    }

    public static function register_query_var($vars) {
        $vars[] = 'feladvany_id';
        return $vars;
    }

    public static function add_rewrite_rule() {
        add_rewrite_rule(
            '^szozat/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
            'index.php?pagename=szozat&feladvany_id=$matches[1]-$matches[2]-$matches[3]',
            'top'
        );
    }

//? Admin felület metódusai
    public static function admin_menu() {
        add_menu_page(
            __('Szózat beállítások', 'szozat'),     // Oldal címe
            __('Szózatjáték', 'szozat'),            // Menü szöveg
            'manage_options',                       // Jogosultság
            'szozat_settings',                      // Slug (URL-ben)
            ['Szozat', 'admin_page'],               // Callback (megjelenítő függvény)
            'dashicons-games',                      // Ikon
            80                                      // Pozíció
        );
    }

    public static function admin_page() {
        $feladvanyok = self::feladvanyok_listaja();
        include SZOZAT_PLUGIN_DIR . 'views/szoadmin-view.php';
    }

    public static function handle_admin_post() {
        if(isset($_POST['feladvany']))
        {
            self::post_feladvany();
        }
    }

    private static function post_feladvany() {
        global $wpdb;
        if (
            isset($_POST['szozat_nonce']) &&
            wp_verify_nonce($_POST['szozat_nonce'], 'szozat_admin_form')
        ) {
            $ujszo = trim(mb_strtoupper($_POST['feladvany']));

            $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}szozat_legalisszavak WHERE szo = %s", $ujszo);

            // Ha nincs találat az ismert szavak között, hiba dobása
            if((int) $wpdb->get_var($sql) > 0)
            {
                $wpdb->insert(
                    "{$wpdb->prefix}szozat_feladvanyok",
                    [
                        'feladvany_szoveg' => $ujszo,
                        'nap' => $_POST['nap'],
                        'egyszavas' => 1
                    ],
                    [
                        '%s',
                        '%s',
                        '%d'
                    ]
                );

                // Ha a feladvány létezik már, akkor hibaüzenet a felhasználó részére
                if ($wpdb->last_error) {
                    wp_redirect(admin_url('admin.php?page=szozat_settings&error=duplikalt_szo&szo=' . urlencode($ujszo)));
                    exit;
                }
                else
                {
                    // Ha sikeres, akkor átirányítjuk a beállítások oldalra
                    wp_redirect(admin_url('admin.php?page=szozat_settings&success=1'));
                    exit;
                }
            }
            else
            {
                wp_redirect(admin_url('admin.php?page=szozat_settings&error=ismeretlen_szo&szo=' . urlencode($ujszo)));
                exit;
            }
        }
    }

    public static function admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'szozat_settings') {
            return;
        }

        if(isset($_GET['error']) || isset($_GET['success']))
        {
            isset($_GET['error']) ? $class = 'error' : $class = 'success';
            echo '<div class="notice notice-' . $class . ' is-dismissible">';
            if(isset($_GET['error']))
            {
                switch ($_GET['error']) {
                    case "ismeretlen_szo":
                        /* translators: %s is a placeholder for the word the admin tried to add as a puzzle, but wasn't found in the db as a known word */
                        printf(
                            '<p><strong>%s</strong> %s</p>',
                            __('Hiba:', 'szozat'),
                            sprintf(
                                __('A(z) %s nem egy felismert magyar szó, ezért nem adható az adatbázishoz!', 'szozat'),
                                mb_strtoupper($_GET['szo'])
                            )
                        );
                        break;

                    case "duplikalt_szo":
                        /* translators: %s is a placeholder for the word the admin tried to add as a puzzle, but it was already among the list of previously added puzzles */
                        printf(
                            '<p><strong>%s</strong> %s</p>',
                            __('Hiba:', 'szozat'),
                            sprintf(
                                __('A(z) %s már szerepel a feladványok között!', 'szozat'),
                                mb_strtoupper($_GET['szo'])
                            )
                        );
                        break;
                }
            }

            if (isset($_GET['success'])) {
                printf(
                    '<p>%s</p>',
                    __('Feladvány sikeresen létrehozva.', 'szozat'))
                    ;
            }
            echo '</div>';
        }
    }

    private static function feladvanyok_listaja() {
        global $wpdb;
        $sql = "SELECT feladvany_id, feladvany_szoveg, egyszavas, nap FROM {$wpdb->prefix}szozat_feladvanyok ORDER BY feladvany_id DESC";
        return $wpdb->get_results($sql, ARRAY_A);
    }

//? Frontend metódusok
    public static function enqueue_assets($cssonly = false) {
        wp_enqueue_style('szozat-style');
        if(!$cssonly) {
            wp_enqueue_script('sweetalert2-js');
            wp_enqueue_style('sweetalert2-css');
            wp_enqueue_script('szozat-frontend');
            
            wp_localize_script('szozat-frontend', 'SzozatAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('szozat_nonce'),
            ]);
        }
    }

    public static function register_assets() {
        wp_register_script(
            'sweetalert2-js',
            plugin_dir_url(__FILE__) . 'includes/external/sweetalert2.all.min.js',
            [],
            '11.22.0',
            true
        );

        wp_register_style(
            'sweetalert2-css',
            plugin_dir_url(__FILE__) . 'includes/external/sweetalert2.min.css',
            [],
            '11.22.0'
        );
        
        wp_register_script(
            'szozat-frontend',
            plugin_dir_url(__FILE__) . 'includes/szozat-frontend.js',
            [],
            '1.0',
            true
        );

        wp_register_style(
            'szozat-style',
            plugin_dir_url(__FILE__) . 'includes/szozat-view.css',
            [],
            '1.0'
        );
    }

    public static function render_game() {
        // Bekérjük a feladványt
        $jelenszo = self::get_feladvany();
        if($jelenszo['sikeres'] === -1) {
            return '<h3>' . __('Hiba: A kért feladvány nem létezik!', 'szozat') . '</h3>';
        }
        elseif(!is_null($jelenszo['sikeres'])) {
            self::enqueue_assets(true);
            // Ha már lezárult a feladvány, nem lehet újra kitölteni
            echo '<h3>' . __('Ezt a feladványt már megoldottad korábban!', 'szozat') . '</h3>';
            $szemelyes = self::get_singleuser_stats();
            $valaszeloszlas = self::get_valaszeloszlas(get_current_user_id());
            include SZOZAT_PLUGIN_DIR . 'views/szemelyes-view.php';
            include SZOZAT_PLUGIN_DIR . 'views/eloszlas-view.php';
            return null;
        }
        else {
            self::enqueue_assets();
        }

        if(isset($jelenszo['valaszok'])) {
            $kitoltesfolytat = self::folytat_kitoltes($jelenszo['feladvany_szoveg'], $jelenszo['valaszok']);
            wp_localize_script('szozat-frontend', 'MentettKitoltes', $kitoltesfolytat);
        }

        $betuszam = mb_strlen($jelenszo['feladvany_szoveg']);
        ob_start();

        // Ha a views könyvtár a plugin gyökér alatt van, akkor így adod meg az útvonalat:
        include SZOZAT_PLUGIN_DIR . 'views/szozat-view.php';

        return ob_get_clean();
    }

//? Widget metódusok
    public static function register_widgets() {
        register_widget('Szozat_Widget');
    }

    public static function render_widget_stats() {
        $cache_key = 'szozat_stats_widget';
        $cached_output = get_transient($cache_key);

        if ($cached_output !== false) {
            return $cached_output;
        }

        // Példa lekérdezés — természetesen használd a saját statisztikádat
        $results = self::get_statistics();

        if (!$results) return '<p>' . __('Nincs statisztikai adat.', 'szozat') . '</p>';


        ob_start();
        include SZOZAT_PLUGIN_DIR . 'views/widget-view.php';
        $output = ob_get_clean();

        // Cache-eld az eredményt 10 percre (vagy amennyire kell)
        set_transient($cache_key, $output, 60 * MINUTE_IN_SECONDS);

        return $output;
    }

    static function invalidate_stats_widget_cache() {
        delete_transient('szozat_stats_widget');
    }

//? AJAX metódusok
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
        $inputszo = "";
        $betuszam = $kiserletszam = $sikeres = 0;

        // Bemenet string-gé alakítása
        foreach($_POST['jatekmezoinput'] as $value)
        {
            $inputszo .= mb_strtoupper($value);
        }

        $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}szozat_legalisszavak WHERE szo = %s", $inputszo);

        // Ha nincs találat az ismert szavak között, hiba dobása
        if((int) $wpdb->get_var($sql) == 0)
        {
            $retcode = 406;
        }
        else
        {
            $sql = $wpdb->prepare("SELECT feladvany_szoveg FROM {$wpdb->prefix}szozat_feladvanyok WHERE feladvany_id = %d", $feladvany_id);
            $jelenszo = mb_strtoupper($wpdb->get_var($sql));
            $betuszam = mb_strlen($jelenszo);

            $jelenkitoltes = self::auth_kitoltes($feladvany_id);

            if(!$jelenkitoltes)
            {
                // Mostanra léteznie kell kitöltésnek,
                // ha nincs, akkor nem létezik a feladvány, vagy a kitöltés
                $retcode = 404;
            }
            elseif(!is_null($jelenkitoltes['sikeres']))
            {
                // Ha a kiválasztott feladványhoz már létezik kitöltés, de lezárult (1-es, vagy 0-s),
                // nem engedjük az újrapróbálkozást
                $retcode = 423;
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
                    SET valaszok = %s, kiserletszam = %d
                    WHERE kitoltes_id = %d",
                    $jelenkitoltesvalaszok,
                    $kiserletszam,
                    $kitoltesid
                );
                $wpdb->query($sql);

                // A játék megoldásának kiértékelése
                $return = self::eredmeny_kiertekel($jelenszo, $_POST['jatekmezoinput']);

                if($return['valaszertek'] == $betuszam * 2)
                    $sikeres = 1;
                
                if($sikeres == 1 || $kiserletszam == 8)
                {
                    $sql = $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}szozat_kitoltesek
                        SET sikeres = %d
                        WHERE kitoltes_id = %d",
                        $sikeres,
                        $kitoltesid
                    );
                    $wpdb->query($sql);

                    // A widget cache-ét érvénytelenítjük, hogy a statisztika frissüljön
                    self::invalidate_stats_widget_cache();

                    if($sikeres)
                        $retcode = 202;
                    else
                        $retcode = 204;
                }
                else
                    $retcode = 200;
            }
        }

        wp_send_json_success([
            'retcode' => $retcode,
            'uzenet' => self::$uzenet[$retcode],
            'eredmeny' => $return['eredmeny']
        ]);
    }

//? Segéd metódusok
    public static function get_feladvany() {
        global $wpdb;
        $feladvany_id = get_query_var('feladvany_id');

        if(!$feladvany_id)
            $feladvany_id = date('Y-m-d'); // Ha nincs megadva, akkor az aktuális nap feladványa

        $sql = $wpdb->prepare(
            "SELECT feladvany_id, feladvany_szoveg, null AS sikeres FROM {$wpdb->prefix}szozat_feladvanyok WHERE nap = %s",
            $feladvany_id
        );

        $kivalasztott_feladvany = $wpdb->get_row($sql, ARRAY_A);

        // Ellenőrizzük, hogy van-e ilyen feladvány
        if($kivalasztott_feladvany === null) {
            return ['sikeres' => -1]; // Nincs ilyen feladvány
        }
        else
        {
            $jelenkitoltes = self::auth_kitoltes($kivalasztott_feladvany['feladvany_id']);
            if($jelenkitoltes)
            {
                if($jelenkitoltes['sikeres'] === null)
                {
                    // Ha a kiválasztott feladványhoz már létezik kitöltés, de még nem zárult le, akkor visszaadjuk a feladványt
                    update_user_meta(get_current_user_id(), 'szozat_feladvany_id', $kivalasztott_feladvany['feladvany_id']);

                    // A két asszociatív tömböt egyesítjük, hogy a kitöltés adatai is benne legyenek
                    // Mind a két tömbben szerepel a 'sikeres' kulcs, de ezesetben mindkettőben 'null'
                    return $kivalasztott_feladvany + $jelenkitoltes;
                }
                else
                {
                    // Ha már van kitöltés, de lezárult, akkor csak a korábbi eredményt adjuk vissza
                    return $jelenkitoltes;
                }
            }
            else
            {
                // Ha a feladványhoz még nem létezik kitöltés, akkor létrehozzuk
                $wpdb->insert(
                    "{$wpdb->prefix}szozat_kitoltesek",
                    [
                        'felhasznalo_id' => get_current_user_id(),
                        'feladvany_id' => $kivalasztott_feladvany['feladvany_id']
                    ],
                    [
                        '%d',
                        '%d'
                    ]
                );
                update_user_meta(get_current_user_id(), 'szozat_feladvany_id', $kivalasztott_feladvany['feladvany_id']);
                return $kivalasztott_feladvany;
            }
        }
    }

    public static function auth_kitoltes($feladvany_id) {
        global $wpdb;
        $sql = $wpdb->prepare(
                "SELECT kitoltes_id, valaszok, sikeres
                FROM {$wpdb->prefix}szozat_kitoltesek
                WHERE felhasznalo_id = %d
                    AND feladvany_id = %d
                ORDER BY kitoltes_id DESC
                LIMIT 1",
                get_current_user_id(),
                $feladvany_id
            );
        return $wpdb->get_row($sql, ARRAY_A);
    }

    public static function get_singleuser_stats($uid = null) {
        if(!$uid)
            $uid = get_current_user_id();
        return self::get_statistics('sikerrata', 'DESC', 1, 0, get_current_user_id())[0];
    }

    public static function get_statistics($rendez = 'sikerrata', $irany = 'DESC', $limit = 10, $offset = 0, $felhasznalo_id = null) {
        /*
        Visszaadott változók:
            - felhasznalo_id: A felhasználó azonosítója
            - display_name: A felhasználó megjelenített neve
            - bekuldottszo: Beküldött szavak száma
            - megoldott: Megoldott feladványok száma
            - feladvanyok: Összes feladványok száma
            - atlag_kiserlet: Átlagos kísérletek száma
            - sikerrata: Sikerességi arány (százalékban)
            - leghosszabb_sorozat: Leghosszabb sorozat hossza
            - sikeresorozat: Leghosszabb sikeres sorozat hossza
            - aktualis_sorozat: Az aktuális sorozat kezdete és vége
            - aktualis_sikersorozat: Az aktuális sikeres sorozat kezdete és vége
        */
        global $wpdb;
        $felhszur = '';
        if($felhasznalo_id)
            $felhszur = $wpdb->prepare('WHERE u.ID = %d', $felhasznalo_id);

        $sql = "WITH minden_nap AS (
                SELECT 
                    k.felhasznalo_id,
                    f.nap,
                    DATE_SUB(f.nap, INTERVAL ROW_NUMBER() OVER (
                        PARTITION BY k.felhasznalo_id ORDER BY f.nap
                    ) DAY) AS napcsoport
                FROM wp_szozat_kitoltesek k
                JOIN wp_szozat_feladvanyok f ON f.feladvany_id = k.feladvany_id
                GROUP BY k.felhasznalo_id, f.nap
            ),
            sikeres_nap AS (
                SELECT 
                    k.felhasznalo_id,
                    f.nap,
                    DATE_SUB(f.nap, INTERVAL ROW_NUMBER() OVER (
                        PARTITION BY k.felhasznalo_id ORDER BY f.nap
                    ) DAY) AS napcsoport
                FROM wp_szozat_kitoltesek k
                JOIN wp_szozat_feladvanyok f ON f.feladvany_id = k.feladvany_id
                WHERE k.sikeres = 1
                GROUP BY k.felhasznalo_id, f.nap
            ),
            osszes_sorozat AS (
                SELECT 
                    felhasznalo_id, 
                    napcsoport,
                    MIN(nap) AS sorozat_eleje,
                    MAX(nap) AS sorozat_vege,
                    COUNT(*) AS hossz
                FROM minden_nap
                GROUP BY felhasznalo_id, napcsoport
            ),
            sikeres_sorozat AS (
                SELECT 
                    felhasznalo_id, 
                    napcsoport,
                    MIN(nap) AS sorozat_eleje,
                    MAX(nap) AS sorozat_vege,
                    COUNT(*) AS hossz
                FROM sikeres_nap
                GROUP BY felhasznalo_id, napcsoport
            ),
            stat AS (
                SELECT 
                    k.felhasznalo_id,
                    SUM(k.kiserletszam) AS bekuldottszo,
                    SUM(k.sikeres) AS megoldott,
                    COUNT(*) AS feladvanyok,
                    ROUND(AVG(k.kiserletszam), 2) AS atlag_kiserlet,
                    (SUM(k.sikeres) / COUNT(*) * 100) AS sikerrata
                FROM wp_szozat_kitoltesek k
                GROUP BY k.felhasznalo_id
            ),
            max_sorozatok AS (
                SELECT 
                    o.felhasznalo_id,
                    MAX(o.hossz) AS max_sorozat_hossz
                FROM osszes_sorozat o
                GROUP BY o.felhasznalo_id
            ),
            max_sikeres_sorozatok AS (
                SELECT 
                    s.felhasznalo_id,
                    MAX(s.hossz) AS max_sikeres_sorozat
                FROM sikeres_sorozat s
                GROUP BY s.felhasznalo_id
            ),
            aktualis_napcsoport AS (
                SELECT 
                    felhasznalo_id,
                    MAX(napcsoport) AS aktualis_napcsoport
                FROM osszes_sorozat
                GROUP BY felhasznalo_id
            ),
            aktualis_sorozat AS (
                SELECT 
                    o.felhasznalo_id,
                    o.sorozat_eleje,
                    o.sorozat_vege,
                    o.hossz
                FROM osszes_sorozat o
                JOIN aktualis_napcsoport a 
                    ON o.felhasznalo_id = a.felhasznalo_id AND o.napcsoport = a.aktualis_napcsoport
            ),
            aktualis_sikeres_napcsoport AS (
                SELECT 
                    felhasznalo_id,
                    MAX(napcsoport) AS aktualis_sikeres_napcsoport
                FROM sikeres_sorozat
                GROUP BY felhasznalo_id
            ),
            aktualis_sikeres_sorozat AS (
                SELECT 
                    s.felhasznalo_id,
                    s.sorozat_eleje,
                    s.sorozat_vege,
                    s.hossz
                FROM sikeres_sorozat s
                JOIN aktualis_sikeres_napcsoport a 
                    ON s.felhasznalo_id = a.felhasznalo_id AND s.napcsoport = a.aktualis_sikeres_napcsoport
            )

            SELECT 
                u.ID AS felhasznalo_id,
                u.display_name,
                st.bekuldottszo,
                st.megoldott,
                st.feladvanyok,
                st.atlag_kiserlet,
                ROUND(st.sikerrata, 2) AS sikerrata,
                COALESCE(ms.max_sorozat_hossz, 0) AS leghosszabb_sorozat,
                COALESCE(mss.max_sikeres_sorozat, 0) AS leghosszabb_sikersorozat,
                aa.hossz AS aktualis_sorozat,
                sa.hossz AS aktualis_sikersorozat
            FROM wp_users u
                LEFT JOIN stat st ON u.ID = st.felhasznalo_id
                LEFT JOIN max_sorozatok ms ON u.ID = ms.felhasznalo_id
                LEFT JOIN max_sikeres_sorozatok mss ON u.ID = mss.felhasznalo_id
                LEFT JOIN aktualis_sorozat aa ON u.ID = aa.felhasznalo_id
                LEFT JOIN aktualis_sikeres_sorozat sa ON u.ID = sa.felhasznalo_id
            $felhszur
            ORDER BY " . self::$orderby[$rendez] . " $irany
            LIMIT $limit OFFSET $offset;";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function get_valaszeloszlas($felhasznalo = null, $feladvany = null) {
        global $wpdb;
        $where = null;
        $sikertelen = "WHERE ";

        // Alapértelmezetten nincs WHERE feltétel, és csak egy feltétellel lehet szűrni,
        // mert nincs értelme mind a két feltételt egyszerre használni
        if($felhasznalo)
            $where = $wpdb->prepare('WHERE felhasznalo_id = %d', $felhasznalo);
        elseif($feladvany)
            $where = $wpdb->prepare('WHERE feladvany_id = %d', $feladvany);

        if($where)
            $sikertelen = "AND ";

        $sql = "SELECT kiserletszam, count(*) AS darab
            FROM wp_szozat_kitoltesek
            $where $sikertelen sikeres != 0
            GROUP BY kiserletszam
        UNION
            SELECT 9 AS kiserletszam, count(*) AS darab
            FROM wp_szozat_kitoltesek
            $where $sikertelen sikeres = 0;";

        return array_column($wpdb->get_results($sql, ARRAY_N), 1, 0);
    }

    public static function eredmeny_kiertekel($feladvany, $betuarray) {
        $index = $valaszertek = 0;
        $megtalalt = "";
        $return = array();
        // Első pass, helyes betű a helyes helyen
        foreach($betuarray as $value)
        {
            $value = mb_strtoupper($value);
            $karakter = mb_substr($feladvany, $index, 1, 'UTF-8');

            if($karakter == $value)
            {
                $return[$index] = 2;
                $megtalalt .= $value;
                $valaszertek += 2;
            }
            $index++;
        }
        $index = 0;

        // Második pass, helyes betű a helytelen helyen
        foreach($betuarray as $value)
        {
            $value = mb_strtoupper($value);
            $karakter = mb_substr($feladvany, $index, 1, 'UTF-8');
            if($value != $karakter)
            {
                if(str_contains($feladvany, $value)
                    && substr_count($feladvany, $value) != substr_count($megtalalt, $value))
                {
                    $return[$index] = 1;
                    $megtalalt .= $value;
                }
                else
                {
                    $return[$index] = 0;
                }
            }
            $index++;
        }

        // Visszaadás előtt rendezni kell, hogy a kulcsok sorrendje ne legyen véletlenszerű
        ksort($return);

        return array(
            'valaszertek' => $valaszertek,
            'eredmeny' => $return
        );
    }

    private static function folytat_kitoltes($feladvany, $korabbivalaszok) {
        $return = array();
        $valaszok = json_decode($korabbivalaszok, true);
        foreach($valaszok as $valasz)
        {
            $eredmeny = self::eredmeny_kiertekel($feladvany, $valasz)['eredmeny'];
            $return[] = array(
                'valasz' => $valasz,
                'eredmeny' => $eredmeny
            );
        }

        return $return;
    }
}