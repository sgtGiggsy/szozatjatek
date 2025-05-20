<?php
$start_time = microtime(true);
//? Alap includolások
include('./includes/config.inc.php');
include('./includes/functions.php');
include('./Classes/MySQLhandler.class.php');

define('ROOT_PATH', getenv('APP_ROOT_PATH'));
define('DEBUG_MODE', false);

$dbcallcount = 0;
$admin = $loginsuccess = $sajatolvas = $csoportolvas = $mindolvas = $sajatir = $csoportir = $mindir = false;
$szulonyit = $id = $userid = $felhasznaloid = $loginid = $activitylogid = $gyujtooldal = $selectedurl = null;
$pagetofind = "jatek";
$params = array();
//$querylist = array();

//? Session indítása, vagy folytatása
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params('604800');
	session_start();
}

//? Cache beállítása
//header('Cache-Control: no-cache');
//header('Pragma: no-cache');
header("Content-Encoding: compress");
header("Cache-Control: must-revalidate, private, max-age=31536000");
//header("Content-Security-Policy-Report-Only: script-src 'nonce-{RANDOM}' 'strict-dynamic';");

//? Címsorból vett GET értékek tisztítása nemkívánt karakterektől
foreach($_GET as $key => $value)
{
    if($key != "page" && $key != "subpage" && $key != "id")
        $params[$key] = $value;
    
    $value = trim($value);
    $value = strip_tags($value);
    $value = str_replace(array("\r\n", "\r", "\n", "'", "\"", "<", ">", ";", ":", "(", ")"), "", $value);
    $_GET[$key] = $value;
}

//? Alapvető $_GET és $_SESSION műveletek lebonyolítása, és kilépés
//? Ha nincs betölteni kívánt oldal, a főoldal marad betöltésre kiválasztva
if(isset($_GET['page']))
{
    $pagetofind = $_GET['page'];

	if($pagetofind == "kilep" && $_SESSION['id'] && $_SESSION['felhasznalonev'])
	{
        session_destroy();
		header("Location: $RootPath/index.php");
		die();
	}
}

//? Az lekérdezendő elem ID-jének begyüjtése
if(isset($_GET['id']))
    $id = $_GET['id'];

$_SESSION['id'] = 1;
//? Felhasználó beléptetése
if((!isset($_SESSION['id']) || !$_SESSION['id']) && isset($_POST['felhasznalonev']))
{
    $samaccountname = $_POST['felhasznalonev'];
    $plainpassword = $_POST['jelszo'];
    $hashedpassword = password_hash($plainpassword, PASSWORD_DEFAULT); // A jelszó hash-e az adatbázisban tároláshoz

    // LDAP-on keresztüli autentikációt elvégző rész

    // MySQL-en keresztüli autentikációt elvégző rész
    $login = new MySQLHandler('SELECT felhasznalo_id, jelszo, admin FROM szozat_felhasznalok WHERE felhasznalonev = ?', $samaccountname);
    $login->Bind($userid, $jelszo, $admin);

    // A lényegi bejelentkeztetést végző elágazás, csak akkor lépünk be ide, ha legalább az egyik módon van érvényes eredmény a felhasználónév-jelszó párosra
    //! Ha lesz jelszó, akkor a ||-t át kell írni &&-re
    if(isset($jelszo) || (password_verify($_POST['jelszo'], $jelszo)))
    {
        session_regenerate_id();
        if($login->sorokszama == 1) // Ez az egyedüli "Sikeres bejelentkezés" ág. Bármely más ágra fut ki a modul, a bejelentkezés sikertelen
        {
            $_SESSION['id'] = true;
            $_SESSION['admin'] = ($admin == 1) ? true : false;
            $loginsuccess = true;
            $_SESSION['badpasscount'] = 0;
        }
        
    }
    else // Nem létező felhasználó, vagy hibás jelszó esetén lefutó ág. Csak akkor kerülünk ide, ha egyik metódussal sem érkezett érvényes válasz a felhasználónév-jelszó párosra
    {
        $hiba = "Felhasználónév vagy jelszó nem megfelelő!";
    }

    if(isset($hiba) && $hiba)
    {
		$failed = new MySQLHandler('INSERT INTO failedlogins (felhasznalonev, ipcim) VALUES (?, ?)',
            $_POST['felhasznalonev'], $_SERVER['REMOTE_ADDR']);
        echo "<h2>$hiba</h2>";
        ?><script type='text/javascript'>alert('<?=$hiba?>')</script>
        <head><meta http-equiv="refresh" content="0; URL='./belepes'" /></head><?php
        $_SESSION['badpasscount']++;
        $_SESSION['lastbadpasstime'] = time();
        die;
    }
}

//? A menüpontok, valamint a felhasználó jogosultságainak, csoporttagságainak és személyes beálltásainak lekérése.
//? Innentől kezdve a $felhasznaloid változónak bejelentkezett felhasználó esetén léteznie KELL
//? A menüpontok lekérése is itt történik meg
if(isset($_SESSION['id']))
{
    $felhasznaloid = $_SESSION['id'];
    
}

//? Fallback megoldás arra az esetre, ha a lekérni próbált oldalhoz nincs adatbázis bejegyzés
if(!isset($currentpage))
{
    $selectedurl = $pagetofind;
    $currentpage['oldal'] = $pagetofind;
    $currentpage['cimszoveg'] = "Oldal";
    $currentpage['gyujtocimszoveg'] = "Oldal";
    $currentpage['aktiv'] = 3;
}

if(!$felhasznaloid)
{
    $selectedurl = "belepes";
    $currentpage['oldal'] = "belepes";
    $currentpage['cimszoveg'] = "Bejelentkezés";
    $currentpage['gyujtocimszoveg'] = "Bejelentkezés";
    $currentpage['aktiv'] = 1;
}

//? Szükség esetén 404-es hibaoldal generálása
try
{
    $page = @fopen("./{$selectedurl}.php", "r");
    if(!$page)
        throw new Exception();
}
catch(Exception $e)
{
    http_response_code(404);
    $selectedurl = "404";
    $currentpage['gyujtocimszoveg'] = "Oldal nem található!";
}

//? Folyamatértesítés
if(@$_GET['sikeres'] == "uj")
    $succesmessage = "Új " . $currentpage['cimszoveg'] . " hozzáadása sikeres";
elseif(@$_GET['sikeres'] == "szerkesztes")
    $succesmessage = "A(z) " . $currentpage['cimszoveg'] . " szerkesztése sikerült";
elseif(@$_GET['sikeres'] == "bejelentkezes")
    $succesmessage = "Sikeres bejelentkezés";

//? Oldal megjelenítése
include("./{$selectedurl}.php");

?>