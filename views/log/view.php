<h1><?= _("Log-Einträge") ?></h1>
<table class="default">
    <thead>
        <tr>
            <th><?= _("Log-Typ") ?></th>
            <th><?= _("Eintrag") ?></th>
            <th><?= _("JSON") ?></th>
            <th><?= _("Zeit") ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($entries as $logentry) : ?>
        <tr>
            <td><?= htmlReady($logentry['log_type']) ?></td>
            <td><?= htmlReady($logentry['log_text']) ?></td>
            <td><?= htmlReady($logentry['log_json']) ?></td>
            <td><?= date("d.n.Y G:i", $logentry['mkdate']) ?></td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>