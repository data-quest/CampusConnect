<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

?>
<div id="messages"></div>

<table class="attribute_table default" style="margin: 10px; width: 95%">
    <caption>
        <?= htmlReady($server['data']['name']) ?>
    </caption>
    <tbody>
        <tr>
            <td>
                <?= _("Domänenname") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['dns']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Institution") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['org']['name']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("E-Mail des Ansprechpartners vor Ort") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['email']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Kürzel") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['org']['abbr']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Identifikation") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['pid']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Communities") ?>
            </td>
            <td>
                <ul class="clean">
                <? foreach ($server['data']['mid'] as $cid => $mid) : ?>
                    <li>
                        <?= htmlReady($communities[$cid]['data']['name'])." &rArr; ID: ".htmlReady($mid) ?>
                    </li>
                <? endforeach ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Beschreibung") ?>
            </td>
            <td>
                <?= htmlReady($server['data']['description']) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Stud.IP-interne ID") ?>
            </td>
            <td>
                <input type="hidden" name="id" value="<?= htmlReady($server->id) ?>">
                <?= htmlReady($server->id) ?>
            </td>
        </tr>
        <tr>
            <td><?= _("ECS") ?></td>
            <td>
            <? $server_data = $server->data->getArrayCopy() ?>
            <? foreach((array) $server_data['ecs'] as $ecs) : ?>
                <? $ecs = new CampusConnectConfig($ecs) ?>
                <?= htmlReady($ecs['data']['name']) ?><br>
            <? endforeach ?>
            </td>
        </tr>
        <tr>
            <td><label for="p_active"><?= _("Aktiviert") ?></label></td>
            <td>
                <input type="checkbox" id="p_active" name="active" <?= $server['active'] ? "checked" : "" ?> value="1">
            </td>
        </tr>
        <tr>
            <td><label for="p_import"><?= _("Import vom Teilnehmer") ?></label></td>
            <td>
                <input type="checkbox" id="p_import" name="data[import]" <?= $server['data']['import'] ? "checked" : "" ?> value="1" onChange="jQuery(this).next('.sync_setting').toggle('fade');">
                <span class="sync_setting"<?= !$server['data']['import'] ? ' style="display: none"' : "" ?>>
                    <select name="data[import_settings][course_entity_type]" style="width: 250px;" id="import_course_type" onChange="STUDIP.CC.participants.showImportFields();">
                        <option value="kurslink"<?= $server['data']['import_settings']['course_entity_type'] === "kurslink" ? " selected" : "" ?>><?= _("Kurslinks") ?></option>
                        <option value="kurs"<?= $server['data']['import_settings']['course_entity_type'] === "kurs" ? " selected" : "" ?>><?= _("Kurse") ?></option>
                    </select>

                    <a href="#" onClick="STUDIP.CC.participants.setup_import(); return false;"><?= _("Konfiguration") ?></a>
                    <? /*<a target="_blank" href="<?= PluginEngine::getLink($plugin, array('id' => $server->getId()), 'config/participant_courses') ?>">Zu den Kursen</a> */ ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><label for="p_export"><?= _("Export zum Teilnehmer") ?></label></td>
            <td>
                <input type="checkbox" id="p_export" name="data[export]" <?= $server['data']['export'] ? "checked" : "" ?> value="1" onChange="jQuery(this).next('.sync_setting').toggle('fade');">
                <span class="sync_setting"<?= !$server['data']['export'] ? ' style="display: none"' : "" ?>>
                    <select name="data[export_settings][course_entity_type]" style="width: 250px;">
                        <option value="kurslink"<?= $server['data']['export_settings']['course_entity_type'] === "kurslink" ? " selected" : "" ?>><?= _("Kurslinks") ?></option>
                        <option value="kurs"<?= $server['data']['export_settings']['course_entity_type'] === "kurs" ? " selected" : "" ?>><?= _("Kurse") ?></option>
                    </select>
                    <a href="#" onClick="STUDIP.CC.participants.setup_export(); return false;"><?= _("Konfiguration") ?></a>
                </span>
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

<div id="import_settings_window_title" style="display: none;"><?= _("Importeinstellung von diesem Teilnehmer") ?></div>
<div id="import_settings_window" style="display: none;">
    <?= $this->render_partial("config/_import_settings.php") ?>
</div>

<div id="export_settings_window_title" style="display: none;"><?= _("Exporteinstellung zu diesem Teilnehmer") ?></div>
<div id="export_settings_window" style="display: none;">
    <?= $this->render_partial("config/_export_settings.php") ?>
</div>

<div id="import_directory_trees_settings_window_title" style="display: none;"><?= _("Verzeichnisbäume mappen") ?></div>
<div id="import_directory_trees_settings_window" style="display: none;"></div>

<script>
jQuery(STUDIP.CC.participants.showImportFields);
</script>

