<?php
header('Content-Type: application/json');
$felhasznaloid = @$_SESSION['id'];
$start = microtime(true);
//$felhasznaloid = 1;
if($felhasznaloid)
{
    $ret = array();
    $megtalalt = $inputszo = "";
    $index = $betuszam = $valaszertek = $kiserletszam = $sikeres = 0;

    foreach($_POST['jatekmezoinput'] as $key => $value)
    {
        $inputszo .= mb_strtoupper($value);
    }

    $legalszo = new MySQLHandler('SELECT szo FROM szozat_legalisszavak WHERE szo = ? LIMIT 1', $inputszo);
    if($legalszo->sorokszama != 1)
    {
        $retcode = 406;
    }
    else
    {
        $jelenszo = new MySQLHandler('SELECT feladvany_szoveg FROM szozat_feladvanyok WHERE feladvany_id = ?', $_SESSION['feladvany_id']);
        $jelenszo = mb_strtoupper($jelenszo->Fetch()['feladvany_szoveg']);
        $betuszam = mb_strlen($jelenszo);

        $jelenkitoltes = new MySQLHandler('SELECT kitoltes_id, valaszok FROM szozat_kitoltesek WHERE felhasznalo_id = ? AND feladvany_id = ? AND sikeres IS NULL ORDER BY kitoltes_id DESC LIMIT 1', $felhasznaloid, $_SESSION['feladvany_id']);
        if($jelenkitoltes->sorokszama != 1)
        {
            //TODO: Esetleg igény lehet ennél bővebb hibakezelésre
            $retcode = 204;
        }
        else
        {
            $jelenkitoltes = $jelenkitoltes->Fetch();
            $kitoltesid = $jelenkitoltes['kitoltes_id'];
            if($jelenkitoltes['valaszok'])
                $jelenkitoltesvalaszok = json_decode($jelenkitoltes['valaszok']);
            else
                $jelenkitoltesvalaszok = array();
            $jelenkitoltesvalaszok[] = $_POST['jatekmezoinput'];
            $kiserletszam = count($jelenkitoltesvalaszok);
            $jelenkitoltesvalaszok = json_encode($jelenkitoltesvalaszok, JSON_UNESCAPED_UNICODE);
            $kitoltes = new MySQLHandler('UPDATE szozat_kitoltesek SET valaszok = ? WHERE kitoltes_id = ?', $jelenkitoltesvalaszok, $kitoltesid);

            // Első pass, helyes betű a helyes helyen
            foreach($_POST['jatekmezoinput'] as $key => $value)
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
            foreach($_POST['jatekmezoinput'] as $key => $value)
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
                $kitoltes = new MySQLHandler('UPDATE szozat_kitoltesek SET sikeres=?, kiserletszam=? WHERE kitoltes_id = ?', $sikeres, $kiserletszam, $kitoltesid);
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
}
else
{
    http_response_code(403);
    $retcode = 403;
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

$time = round(microtime(true) - $start, 2);
$return = array(
    'retcode' => $retcode,
    'uzenet' => $uzenet,
    'eredmeny' => $ret,
    'valaszido' => $time
);

echo json_encode($return);