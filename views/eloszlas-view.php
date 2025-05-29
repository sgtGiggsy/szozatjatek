<h3>Megfejtések eloszlása</h3><?php 
$legnagyobb = max($valaszeloszlas);

for($i = 1; $i < 10; $i++)
{
    $szam = 0;
    if(isset($valaszeloszlas[$i]))
        $szam = $valaszeloszlas[$i];
    $szazalek = round(($szam / $legnagyobb) * 100, 2) . "%";

    ?><div class="bar-container">
        <div class="bar" style="width: <?=$szazalek?>;<?=($i == 9) ? ' background-color: var(--hianyzik)' : '' ?>">
            <?=($szam && $i < 9) ? $i . " " . __('próbálkozás', 'szozat') : (($i == 9) ? __('Megfejtés nem sikerült', 'szozat') : '') ?>
        </div>
    </div><?php
}