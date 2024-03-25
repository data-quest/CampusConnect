<div id="messages"></div>

<style>
    #ecs_table tr.active .inactive {
        display: none;
    }
    #ecs_table tr.inactive .active {
        display: none;
    }
</style>

<table id="ecs_table" class="default">
    <caption><?= _("E-Learning Community Servers") ?></caption>
    <thead>
        <tr>
            <th><?= _("Name") ?></th>
            <th><?= _("Aktiv") ?></th>
            <th class="actions"><?= _('Aktion') ?></th>
        </tr>
    </thead>
    <tbody>
        <tr style="display: none;">
            <td></td>
            <td>
                <div class="active"><?= _("aktiv") ?></div>
                <div class="inactive"><?= _("inaktiv") ?></div>
            </td>
        </tr>
        <? if (count($servers)) :?>
        <? foreach ($servers as $server_config) : ?>
        <tr id="ecs_<?= $server_config->getId() ?>" class="selectable <?= $server_config['active'] ? "active" : "inactive" ?>">
            <td><?= htmlReady($server_config['data']['name']) ?></td>
            <td>
                <div class="active"><?= _("aktiv") ?></div>
                <div class="inactive"><?= _("inaktiv") ?></div>
            </td>
            <td class="actions">
                <a href="<?= PluginEngine::getURL($plugin, [], 'config/ecs_edit/'.$server_config->id) ?>" data-dialog>
                    <?= Icon::create('edit') ?>
                </a>
            </td>
        </tr>
        <? endforeach ?>
        <? else : ?>
        <tr>
            <td colspan="3" style="text-align: center;"><?= _("Noch kein ECS registriert. Fügen Sie mit Klick in der Infobox rechts einen hinzu.") ?></td>
        </tr>
        <? endif ?>
    </tbody>
</table>

<?
$actions = new ActionsWidget();
$actions->addLink(_("Stellen Sie eine Verbindung zu einem neuen ECS her."),
    PluginEngine::getURL($plugin, [], 'config/ecs_edit'),
    Icon::create("add"),
    ['data-dialog' => "1"]
);
Sidebar::Get()->addWidget($actions);
