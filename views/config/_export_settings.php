<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

$server_data = $server->data->getArrayCopy();
?>
<table class="cc_settings">
    <tbody>
        <tr>
            <td>
                <?= _("Auth-Token-Attribute abspeichern als") ?>
            </td>
            <td>
                <table>
                    <tbody id="data__export_settings__auth_token__attributes">
                        <tr>
                            <td>ecs_login</td>
                            <td>
                                <? $name = "ecs_login" ?>
                                <select name="data[export_settings][auth_token][attributes][<?= htmlReady($name) ?>]" data-name="data[export_settings][auth_token][attributes][__REPLACE__]">
                                    <option value="user_id"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "user_id" ? " selected" : "" ?>>user_id</option>
                                    <option value="username"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && (($server['data']['export_settings']['auth_token']['attributes'][$name] === "username") || !$server['data']['export_settings']['auth_token']['attributes'][$name]) ? " selected" : "" ?>>username</option>
                                    <option value="email"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                    <option value="institut"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "institut" ? " selected" : "" ?>><?= _("Heimateinrichtung") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>ecs_email</td>
                            <td>
                                <? $name = "ecs_email" ?>
                                <select name="data[export_settings][auth_token][attributes][<?= htmlReady($name) ?>]"
                                        data-name="data[export_settings][auth_token][attributes][__REPLACE__]">
                                    <option value="user_id"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "user_id" ? " selected" : "" ?>>user_id</option>
                                    <option value="username"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "username" ? " selected" : "" ?>>username</option>
                                    <option value="email"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && (($server['data']['export_settings']['auth_token']['attributes'][$name] === "email") || !$server['data']['export_settings']['auth_token']['attributes'][$name]) ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                    <option value="institut"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "institut" ? " selected" : "" ?>><?= _("Heimateinrichtung") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>ecs_institution</td>
                            <td>
                                <? $name = "ecs_institution" ?>
                                <select name="data[export_settings][auth_token][attributes][<?= htmlReady($name) ?>]" data-name="data[export_settings][auth_token][attributes][__REPLACE__]">
                                    <option value="user_id"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "user_id" ? " selected" : "" ?>>user_id</option>
                                    <option value="username"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "username" ? " selected" : "" ?>>username</option>
                                    <option value="email"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                    <option value="institut"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && (($server['data']['export_settings']['auth_token']['attributes'][$name] === "institut") || !$server['data']['export_settings']['auth_token']['attributes'][$name]) ? " selected" : "" ?>><?= _("Heimateinrichtung") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"<?= !empty($server['data']['export_settings']['auth_token']['attributes'][$name]) && $server['data']['export_settings']['auth_token']['attributes'][$name] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <? if (!empty($server_data['export_settings']['auth_token']['attributes'])) : ?>
                        <? foreach ((array) $server_data['export_settings']['auth_token']['attributes'] as $name => $mapping) : ?>
                            <? if (!in_array($name, array("ecs_login", "ecs_email", "ecs_institution"))) : ?>
                                <tr>
                                    <td><input type="text" placeholder="<?= _("Weiteres Attribut") ?>" value="<?= htmlReady($name) ?>" onChange="var select = jQuery(this).closest('tr').find('select'); select.attr('name', select.data('name').replace('__REPLACE__', this.value));"></td>
                                    <td>
                                        <select name="data[export_settings][auth_token][attributes][<?= htmlReady($name) ?>]" data-name="data[export_settings][auth_token][attributes][__REPLACE__]">
                                            <option value="user_id"<?= $mapping === "user_id" ? " selected" : "" ?>>user_id</option>
                                            <option value="username"<?= $mapping === "username" ? " selected" : "" ?>>username</option>
                                            <option value="email"<?= $mapping === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                            <option value="institut"<?= $mapping === "institut" ? " selected" : "" ?>><?= _("Heimateinrichtung") ?></option>
                                            <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                                <option value="<?= $datafield->getId() ?>"<?= $mapping === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                            <? endforeach ?>
                                        </select>
                                        <a href="#" onClick="if (window.confirm('<?= _("Wirklich löschen?") ?>')) { jQuery(this).closest('tr').fadeOut(function() { jQuery(this).remove(); }); }; return false;">
                                            <?= Icon::create("trash")->asImg(20, array('class' => "text-bottom")) ?>
                                        </a>
                                    </td>
                                </tr>
                            <? endif ?>
                        <? endforeach ?>
                        <? endif ?>
                    </tbody>
                    <tfoot>
                        <tr id="data__export_settings__auth_token__attributes_template" style="display: none;">
                            <td><input type="text" placeholder="<?= _("Weiteres Attribut") ?>" onChange="var select = jQuery(this).closest('tr').find('select'); select.attr('name', select.data('name').replace('__REPLACE__', this.value));"></td>
                            <td>
                                <select data-name="data[export_settings][auth_token][attributes][__REPLACE__]">
                                    <option value="user_id">user_id</option>
                                    <option value="username">username</option>
                                    <option value="email"><?= _("Email-Adresse") ?></option>
                                    <option value="institut"><?= _("Heimateinrichtung") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                                <a href="#" onClick="if (window.confirm('<?= _("Wirklich löschen?") ?>')) { jQuery(this).closest('tr').fadeOut(function() { jQuery(this).remove(); }); }; return false;">
                                    <?= Icon::create("trash")->asImg(20, array('class' => "text-bottom")) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="#" onClick="jQuery('#data__export_settings__auth_token__attributes_template').clone().removeAttr('id').appendTo('#data__export_settings__auth_token__attributes').fadeIn(); return false;">
                                    <?= Icon::create("add")->asImg() ?>
                                </a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="grey"></td>
        </tr>

        <tr>
            <td width="30%">
                <?= _("Exportfilter aktivieren") ?>
                <p style="font-size: 0.8em;"><?= _("Es werden niemals Veranstaltungen exportiert, die über CampusConnect importiert wurden. Alle hier definierten Filter sind zusätzlich dazu.") ?></p>
            </td>
            <td width="70%">
                <label>
                    <?= _("Studienbereiche") ?>
                    <input type="checkbox" name="data[export_settings][filter_sem_tree_activate]"<?= !empty($server['data']['export_settings']['filter_sem_tree_activate']) ? " checked" : "" ?> value="1" onChange="jQuery('#filter_sem_tree').toggle('fade');">
                </label>
                <br>
                <label>
                    <?= _("Datenfelder") ?>
                    <input type="checkbox" name="data[export_settings][filter_datafields_activate]"<?= !empty($server['data']['export_settings']['filter_datafields_activate']) ? " checked" : "" ?> value="1" onChange="jQuery('#filter_datafields').toggle('fade');">
                </label>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr id="filter_sem_tree"<?= empty($server['data']['export_settings']['filter_sem_tree_activate']) ? ' style="display: none; "' : "" ?>>
            <td>
                <?= _("Filter nach Studiengängen") ?>
            </td>
            <td>
                <?= _("Nur Veranstaltungen exportieren, die den folgenden Studienbereichen zugeordnet sind.") ?>
                <?= CampusConnectStudyAreaSelector::create("data[export_settings][filter_sem_tree]", "multiple")
                        ->setDefault(!empty($server['data']['export_settings']['filter_sem_tree']) ? array_keys(array_filter((array) $server['data']['export_settings']['filter_sem_tree'])) : [])
                        ->render() ?>
            </td>
        </tr>
        <tr id="filter_datafields"<?= empty($server['data']['export_settings']['filter_datafields_activate']) ? ' style="display: none;"' : "" ?>>
            <td>
                <?= _("Filter nach Datenfeld") ?>
            </td>
            <td>
                <select name="data[export_settings][filter_datafield]">
                <? foreach ($datafields as $datafield) : ?>
                    <option value="<?= htmlReady($datafield['datafield_id']) ?>"<?= !empty($server['data']['export_settings']['filter_datafield']) && $server['data']['export_settings']['filter_datafield'] === $datafield['datafield_id'] ? " selected" : "" ?>>
                        <?= htmlReady($datafield['name']) ?>
                    </option>
                <? endforeach ?>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td>
                <a href="#" onClick="STUDIP.CC.participants.save_data(); return false;"><?= Studip\Button::create(_("speichern")) ?></a>
            </td>
        </tr>
    </tbody>
</table>
