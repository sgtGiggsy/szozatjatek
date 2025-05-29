<?php
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

?><div class="wrap">
    <h1><?=__('Szózat beállítások', 'szozat')?></h1>
    <h2><?=__('Feladványok', 'szozat')?></h2>
    <table>
        <thead>
            <tr>
                <th><?=__('Hozzáadva', 'szozat')?></th><th><?=__('Feladvány', 'szozat')?></th><th><?=__('Egyszavas', 'szozat')?></th>
            </tr>
        </thead>
        <tbody><?php
        foreach($feladvanyok as $feladvany)
        {
            ?><tr>
                <td><?=$feladvany['nap']?></td>
                <td><?=$feladvany['feladvany_szoveg']?></td>
                <td><?=($feladvany['egyszavas'] ? "Igen" : "Nem")?></td>
            </tr><?php
        }
        ?></tbody>
    </table>
    <h2><?=__('Feladvány hozzáadása', 'szozat')?></h2>
    <form method="POST" action="">
        <?php wp_nonce_field('szozat_admin_form', 'szozat_nonce'); ?>
        <input type="text" name="feladvany" placeholder="<?=__('Feladvány szövege', 'szozat')?>" required />
        <input type="date" name="nap" value="<?=date('Y-m-d')?>" required />
        <input type="submit" value="<?=__('Hozzáadás', 'szozat')?>" />
    </form>
</div>