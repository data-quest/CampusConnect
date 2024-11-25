<input type="hidden" id="type" value="<?= htmlReady(Request::get("type")) ?>">
<input type="hidden" id="text" value="<?= htmlReady(Request::get("text")) ?>">
<input type="hidden" id="search" value="<?= htmlReady(Request::get("search")) ?>">
<input type="hidden" id="mkdate" value="<?= htmlReady(Request::get("mkdate")) ?>">

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
                <?= $this->render_partial("log/_logrow.php", array('logentry' => $logentry)) ?>
            <? endforeach ?>
        <? else : ?>
            <tr>
                <td colspan="4" style="text-align: center;"><?= _("Keine Einträge gefunden") ?></td>
            </tr>
        <? endif ?>
    </tbody>
    <tfoot>
        <? if (!empty($more)) : ?>
            <tr id="more">
                <td colspan="4" style="text-align: center;"><?= Assets::img("ajax_indicator_small.gif") ?></td>
            </tr>
        <? endif ?>
    </tfoot>
</table>


<?

if (class_exists("Sidebar")) {
    $search = new SearchWidget(PluginEngine::getURL($this->plugin, array(), "log/view"));
    $search->addNeedle(_("ID, Eigenschaft, Name"), "search", true);
    Sidebar::Get()->addWidget($search);
}
