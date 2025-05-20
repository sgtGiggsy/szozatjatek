<?php

function csvToArray($csv, $fejleccel = true)
{
	$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
	$bemenet = file($csv);
	$bomnelkul = str_replace($bom, '', $bemenet); // Arra az esetre, ha a fájl rendelkezne BOM-mal

	$rows = array_map(function($row) { return str_getcsv($row, ';'); }, $bomnelkul);

	if($fejleccel)
	{
		$fejlec = array_shift($rows);
	
		$array = array();
		foreach($rows as $row)
		{
			$array[] = array_combine($fejlec, $row);
		}
	}
	else
	{
		$array = $rows;
	}

	return $array;
}

function trimCimke($cimke)
{
	$totrim = array("\"", "*");

	return str_replace($totrim, "", $cimke);
}

function timeStampToDate($timestamp)
{
	if($timestamp)
	{
		return date('Y M j.', strtotime($timestamp));
	}
	else
	{
		return null;
	}
}

function timeStampToDateTimeLocal($timestamp)
{
    if($timestamp)
	{
		return str_replace(" ", "T", $timestamp);
	}
	else
	{
		return null;
	}
}

function dateTimeLocalToTimeStamp($datetimelocal)
{
	if($datetimelocal)
	{
		return str_replace("T", " ", $datetimelocal);
	}
	else
	{
		return null;
	}
}

function thisDate()
{
	return date('Y-m-d');
}

function timeStampForSQL($timestamp = null)
{
	return date('Y-m-d H:i:s', $timestamp);
}


function cancelForm()
{	
	?><div class="submit">
		<button type="button" onclick="location.href='<?=$GLOBALS['backtosender']?>'">Mégsem</button>
   </div><?php
}

function mysqliNaturalSort($mysqliresult, $sortcriteria)
{
	$returnarr = mysqliToArray($mysqliresult);
	return arrayNaturalSort($returnarr, $sortcriteria);
}

function arrayNaturalSort($returnarr, $sortcriteria)
{
	usort($returnarr, function($a, $b) use ($sortcriteria) {
		if($a[$sortcriteria] == null)
		{
			$a[$sortcriteria] = "zzzzz";
		}

		if($b[$sortcriteria] == null)
		{
			$b[$sortcriteria] = "zzzzz";
		}

        return strnatcmp($a[$sortcriteria], $b[$sortcriteria]); //Case sensitive
        //return strnatcasecmp($a['manager'],$b['manager']); //Case insensitive
    });

	return $returnarr;
}


function nevToLink($nev)
{
	setlocale(LC_CTYPE, 'hu_HU');
	$charstoremove = array(":", ",", ".", "\"", "'", "(", ")");
	$charstoreplace = array(" ", ".", "_");
	$link = strtolower(str_replace($charstoremove, "", str_replace($charstoreplace, "-", iconv('utf-8', 'ascii//TRANSLIT', $nev))));
	$link = str_replace("--", "-", $link);
	$link = rtrim($link ,"-");
	return $link;
}

function getPermissionError()
{
    http_response_code(403);
	?><h1>403</h1>
	<strong>A kért művelet nem engedélyezett!</strong><?php
}


function redirectToGyujto($gyujtonev)
{
	$eredmeny = null;
	if($_GET['action'] == "new")
	{
		$eredmeny = "?sikeres=uj";
	}
	elseif($_GET['action'] == "update")
	{
		$eredmeny = "?sikeres=szerkesztes";
	}
	header("Location: ./" . $gyujtonev . $eredmeny);
}

function quickXSSfilter($string)
{
	$string = str_replace("<", "&lt;", $string);
	$string = str_replace(">", "&gt;", $string);
	$string = str_replace("{", "&#123;", $string);
	$string = str_replace("}", "&#125;", $string);
	$string = str_replace("$", "&#36;", $string);
	$string = str_replace("(", "&#40;", $string);
	$string = str_replace(")", "&#41;", $string);
	return $string;
}

function revertXSSfilter($string)
{
	$string = str_replace("&lt;", "<", $string);
	$string = str_replace("&gt;", ">", $string);
	$string = str_replace("&#123;", "{", $string);
	$string = str_replace("&#125;", "}", $string);
	$string = str_replace("&#36;", "$", $string);
	$string = str_replace("&#40;", "(", $string);
	$string = str_replace("&#41;", ")", $string);
	return $string;
}

function purifyPost($ishtml = false)
{
	$activitylogid = $GLOBALS['activitylogid'];
	foreach($_POST as $key => $value)
    {
        if(is_array($value))
		{
			foreach($value as $key2 => $value2)
			{
				if(is_array($value2))
				{
					foreach($value2 as $key3 => $value3)
					{
						if ($value3 == "NULL" || $value3 == "")
						{
							$_POST[$key][$key2][$key3] = NULL;
						}
						elseif(!$ishtml)
						{
							$_POST[$key][$key2][$key3] = quickXSSfilter($value3);
						}
					}
				}
				else
				{
					if ($value2 == "NULL" || $value2 == "")
					{
						$_POST[$key][$key2] = NULL;
					}
					elseif(!$ishtml)
					{
						$_POST[$key][$key2] = quickXSSfilter($value2);
					}
				}
			}
		}
		else
		{
			if ($value == "NULL" || $value == "")
			{
				//echo "null";
				$_POST[$key] = NULL;
			}
			elseif(!$ishtml)
			{
				$_POST[$key] = quickXSSfilter($value);
			}
		}
	}
}

function purifyArray($array)
{
	foreach($array as $key => $value)
    {
        if ($value == "NULL" || $value == "")
        {
            $array[$key] = NULL;
        }
		else
        {
            $array[$key] = quickXSSfilter($value);
        }
    }

	return $array;
}



function verifyWholeNum($szam)
{
	if(is_int($szam))
	{
		return true;
	}
	elseif(ctype_digit($szam))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function arrayMultiDimension($array)
{
   rsort($array);
   return isset($array[0]) && is_array($array[0]);
}

function fajlFeltoltes($fajlok, $filetypes, $mediatype, $gyokermappa, $egyedimappa, $csakurl = false)
{
	$feltoltdb = new MySQLHandler();
	$feltoltdb->KeepAlive();
	$feltoltesimappa = "$gyokermappa/$egyedimappa/";
	$feltoltottfajlok = array();
    $uploadids = array();
	$single = false;

	if(arrayMultiDimension($fajlok))
	{
		$db = count($fajlok['name']);
	}
	else
	{
		$single = true;
		$db = 1;
	}

	for($i = 0; $i < $db; $i++)
	{
		if($single)
		{
			$fajlok['name'] = array($fajlok['name']);
			$fajlok['type'] = array($fajlok['type']);
			$fajlok['tmp_name'] = array($fajlok['tmp_name']);
		}

		if (!in_array($fajlok['type'][$i], $mediatype))
		{
			$uzenet = "A fájl típusa nem megengedett: " . $fajlok['name'][$i] . " A feltöltött fájl típusa: " . $fajlok['type'][$i];
		}
		else
		{
			if(!file_exists($feltoltesimappa))
			{
				mkdir($feltoltesimappa, 0777, true);
			}

			$fajlnev = strtolower(str_replace(".", time() . ".", $fajlok['name'][$i]));
			$finalfile = $feltoltesimappa . $fajlnev;
			if(file_exists($finalfile))
			{
				$uzenet = "A feltölteni kívánt fájl már létezik: " . $fajlnev;
			}
			else
			{
				move_uploaded_file($fajlok['tmp_name'][$i], $finalfile);
				$uzenet = 'A fájl feltöltése sikeresen megtörtént: ' . $fajlnev;
				$feltoltottfajlok[] = "$egyedimappa/" . "$fajlnev";
			}
		}
	}

	if(count($feltoltottfajlok) > 0)
	{
		$feltoltdb->Prepare("INSERT INTO feltoltesek (fajl, felhasznalo) VALUES (?, ?)");
		foreach($feltoltottfajlok as $fajl)
		{
			$feltoltdb->Run($fajl, $_SESSION['id']);
			$uploadids[] = $feltoltdb->last_insert_id;
		}
	}

	if($csakurl)
	{
		$uploadids = $feltoltottfajlok;
	}

	//echo $uzenet;

	return $uploadids;
}

function roundUp99($value)
{
	$tizedes = $value - floor($value);
	if($tizedes > 0.95)
	{
		return ceil($value);
	}
	else
	{
		return $value;
	}
}

function secondsToFullFormat($seconds, $showseconds = true)
{
	if($seconds < 0)
		$seconds = $seconds * - 1;
	$nap = $ev = null;
	$masodperc = str_pad(($seconds % 60), 2, "0", STR_PAD_LEFT);
	$perc = str_pad(($seconds / 60 % 60), 2, "0", STR_PAD_LEFT);
	$ora = str_pad((floor($seconds / 3600 % 24)), 2, "0", STR_PAD_LEFT);
	$ora = "$ora óra, ";
	$napok = floor($seconds / 3600 / 24);
	$evek = floor($napok / 365);
	$napok = $napok - ($evek * 365);

	if($napok > 0)
	{
		$nap = "$napok nap, ";
	}

	if($evek > 0)
	{
		$ev = "$evek év, ";
	}

	if($showseconds)
	{
		$masodperc = ", $masodperc másodperc";
	}
	else
	{
		$masodperc = "";
	}

	return $ev . $nap . $ora . "$perc perc$masodperc";
}

function FormatSQL($sql)
{
	$sql = str_replace("INNER", "<br>&nbsp&nbsp\n&nbsp&nbspINNER", $sql);
	$sql = str_replace("LEFT", "<br>&nbsp&nbsp\n&nbsp&nbspLEFT", $sql);
	$sql = str_replace("FROM", "<br>&nbsp&nbspFROM", $sql);
	$sql = str_replace("WHERE", "<br>&nbsp&nbspWHERE", $sql);
	$sql = str_replace("ORDER", "<br>&nbsp&nbspORDER", $sql);
	$sql = str_replace("GROUP", "<br>&nbsp&nbspGROUP", $sql);
	$sql = str_replace("(SELECT", "<br>&nbsp&nbsp(SELECT", $sql);
	$sql = str_replace("UNION", "<br>UNION<br>", $sql);
	return $sql;
}

function IntRagValaszt($szam)
{
    if($szam == 0)
    {
        return $szam . "-s";
    }
	elseif(!is_numeric($szam))
	{
		return $szam;
	}
    else
    {
        switch($szam % 10)
        {
            case 1: case 2: case 4: case 7: case 9:
                return $szam . "-es";
                break;
            case 3: case 8:
                return $szam . "-as";
                break;
            case 5:
                return $szam . "-ös";
                break;
            case 6:
                return $szam . "-os";
                break;
            case 0:
                switch($szam / 10)
                {
                    case 1: case 4: case 5: case 7: case 9:
                        return $szam . "-es";
                        break;
                    case 2: case 3: case 6: case 8: default:
                        return $szam . "-as";
                        break;
                }
        }
    }
}

function concatToAssocArray($fields, ...$concats)
{
	if(count($fields) != count($concats))
	{
		echo "A mezőnevek és értékmezők száma nem egyezik!";
		return false;
	}

	if(!$concats[0])
	{
		return array();
	}

	$duplicatedarray = array();
	$assoc = array();

	$mezoindex = 0;

	foreach($concats as $concat)
	{
		$temparr = explode(",;,", $concat);

		$elemszam = count($temparr);

		for($i = 0; $i < $elemszam; $i++)
		{
			$duplicatedarray[$i][$fields[$mezoindex]] = $temparr[$i];
		}
		$mezoindex++;
	}

	foreach($duplicatedarray as $duplicate)
	{
		if(!in_array($duplicate, $assoc))
		{
			$assoc[] = $duplicate;
		}
	}

	return $assoc;
}

function str_contains_any($haystack, $needles): bool
{
	return array_reduce($needles, fn($a, $n) => $a || str_contains($haystack, $n), false);
}

function fajlnevFromPath($path)
{
	$ut = explode("/", $path);
	return end($ut);
}

function isVerifiedToWrite($querystring, $needle, $haystack, $params = array())
{
	$verify = new mySQLHandler();
	
	if(!defined('MINDIR') || !MINDIR)
	{
		if(defined('CSOPORTIR') && CSOPORTIR)
		{
			if(isset($_POST['id']) || isset($_GET['id']))
			{
				$f_id = (isset($_POST['id'])) ? $_POST['id'] : $_GET['id'];
			
				$verify->Query($querystring, $f_id, ...$params);
				if($verify->Fetch()[$haystack] == $needle)
				{
					$irhat = true;
				}
				else
				{
					$irhat = false;
				}
			}
			else
			{
				$irhat = true;
			}
		}
		else
		{
			$irhat = false;
		}
	}
	else
	{
		$irhat = true;
	}

	return $irhat;
}

function compareObjects($obj1, $obj2)
{
	$vars1 = get_object_vars($obj1);
	$vars2 = get_object_vars($obj2);

	$elteresek = array();

	foreach($vars1 as $key => $value)
	{
		if(!array_key_exists($key, $vars2) || $vars1[$key] !== $vars2[$key])
		{
			$elteresek[$key] = $key;
		}
	}

	return $elteresek;
}