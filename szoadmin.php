<?php
$hiba = null;
include_once("./templates/header.tpl.php");
if(!(isset($_SESSION['admin']) && $_SESSION['admin']))
{
    http_response_code(403);
    echo "<h2>Nincs jogosultságod ehhez az oldalhoz!</h2>";
}
else
{
    if(isset($_POST['feladvany']))
    {
        $ujszo = mb_strtoupper($_POST['feladvany']);
        $legalszo = new MySQLHandler('SELECT szo FROM szozat_legalisszavak WHERE szo = ? LIMIT 1', $ujszo);
        if($legalszo->sorokszama == 1)
        {
            $feladvany = new MySQLHandler('INSERT INTO szozat_feladvanyok (feladvany_szoveg, egyszavas) VALUES (?, ?)', $ujszo, 1);
            header("Location: ./szoadmin");
            die;
        }
        else
        {
            $hiba = "A(z) $ujszo nem felismert magyar szó, ezért nem adható az adatbázishoz!";
        }
    }

    $feladvanyok = new MySQLHandler('SELECT feladvany_szoveg, egyszavas, datum FROM szozat_feladvanyok ORDER BY feladvany_id DESC;');
    $feladvanyok = $feladvanyok->Result();

    echo "<h2>Feladványok</h2>";
    echo "<table>";
    echo "<tr><th>Hozzáadva</th><th>Feladvány</th><th>Egyszavas</th></tr>";
    foreach($feladvanyok as $feladvany)
    {
        echo "<tr>";
        echo "<td>" . $feladvany['datum'] . "</td>";
        echo "<td>" . $feladvany['feladvany_szoveg'] . "</td>";
        echo "<td>" . ($feladvany['egyszavas'] ? "Igen" : "Nem") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<h2>Feladvány hozzáadása</h2>";
    ?><form method="POST" action="szoadmin">
        <input type="text" name="feladvany" placeholder="Feladvány szövege" required />
        <input type="submit" value="Hozzáadás" /><?php
}
include_once("./templates/footer.tpl.php");
if($hiba)
{
    ?><script>
        setTimeout(() => {
            alert('<?=$hiba?>')
        }, 0);
    </script><?php
}