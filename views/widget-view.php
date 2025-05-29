<div class="szozat-widget">
    <table>
        <thead>
            <tr>
                <th><?=__('Felhasználó', 'szozat')?></th>
                <th><?=__('Összes', 'szozat')?></th>
                <th><?=__('Sikeres', 'szozat')?></th>
                <th><?=__('Átlag', 'szozat')?></th>
                <th><?=__('Széria', 'szozat')?></th>
                <th><?=__('Sikerszéria', 'szozat')?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?=esc_html($row['display_name'])?></td>
                    <td><?=$row['feladvanyok']?></td>
                    <td><?=$row['sikerrata']?>%</td>
                    <td><?=$row['atlag_kiserlet']?></td>
                    <td><?=$row['leghosszabb_sorozat']?></td>
                    <td><?=$row['leghosszabb_sikersorozat']?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>