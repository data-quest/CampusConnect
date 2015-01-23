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
<p class="info"><?= _("Es werden niemals Veranstaltungen exportiert, die über CampusConnect importiert wurden. Alle hier definierten Filter sind zusätzlich dazu.") ?></p>
<table>
    <tbody>
        <tr>
            <td width="30%">
                <?= _("Exportfilter aktivieren") ?>
            </td>
            <td width="70%">
                <label>
                    <?= _("Studienbereiche") ?>
                    <input type="checkbox" name="data[export_settings][filter_sem_tree_activate]"<?= $server['data']['export_settings']['filter_sem_tree_activate'] ? " checked" : "" ?> value="1" onChange="jQuery('#filter_sem_tree').toggle('fade');">
                </label>
                <br>
                <label>
                    <?= _("Datenfelder") ?>
                    <input type="checkbox" name="data[export_settings][filter_datafields_activate]"<?= $server['data']['export_settings']['filter_datafields_activate'] ? " checked" : "" ?> value="1" onChange="jQuery('#filter_datafields').toggle('fade');">
                </label>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr id="filter_sem_tree"<?= !$server['data']['export_settings']['filter_sem_tree_activate'] ? ' style="display: none; "' : "" ?>>
            <td>
                <?= _("Filter nach Studiengängen") ?>
            </td>
            <td>
                <?= _("Nur Veranstaltungen exportieren, die den folgenden Studienbereichen zugeordnet sind.") ?>
                <?= \CampusConnect\StudyAreaSelector::create("data[export_settings][filter_sem_tree]", "multiple")
                        ->setDefault(array_keys(array_filter($server['data']['export_settings']['filter_sem_tree'])))
                        ->render() ?>
            </td>
        </tr>
        <tr id="filter_datafields"<?= !$server['data']['export_settings']['filter_datafields_activate'] ? ' style="display: none;"' : "" ?>>
            <td>
                <?= _("Filter nach Datenfeld") ?>
            </td>
            <td>
                <select name="data[export_settings][filter_datafield]">
                <? foreach ($datafields as $datafield) : ?>
                    <option value="<?= htmlReady($datafield['datafield_id']) ?>"<?= $server['data']['export_settings']['filter_datafield'] === $datafield['datafield_id'] ? " selected" : "" ?>>
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