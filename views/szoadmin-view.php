<?php
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

?><div class="wrap">
    <h1>Szózat beállítások</h1>
    <h2>Feladványok</h2>
    <table>
        <thead>
            <tr>
                <th>Hozzáadva</th><th>Feladvány</th><th>Egyszavas</th>
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
    <h2>Feladvány hozzáadása</h2>
    <form method="POST" action="">
        <?php wp_nonce_field('szozat_admin_form', 'szozat_nonce'); ?>
        <input type="text" name="feladvany" placeholder="Feladvány szövege" required />
        <input type="date" name="nap" value="<?=date('Y-m-d')?>" required />
        <input type="submit" value="Hozzáadás" />
    </form>
</div>