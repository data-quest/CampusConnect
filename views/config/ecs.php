<div id="messages"></div>
<h2><?= _("E-Learning Community Servers") ?></h2>

<style>
    #ecs_table tr.active .inactive {
        display: none;
    }
    #ecs_table tr.inactive .active {
        display: none;
    }
</style>

<table id="ecs_table" class="select">
    <thead>
        <tr>
            <th><?= _("Name") ?></th>
            <th><?= _("Aktiv") ?></th>
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
        </tr>
        <? endforeach ?>
        <? else : ?>
        <tr>
            <td colspan="2" style="text-align: center;"><?= _("Noch kein ECS registriert. Fügen Sie mit Klick in der Infobox rechts einen hinzu.") ?></td>
        </tr>
        <? endif ?>
    </tbody>
</table>

<div id="ecs_edit_window" style="display: none;">
    <input type="hidden" name="id" id="ecs_id">
    <table style="width: 100%; margin-left: auto; margin-right: auto;">
        <tbody>
            <tr>
                <td><label for="ecs_data_name"><?= _("Name") ?></label></td>
                <td><input type="text" id="ecs_data_name" name="data[name]" style="width: 99%"></td>
            </tr>
            <tr>
                <td><label for="ecs_server"><?= _("Server Url") ?></label></td>
                <td><input type="text" id="ecs_server" name="data[server]" style="width: 99%"></td>
            </tr>
            <tr>
                <td style="vertical-align:top;"><?= _("Authentifizierung") ?></td>
                <td>
                    <div>
                    <input type="radio" id="ecs_data_auth_type_1" name="data[auth_type]" value="1">
                    <label for="ecs_data_auth_type_1"><?= _("Benutzername/Passwort")?></label>
                    </div>
                    <table style="width: 100%; padding-left: 10px; margin-right: auto;">
                        <tbody>
                        <tr>
                            <td><label for="ecs_data_auth_user"><?= _("Benutzername") ?></label></td>
                            <td><input type="text" id="ecs_data_auth_user" name="data[auth_user]" style="width: 99%"></td>
                        </tr>
                        <tr>
                            <td><label for="ecs_data_auth_password"><?= _("Passwort") ?></label></td>
                            <td><input type="password" id="ecs_data_auth_password" name="data[auth_pass]" style="width: 99%"></td>
                        </tr>
                        </tbody>
                    </table>
                    <div>
                    <input type="radio" id="ecs_data_auth_type_2" name="data[auth_type]" value="2">
                    <label for="ecs_data_auth_type_2"><?= _("Zertifikatsbasiert")?></label>
                    </div>
                    <table style="width: 100%; padding-left: 10px; margin-right: auto;">
                        <tbody>
                        <tr>
                            <td><label for="ecs_data_ca_cert_path"><?= _("CA Zertifikat") ?></label></td>
                            <td><input type="text" id="ecs_data_ca_cert_path" name="data[ca_cert_path]" style="width: 99%"></td>
                        </tr>
                        <tr>
                            <td><label for="ecs_data_client_cert_path"><?= _("Clientzertifikat") ?></label></td>
                            <td><input type="text" id="ecs_data_client_cert_path" name="data[client_cert_path]" style="width: 99%"></td>
                        </tr>
                        <tr>
                            <td><label for="ecs_data_key_path"><?= _("Zertifikatsschlüssel") ?></label></td>
                            <td><input type="text" id="ecs_data_key_path" name="data[key_path]" style="width: 99%"></td>
                        </tr>
                        <tr>
                            <td><label for="ecs_data_key_password"><?= _("Schlüsselpasswort") ?></label></td>
                            <td><input type="password" id="ecs_data_key_password" name="data[key_password]" style="width: 99%"></td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>

            <tr>
                <td><label for="ecs_active"><?= _("Aktiviert") ?></label></td>
                <td><input type="checkbox" id="ecs_active" name="active" value="1"></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <a href="#" onClick="STUDIP.CC.ECS.save_ecs_data(); return false;"><?= Studip\Button::create(_("speichern")) ?></a>
                    <a href="#" onClick="STUDIP.CC.ECS.connectivity(); return false;"><?= Studip\Button::create(_("Verbindung testen")) ?></a>
                    <a href="#" id="ecs_delete" onClick="STUDIP.CC.ECS.del(); return false;"><?= Studip\Button::create(_("löschen")) ?></a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div id="ecs_edit_window_title" style="display: none;"><?= _("Serverinformation bearbeiten") ?></div>
<div id="ecs_edit_window_title_new" style="display: none;"><?= _("Neuen ECS registrieren") ?></div>

<?
if (class_exists("Sidebar")) {
    $actions = new ActionsWidget();
    $actions->addLink(_("Stellen Sie eine Verbindung zu einem neuen ECS her."), "#", "icons/16/black/add", array('onclick' => "STUDIP.CC.ECS.new(); return false;"));
    Sidebar::Get()->addWidget($actions);
} else {
    $infobox = array(
        array(
            'kategorie' => _("Information"),
            'eintrag'   => array(
                array(
                    'icon' => "icons/16/black/info",
                    'text' => _("Hier sehen Sie alle ECS (elearning community server), können sie konfigurieren oder inaktiv schalten.")
                )
            )
        ),
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => array(
                array(
                    'icon' => "icons/16/black/add",
                    'text' => '<a href="#" onClick="STUDIP.CC.ECS.new(); return false;">'.
                        _("Stellen Sie eine Verbindung zu einem neuen ECS her.").
                    '</a>'
                )
            )
        )
    );
    $infobox = array(
        'picture' => $assets_url . "/images/network.png",
        'content' => $infobox
    );
}