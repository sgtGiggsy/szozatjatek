<div class="szozat-widget">
    <table>
        <thead>
            <tr>
                <th>Felhasználó</th>
                <th>Összes</th>
                <th>Sikeres</th>
                <th>Átlag</th>
                <th>Széria</th>
                <th>Sikerszéria</th>
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