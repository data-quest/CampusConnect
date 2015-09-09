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
        <? if (count($entries)) : ?>
            <? foreach ($entries as $logentry) : ?>
            <tr>
                <td>
                    <a href="<?= PluginEngine::getLink($this->plugin, array('type' => $logentry['log_type']), "log/view") ?>">
                        <?= htmlReady($logentry['log_type']) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($this->plugin, array('text' => $logentry['log_text']), "log/view") ?>">
                        <?= htmlReady($logentry['log_text']) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($this->plugin, array('mkdate' => $logentry['mkdate']), "log/view") ?>">
                        <?= date("d.m.Y G:i", $logentry['mkdate']) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($this->plugin, array(), "log/details/".$logentry['log_id']) ?>" data-dialog><?= Assets::img("icons/16/blue/info-circle") ?></a>
                </td>
            </tr>
            <? endforeach ?>
        <? else : ?>
            <tr>
                <td colspan="4" style="text-align: center;"><?= _("Keine Einträge gefunden") ?></td>
            </tr>
        <? endif ?>
    </tbody>
</table>


<?

if (class_exists("Sidebar")) {
    $search = new SearchWidget(PluginEngine::getURL($this->plugin, array(), "log/view"));
    $search->addNeedle(_("ID, Eigenschaft, Zeitstempel"), "search", true);
    Sidebar::Get()->addWidget($search);
}