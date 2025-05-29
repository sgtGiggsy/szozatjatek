<h3>Eddigi statiszikáid</h3>
<div id="jatekinfo">
    <div id="jatekinfo_1"><?=__('Játékos', 'szozat') . ":"?> <span id="jatekosnev"><?=$szemelyes['display_name']?></span></div>
    <div id="jatekinfo_2"><?=__('Megoldott feladványok száma', 'szozat') . ":"?> <span id="szoveg"><?=$szemelyes['megoldott']?></span></div>
    <div id="jatekinfo_3"><?=__('Összes játék száma', 'szozat') . ":"?> <span id="helyesvalaszokszama"><?=$szemelyes['feladvanyok']?></span></div>
    <div id="jatekinfo_4"><?=__('Átlag kísérletszám játékonként', 'szozat') . ":"?> <span id="hibasvalaszokszama"><?=$szemelyes['atlag_kiserlet']?></span></div>
    <div id="jatekinfo_5"><?=__('Sikeres játékok aránya', 'szozat') . ":"?> <span id="jatekallapot"><?=$szemelyes['sikerrata']?></span></div>
    <div id="jatekinfo_6"><?=__('Leghosszabb sorozat', 'szozat') . ":"?> <span id="jatektartam"><?=$szemelyes['leghosszabb_sorozat']?></span></div>
    <div id="jatekinfo_7"><?=__('Leghosszabb sikeres sorozat', 'szozat') . ":"?> <span id="jatektartam"><?=$szemelyes['leghosszabb_sikersorozat']?></span></div>
    <div id="jatekinfo_6"><?=__('Jelenlegi sorozat', 'szozat') . ":"?> <span id="jatektartam"><?=$szemelyes['aktualis_sorozat']?></span></div>
    <div id="jatekinfo_7"><?=__('Jelenlegi sikeres sorozat', 'szozat') . ":"?> <span id="jatektartam"><?=$szemelyes['aktualis_sikersorozat']?></span></div>
</div>