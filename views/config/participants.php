<table id="participant_table" class="default">
    <caption>
        <?= _("Teilnehmer") ?>
    </caption>
    <thead>
        <tr>
            <th><?= _("Name") ?></th>
            <th><?= _("ECS") ?></th>
            <th><?= _("Aktiv") ?></th>
        </tr>
    </thead>
    <? if (count($communities)) : ?>
    <? foreach ($communities as $community) : ?>
    <tbody>
        <tr class="header">
            <td colspan="3">
                <strong>
                    <?= Icon::create("community", Icon::ROLE_INFO)->asImg(20, ['class' => 'text-bottom']) ?>
                    <?= htmlReady($community['data']['name']) ?>
                </strong>
                <? if ($community['data']['description']) : ?>
                    <div>
                        <?= htmlReady($community['data']['description']) ?>
                    </div>
                <? endif ?>
            </td>
        </tr>
        <? $vorhanden = false ?>
        <? foreach ($servers as $server_config) : ?>
        <? $server_data = $server_config->data->getArrayCopy() ?>
        <? if (in_array($community['data']['cid'], (array) $server_data['communities'])) : ?>
        <tr id="participant_<?= $server_config->getId() ?>" class="<?= $server_config['active'] ? "active" : "inactive" ?>">
            <td>
                <a href="<?= PluginEngine::getLink($plugin, ['id' => $server_config->id], 'config/participant') ?>">
                    <?= htmlReady($server_data['name']) ?>
                </a>
            </td>
            <td>
            <? foreach((array) $server_data['ecs'] as $number => $ecs) : ?>
                <?= $number > 0 ? "|" : "" ?>
                <? $ecs = new CampusConnectConfig($ecs[0]) ?>
                <?= htmlReady($ecs['data']['name']) ?>
            <? endforeach ?>
            </td>
            <td>
                <? if ($server_config['active']) : ?>
                    <?= Icon::create('accept', Icon::ROLE_STATUS_GREEN)->asImg(20, ['class' => 'text-bottom']) ?>
                    <?= _("aktiv") ?>
                <? else : ?>
                    <div class="inactive"><?= _("inaktiv") ?></div>
                <? endif ?>
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
