<div class="overview">
    <div class="overview_header">
        <?= _("Statistik") ?>
    </div>
    <div>
        <strong><?= _("Importierte Veranstaltungen") ?></strong>:
        <?= (int) $imported_courses ?>
    </div>
</div>
<div class="overview">
    <div class="overview_header">
        <a href="#" onClick="STUDIP.CC.ECS.export_stack(); return false;">
            <?= _("Synchronisierung durchführen") ?>
        </a>
        <span style="display: none;" id="sync_loader">
            <?= Assets::img("ajax_indicator_small.gif") ?>
        </span>
    </div>
    <div>
        <strong><?= _("Objekte, die synchronisiert werden müssen") ?></strong>:
        <span id="items_to_be_synced">
            <?= (int) count(CampusConnectTriggerStack::findAll()) ?>
        </span>
    </div>
</div>
<div class="overview">
    <div class="overview_header">
        <a href="#" onClick="STUDIP.CC.ECS.import_changes(); return false;">
            <?= _("Daten abrufen") ?>
        </a>
        <span style="display: none;" id="import_sync_loader">
            <?= Assets::img("ajax_indicator_small.gif") ?>
        </span>
    </div>
</div>
<div class="overview">
    <div class="overview_header">
        <a href="#" onClick="STUDIP.CC.checks.test(); return false;">
            <?= _("Unit-Tests durchführen") ?>
        </a>
    </div>
    <div id="progressbar">
    </div>
    <div style="display: none;" id="test_results">
    </div>
    <div id="progress_time" style="display: none;"><?= $_SESSION['unit_test_progress_time'] ? (int) $_SESSION['unit_test_progress_time'] : "8000" ?></div>
</div>
<div class="overview">
    <div class="overview_header">
        <a href="#" onClick="STUDIP.CC.ECS.export_everything(); return false;">
            <?= _("Alle Daten nochmal zum Update senden") ?>
        </a>
    </div>
    <span style="display: none;" id="update_everything_loader">
        <?= Assets::img("ajax_indicator_small.gif") ?>
    </span>
</div>
