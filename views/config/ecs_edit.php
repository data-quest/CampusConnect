<form class="default" method="post" action="<?= PluginEngine::getLink($plugin, [], 'config/ecs_save/'.$server->id) ?>">

    <fieldset>
        <legend><?= _('Grunddaten') ?></legend>

        <label>
            <?= _("Name") ?>
            <input type="text" name="data[name]" value="<?= htmlReady($server['data']['name']) ?>">
        </label>

        <label>
            <?= _("Server URL") ?>
            <input type="text"
                   name="data[server]"
                   id="ecs_data_server"
                   value="<?= htmlReady($server['data']['server']) ?>">
        </label>

        <label>
            <input type="checkbox"
                   id="ecs_active"
                   name="active"
                   value="1" <?= htmlReady($server['active'] > 0 ? ' checked' : '') ?>>
            <?= _("Aktiviert") ?>
        </label>

    </fieldset>

    <fieldset>
        <legend><?= _("Authentifizierung") ?></legend>

        <table style="width: 100%;">
            <tbody>
                <tr>
                    <td colspan="2">
                        <label>
                            <input type="radio"
                                   name="data[auth_type]"
                                   id="ecs_data_auth_type_1"
                                   value="1" <?= htmlReady($server['data']['auth_type'] == 1 ? ' checked' : '') ?>>
                            <?= _("Benutzername/Passwort")?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><label for="ecs_data_auth_user"><?= _("Benutzername") ?></label></td>
                    <td><input type="text"
                               id="ecs_data_auth_user"
                               name="data[auth_user]"
                               value="<?= htmlReady($server['data']['auth_user']) ?>"></td>
                </tr>
                <tr>
                    <td><label for="ecs_data_auth_password"><?= _("Passwort") ?></label></td>
                    <td><input type="password"
                               id="ecs_data_auth_password"
                               name="data[auth_pass]"
                               value="<?= htmlReady($server['data']['auth_pass']) ?>"></td>
                </tr>
            </tbody>
            <tbody>
                <tr>
                    <td colspan="2">
                        <label>
                            <input type="radio"
                                   name="data[auth_type]"
                                   value="2"
                                   <?= htmlReady($server['data']['auth_type'] == 2 ? ' checked' : '') ?>>
                            <?= _("Benutzername/Passwort")?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><label for="ecs_data_ca_cert_path"><?= _("CA Zertifikat") ?></label></td>
                    <td>
                        <input type="text"
                               id="ecs_data_ca_cert_path"
                               name="data[ca_cert_path]"
                               value="<?= htmlReady($server['data']['ca_cert_path']) ?>">
                    </td>
                </tr>
                <tr>
                    <td><label for="ecs_data_client_cert_path"><?= _("Clientzertifikat") ?></label></td>
                    <td><input type="text"
                               id="ecs_data_client_cert_path"
                               name="data[client_cert_path]"
                               value="<?= htmlReady($server['data']['client_cert_path']) ?>">
                    </td>
                </tr>
                <tr>
                    <td><label for="ecs_data_key_path"><?= _("Zertifikatsschlüssel") ?></label></td>
                    <td><input type="text"
                               id="ecs_data_key_path"
                               name="data[key_path]"
                               value="<?= htmlReady($server['data']['key_path']) ?>">
                    </td>
                </tr>
                <tr>
                    <td><label for="ecs_data_key_password"><?= _("Schlüsselpasswort") ?></label></td>
                    <td>
                        <input type="password"
                               id="ecs_data_key_password"
                               name="data[key_password]"
                               value="<?= htmlReady($server['data']['key_password']) ?>">
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(_('Speichern'), 'save') ?>
        <?= \Studip\LinkButton::create(_('Verbindung testen'), '#', ['onclick' => 'STUDIP.CC.ECS.connectivity(); return false;']) ?>
        <?= \Studip\Button::create(_('Löschen'), 'delete', ['data-confirm' => _('Wirklich löschen?')]) ?>
    </div>
</form>
