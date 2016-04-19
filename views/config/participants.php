<h2><?= _("Teilnehmer") ?></h2>

<style>
    #participant_table tr.active .inactive {
        display: none;
    }
    #participant_table tr.inactive .active {
        display: none;
    }
</style>

<table id="participant_table" class="select">
    <thead>
        <tr>
            <th><?= _("Name") ?></th>
            <th><?= _("ECS") ?></th>
            <th><?= _("Aktiv") ?></th>
        </tr>
    </thead>
    <? if (count($communities)) : ?>
    <? foreach ($communities as $community) : ?>
    <tbody style="border: 2px solid #bbbbbb;">
        <tr class="header">
            <td colspan="3">
                <strong>
                    <?= Assets::img("icons/16/grey/community")." ".htmlReady($community['data']['name']) ?>
                </strong>
                <? if ($community['data']['description']) : ?>
                <br>
                <?= htmlReady($community['data']['description']) ?>
                <? endif ?>
            </td>
        </tr>
        <? $vorhanden = false ?>
        <? foreach ($servers as $server_config) : ?>
        <? if (in_array($community['data']['cid'], $server_config['data']['communities'])) : ?>
        <tr id="participant_<?= $server_config->getId() ?>" class="selectable <?= $server_config['active'] ? "active" : "inactive" ?>">
            <td><?= htmlReady($server_config['data']['name']) ?></td>
            <td>
            <? foreach((array) $server_config['data']['ecs'] as $number => $ecs) : ?>
                <?= $number > 0 ? "|" : "" ?>
                <? $ecs = new CampusConnectConfig($ecs) ?>
                <?= htmlReady($ecs['data']['name']) ?>
            <? endforeach ?>
            </td>
            <td>
                <div class="active"><?= _("aktiv") ?></div>
                <div class="inactive"><?= _("inaktiv") ?></div>
            </td>
        </tr>
        <? $vorhanden = true ?>
        <? endif ?>
        <? endforeach ?>
        <? if (!$vorhanden) : ?>
        <tr>
            <td colspan="3" style="text-align: center;">
                <?= _("Sie sind einziges Mitglied dieser Community.") ?>
            </td>
        </tr>
        <? endif ?>
    </tbody>
    <? endforeach ?>
    <? else : ?>
    <tbody>
        <tr>
            <td colspan="3" style="text-align: center;">
                <?= _("Keine Teilnehmer registriert") ?>
            </td>
        </tr>
    </tbody>
    <? endif ?>
</table>

<?
if (class_exists("Sidebar")) {

} else {
    $infobox = array(
        array(
            'kategorie' => _("Information"),
            'eintrag'   => array(
                array(
                    'icon' => "icons/16/black/info",
                    'text' => _("Hier sehen Sie alle angebundenen Teilnehmer aller ECS.")
                )
            )
        )
    );
    $infobox = array(
        'picture' => $assets_url . "/images/network.png",
        'content' => $infobox
    );
}