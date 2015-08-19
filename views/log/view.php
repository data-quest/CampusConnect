<h1><?= _("Log-Einträge") ?></h1>
<table class="default">
    <thead>
        <tr>
            <th><?= _("Log-Typ") ?></th>
            <th><?= _("Eintrag") ?></th>
            <th><?= _("Zeit") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($entries as $logentry) : ?>
        <tr>
            <td><?= htmlReady($logentry['log_type']) ?></td>
            <td><?= htmlReady($logentry['log_text']) ?></td>
            <td><?= date("d.n.Y G:i", $logentry['mkdate']) ?></td>
            <td>
                <a href="<?= PluginEngine::getLink($this->plugin, array(), "log/details/".$logentry['log_id']) ?>" data-dialog><?= Assets::img("icons/16/blue/info-circle") ?></a>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>