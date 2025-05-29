
<?php
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

?><div class="jatek" id="jatekter">
    <table id="jatektabla" data-betuszam='<?=$betuszam?>'><?php
        for($tr = 0; $tr < 8; $tr++)
        {
            ?><tr id='sorid-<?=$tr?>' ><?php
            for($td = 0; $td < $betuszam; $td++)
            {
                ?><td class='jatekmezok' id='jatekmezo_<?=$tr . "_" . $td?>'>
                    <input type='text' id='jatekmezoinput_<?=$tr . "_" . $td?>' class='jatekmezoinput' maxlength='1' onkeyup="jumpToNext('<?=$td?>')" onclick="setCursor('<?=$td?>')" inputmode="none" autocomplete="off" disabled/>
                </td><?php
            }
            ?></tr><?php
        }
    ?></table>
    <button id="beKuld" onclick="sendMegoldas()" disabled><?=__('Beküld', 'szozat') . ":"?></button>

    <div class="keyboard">
        <!-- Számok -->
        <div class="row">
            <div class="key" id="bill-1" onclick="billentyuLeut('1')">1</div>
            <div class="key" id="bill-2" onclick="billentyuLeut('2')">2</div>
            <div class="key" id="bill-3" onclick="billentyuLeut('3')">3</div>
            <div class="key" id="bill-4" onclick="billentyuLeut('4')">4</div>
            <div class="key" id="bill-5" onclick="billentyuLeut('5')">5</div>
            <div class="key" id="bill-6" onclick="billentyuLeut('6')">6</div>
            <div class="key" id="bill-7" onclick="billentyuLeut('7')">7</div>
            <div class="key" id="bill-8" onclick="billentyuLeut('8')">8</div>
            <div class="key" id="bill-9" onclick="billentyuLeut('9')">9</div>
            <div class="key" id="bill-0" onclick="billentyuLeut('0')">0</div>
        </div>
        
        <!-- ÉKEZETES sor -->
        <div class="row">
            <div class="key" id="bill-é" onclick="billentyuLeut('É')">É</div>
            <div class="key" id="bill-á" onclick="billentyuLeut('Á')">Á</div>
            <div class="key" id="bill-ó" onclick="billentyuLeut('Ó')">Ó</div>
            <div class="key" id="bill-ö" onclick="billentyuLeut('Ö')">Ö</div>
            <div class="key" id="bill-ő" onclick="billentyuLeut('Ő')">Ő</div>
            <div class="key" id="bill-ú" onclick="billentyuLeut('Ú')">Ú</div>
            <div class="key" id="bill-ü" onclick="billentyuLeut('Ü')">Ü</div>
            <div class="key" id="bill-ű" onclick="billentyuLeut('Ű')">Ű</div>
            <div class="key" id="bill-í" onclick="billentyuLeut('Í')">Í</div>
        </div>

        <!-- QWERTZ sor -->
        <div class="row">
            <div class="key" id="bill-q" onclick="billentyuLeut('Q')">Q</div>
            <div class="key" id="bill-w" onclick="billentyuLeut('W')">W</div>
            <div class="key" id="bill-e" onclick="billentyuLeut('E')">E</div>
            <div class="key" id="bill-r" onclick="billentyuLeut('R')">R</div>
            <div class="key" id="bill-t" onclick="billentyuLeut('T')">T</div>
            <div class="key" id="bill-z" onclick="billentyuLeut('Z')">Z</div>
            <div class="key" id="bill-u" onclick="billentyuLeut('U')">U</div>
            <div class="key" id="bill-i" onclick="billentyuLeut('I')">I</div>
            <div class="key" id="bill-o" onclick="billentyuLeut('O')">O</div>
            <div class="key" id="bill-p" onclick="billentyuLeut('P')">P</div>
        </div>

        <!-- ASDF sor -->
        <div class="row">
            <div class="key" id="bill-a" onclick="billentyuLeut('A')">A</div>
            <div class="key" id="bill-s" onclick="billentyuLeut('S')">S</div>
            <div class="key" id="bill-d" onclick="billentyuLeut('D')">D</div>
            <div class="key" id="bill-f" onclick="billentyuLeut('F')">F</div>
            <div class="key" id="bill-g" onclick="billentyuLeut('G')">G</div>
            <div class="key" id="bill-h" onclick="billentyuLeut('H')">H</div>
            <div class="key" id="bill-j" onclick="billentyuLeut('J')">J</div>
            <div class="key" id="bill-k" onclick="billentyuLeut('K')">K</div>
            <div class="key" id="bill-l" onclick="billentyuLeut('L')">L</div>
        </div>

        <!-- YXCV sor -->
        <div class="row">
            <div class="key" id="bill-y" onclick="billentyuLeut('Y')">Y</div>
            <div class="key" id="bill-x" onclick="billentyuLeut('X')">X</div>
            <div class="key" id="bill-c" onclick="billentyuLeut('C')">C</div>
            <div class="key" id="bill-v" onclick="billentyuLeut('V')">V</div>
            <div class="key" id="bill-b" onclick="billentyuLeut('B')">B</div>
            <div class="key" id="bill-n" onclick="billentyuLeut('N')">N</div>
            <div class="key" id="bill-m" onclick="billentyuLeut('M')">M</div>
            <div class="key wide" id="bill-<" onclick="billentyuLeut('Backspace')">←</div>
        </div>
    </div>
</div>