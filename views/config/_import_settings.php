<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
$server; //server-data of participant
?>
<table style="margin-left: auto; margin-right: auto;" class="cc_settings">
    <tbody>
        <tr class="kurs_only kurslink_only">
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr class="kurs_only kurslink_only">
            <td><label for="sem_type"><?= _("Default-Seminartyp aller importierten Kurse") ?></label></td>
            <td>
                <select id="sem_type" name="data[import_settings][default_sem_type]">
                    <? foreach ($GLOBALS['SEM_CLASS'] as $sem_class) : ?>
                    <? foreach ($GLOBALS['SEM_TYPE'] as $index => $sem_type) : ?>
                    <? if ($sem_type['class'] == $sem_class['id']) : ?>
                    <option value="<?= $index ?>"<?= $server['data']['import_settings']['default_sem_type'] == $index ? " selected" : "" ?>>
                        <?= $GLOBALS['SEM_CLASS'][$sem_type['class']]['name'] ?>
                        /
                        <?= $sem_type['name'] ?>
                    </option>
                    <? endif ?>
                    <? endforeach ?>
                    <? endforeach ?>
                </select>
            </td>
        </tr>
        <tr class="kurs_only kurslink_only">
            <td><?= _("Seminartyp-Matching") ?></td>
            <td>
                <div style="display: none;" class="template">
                    <input type="text" placeholder="<?= _("Kurstyp des Fremdsystems") ?>" onChange="if (this.value) { jQuery(this).nextAll('select').attr('name', 'data[import_settings][sem_type_matching][' + encodeURI(this.value) + ']'); } else { jQuery(this).closest('div').remove(); }">
                    <?= Assets::img("icons/16/grey/arr_2right.png", array('class' => "middle", 'title' => _("wird gematched auf"))) ?>
                    <select id="sem_type">
                        <? foreach ($GLOBALS['SEM_CLASS'] as $sem_class) : ?>
                        <? foreach ($GLOBALS['SEM_TYPE'] as $index => $sem_type) : ?>
                        <? if ($sem_type['class'] == $sem_class['id']) : ?>
                        <option value="<?= $index ?>">
                            <?= $GLOBALS['SEM_CLASS'][$sem_type['class']]['name'] ?>
                            /
                            <?= $sem_type['name'] ?>
                        </option>
                        <? endif ?>
                        <? endforeach ?>
                        <? endforeach ?>
                    </select>
                    <a href="" onClick="jQuery(this).closest('div').remove(); return false;">
                        <?= Assets::img("icons/16/blue/trash", array('class' => "middle")) ?>
                    </a>
                </div>

                <div class="sem_types">
                <? foreach ((array) $server['data']['import_settings']['sem_type_matching'] as $key => $type) : ?>
                <div>
                    <input type="text" placeholder="<?= _("Kurstyp des Fremdsystems") ?>" onChange="if (jQuery(this).val()) { jQuery(this).nextAll('select').attr('name', 'data[import_settings][sem_type_matching][' + encodeURI(this.value) + ']'); } else { jQuery(this).closest('div').remove(); }" value="<?= htmlReady($key) ?>">
                    <?= Assets::img("icons/16/grey/arr_2right.png", array('class' => "middle", 'title' => _("wird gematched auf"))) ?>
                    <select id="sem_type" name="data[import_settings][sem_type_matching][<?= urlencode(studip_utf8encode($key)) ?>]">
                        <? foreach ($GLOBALS['SEM_CLASS'] as $sem_class) : ?>
                        <? foreach ($GLOBALS['SEM_TYPE'] as $index => $sem_type) : ?>
                        <? if ($sem_type['class'] == $sem_class['id']) : ?>
                        <option value="<?= $index ?>"<?= $server['data']['import_settings']['sem_type_matching'][$key] == $index ? " selected" : "" ?>>
                            <?= $GLOBALS['SEM_CLASS'][$sem_type['class']]['name'] ?>
                            /
                            <?= $sem_type['name'] ?>
                        </option>
                        <? endif ?>
                        <? endforeach ?>
                        <? endforeach ?>
                    </select>
                    <a href="" onClick="jQuery(this).closest('div').remove(); return false;">
                        <?= Assets::img("icons/16/blue/trash", array('class' => "middle")) ?>
                    </a>
                </div>
                <? endforeach ?>
                </div>

                <div>
                    <a href="" onClick="jQuery(this).closest('td').find('.sem_types').append(jQuery(this).closest('td').children('.template').clone().show()); return false;">
                        <?= Assets::img("icons/16/blue/plus") ?>
                    </a>
                </div>
            </td>
        </tr>
        <tr class="kurslink_only">
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr class="kurslink_only">
            <td><label for="import_settings__institute"><?= _("Heimateinrichtung der importierten Kurslinks") ?></label></td>
            <td>
                <select name="data[import_settings][institute]" id="import_settings__institute">
                    <? foreach ($institute as $institut) : ?>
                    <option<?= $server['data']['import_settings']['institute'] == $institut['Institut_id'] ? " selected" : "" ?> value="<?= htmlReady($institut['Institut_id']) ?>"><?= (!$institut['is_fak'] ? "&nbsp;&nbsp;&nbsp;" : ""). htmlReady($institut['Name']) ?></option>
                    <? endforeach ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr>
            <td><label for="data__import_settings____sem_tree___1"><?= _("Default-Studienbereich") ?></label></td>
            <td>
                <?= QuickSearch::get(
                    "data__import_settings____sem_tree__",
                    new SQLSearch("SELECT sem_tree_id, name FROM sem_tree WHERE name LIKE :input", _("Studienbereich w�hlen"))
                    )->defaultValue($server['data']['import_settings']['sem_tree'], $server['data']['import_settings']['sem_tree'] ? \StudipStudyArea::find($server['data']['import_settings']['sem_tree'])->name : "")
                    ->render() ?>
            </td>
        </tr>
        <tr class="kurslink_only">
            <td><label for="dynamically_add_studyareas"><?= _("Studienbereiche dynamisch erzeugen, falls vorhanden") ?></label></td>
            <td><input type="checkbox" name="data[import_settings][dynamically_add_semtree]" id="dynamically_add_studyareas" value="1"<?= $server['data']['import_settings']['dynamically_add_semtree'] ? " checked" : "" ?>></td>
        </tr>
        <tr class="cms_only">
            <td><label for="import_settings__directory_tree__override_title"><?= _("Name der Studienbereiche immer �berschreiben") ?></label></td>
            <td>
                <input type="checkbox" name="data[import_settings][directory_tree][override_title]" id="import_settings__directory_tree__override_title" value="1"<?= $server['data']['import_settings']['directory_tree']['override_title'] ? " checked" : "" ?>>
            </td>
        </tr>
        <tr class="cms_only">
            <td><label for="import_settings__directory_tree__import_nonempty"><?= _("Nur Kategorien importieren, die Kurse enthalten") ?></label></td>
            <td>
                <input type="checkbox" name="data[import_settings][directory_tree][import_nonempty]" id="import_settings__directory_tree__import_nonempty" value="1"<?= $server['data']['import_settings']['directory_tree']['import_nonempty'] ? " checked" : "" ?>>
            </td>
        </tr>
        <tr class="cms_only">
            <td colspan="2">
                <a href="#" onClick="STUDIP.CC.participants.showTreeMapping(); return false;">
                    <?= Assets::img("icons/16/blue/admin") ?>
                    <?= _("Zum Mapping der importierten B�ume") ?>
                </a>
            </td>
        </tr>

        <tr class="kurslink_only">
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr class="kurslink_only">
            <td><label for="import_settings__auth"><?= _("SingleSignOn-Mechanismus des Teilnehmers") ?></label></td>
            <td>
                <select id="import_settings__auth" name="data[import_settings][auth]" onChange="if (this.value=== 'ecs_token') { jQuery('#import_settings__auth_token').removeClass('hidden'); } else { jQuery('#import_settings__auth_token').addClass('hidden'); }">
                    <option value="ecs_token"<?= $server['data']['import_settings']['auth'] === "ecs_token" ? " selected" : "" ?>><?= _("ECS-Auth-Token") ?></option>
                    <option value="legacy_ecs_token"<?= $server['data']['import_settings']['auth'] === "legacy_ecs_token" ? " selected" : "" ?>><?= _("Legacy ECS-Auth-Token (veraltet)") ?></option>
                    <option value="no"<?= $server['data']['import_settings']['auth'] === "no" ? " selected" : "" ?>><?= _("Kein SSO �ber CampusConect") ?></option>
                </select>
            </td>
        </tr>
        <tr id="import_settings__auth_token" class="kurslink_only<?= !$server['data']['import_settings']['auth'] || $server['data']['import_settings']['auth'] !== "ecs_token" ? " hidden" : "" ?>">
            <!-- Stud.IP import Kurslinks, die eigenen Nutzer brauchen einen Auth-Token, um zum Fremdsystem zu kommen, wie soll der aussehen? -->
            <td><?= _("ECS-Auth-Token Konfiguration") ?></td>
            <td>
                <table class="default">
                    <thead>
                    <tr>
                        <th><?= _("Identifizierer") ?></th>
                        <th><?= _("Stud.IP-Attribut") ?></th>
                    </tr>
                    </thead>
                    <tbody id="data__import_settings__auth_token__attributes">
                    <tr>
                        <td>
                            <label>
                                <select name="data[import_settings][auth_token][id_type]">
                                    <option>ecs_uid</option>
                                    <option>ecs_loginUID</option>
                                    <option>ecs_login</option>
                                    <option>ecs_email</option>
                                    <option>ecs_PersonalUniqueCode</option>
                                    <option>ecs_custom</option>
                                </select>
                            </label>
                        </td>
                        <td>
                            <select name="data[import_settings][auth_token][id]">
                                <option value="user_id">user_id</option>
                                <option value="username"<?= $server['data']['import_settings']['auth_token']['id'] === "username" ? " selected" : "" ?>>username</option>
                                <option value="email"<?= $server['data']['import_settings']['auth_token']['id'] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                    <option value="<?= $datafield->getId() ?>"<?= $server['data']['import_settings']['auth_token']['id'] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                <? endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <? foreach ((array) $server['data']['import_settings']['auth_token']['attributes'] as $name => $mapping) : ?>
                    <tr>
                        <td><input type="text" placeholder="<?= _("Weiteres Attribut") ?>" value="<?= htmlReady($name) ?>" onChange="var select = jQuery(this).closest('tr').find('select'); select.attr('name', select.data('name').replace('__REPLACE__', this.value));"></td>
                        <td>
                            <select name="data[import_settings][auth_token][attributes][<?= htmlReady($name) ?>]" data-name="data[import_settings][auth_token][attributes][__REPLACE__]">
                                <option value="user_id"<?= $server['data']['import_settings']['auth_token']['attributes'][$name] === "user_id" ? " selected" : "" ?>>user_id</option>
                                <option value="username"<?= $server['data']['import_settings']['auth_token']['attributes'][$name] === "username" ? " selected" : "" ?>>username</option>
                                <option value="email"<?= $server['data']['import_settings']['auth_token']['attributes'][$name] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                    <option value="<?= $datafield->getId() ?>"<?= $server['data']['import_settings']['auth_token']['attributes'][$name] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                <? endforeach ?>
                            </select>
                            <a href="#" onClick="if (window.confirm('<?= _("Wirklich l�schen?") ?>')) { jQuery(this).closest('tr').fadeOut(function() { jQuery(this).remove(); }); }; return false;">
                                <?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?>
                            </a>
                        </td>
                    </tr>
                    <? endforeach ?>
                    </tbody>
                    <tobdy>
                        <tr id="data__import_settings__auth_token__attributes_template" style="display: none;">
                            <td><input type="text" placeholder="<?= _("Weiteres Attribut") ?>" onChange="var select = jQuery(this).closest('tr').find('select'); select.attr('name', select.data('name').replace('__REPLACE__', this.value));"></td>
                            <td>
                                <select data-name="data[import_settings][auth_token][attributes][__REPLACE__]">
                                    <option value="user_id">user_id</option>
                                    <option value="username"<?= $server['data']['import_settings']['auth_token']['id'] === "username" ? " selected" : "" ?>>username</option>
                                    <option value="email"<?= $server['data']['import_settings']['auth_token']['id'] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"<?= $server['data']['import_settings']['auth_token']['id'] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                                <a href="#" onClick="if (window.confirm('<?= _("Wirklich l�schen?") ?>')) { jQuery(this).closest('tr').fadeOut(function() { jQuery(this).remove(); }); }; return false;">
                                    <?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?>
                                </a>
                            </td>
                        </tr>
                    </tobdy>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <a href="#" onClick="jQuery('#data__import_settings__auth_token__attributes_template').clone().removeAttr('id').appendTo('#data__import_settings__auth_token__attributes').fadeIn(); return false;">
                                    <?= Assets::img("icons/16/blue/add") ?>
                                </a>
                            </td>
                        </tr>
                    </tfoot>
                </table>

            </td>
        </tr>
        <tr class="cms_only">
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr class="cms_only">
            <td>
                <?= _("Personen werden folgenderma�en identifiziert") ?>
            </td>
            <td>
                <table>
                    <tbody>
                        <tr>
                            <td><label><?= _("Standard") ?></label></td>
                            <td><select id="import_settings__cms__user_identifier" name="data[import_settings][cms][user_identifier]">
                                    <option value="username"<?= $server['data']['import_settings']['cms']['user_identifier'] === "username" ? " selected" : "" ?>>username</option>
                                    <option value="email"<?= $server['data']['import_settings']['cms']['user_identifier'] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                    <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                        <option value="<?= $datafield->getId() ?>"<?= $server['data']['import_settings']['cms']['user_identifier'] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                    <? endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <? foreach (array("ecs_PersonalUniqueCode", "ecs_ePPN", "ecs_login", "ecs_loginUID", "ecs_uid", "ecs_email") as $param) : ?>
                            <tr>
                                <td><label for="import_settings__cms__user_identifiers_<?= $param ?>"><?= htmlReady($param) ?></label></td>
                                <td>
                                    <select id="import_settings__cms__user_identifiers_<?= $param ?>" name="data[import_settings][cms][user_identifiers][<?= $param ?>]">
                                        <option value=""> - </option>
                                        <option value="username"<?= $server['data']['import_settings']['cms']['user_identifiers'][$param] === "username" ? " selected" : "" ?>>username</option>
                                        <option value="email"<?= $server['data']['import_settings']['cms']['user_identifiers'][$param] === "email" ? " selected" : "" ?>><?= _("Email-Adresse") ?></option>
                                        <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                            <option value="<?= $datafield->getId() ?>"<?= $server['data']['import_settings']['cms']['user_identifiers'][$param] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady($datafield['name']) ?></option>
                                        <? endforeach ?>
                                    </select>
                                </td>
                            </tr>
                        <? endforeach ?>
                    </tbody>
                </table>
            </td>
        </tr>

        <tr class="cms_only">
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr class="cms_only">
            <td><label for="import_settings__cms__parallelgroup"><?= _("Parallelgruppen als") ?></label></td>
            <td>
                <select id="import_settings__cms__parallelgroup" name="data[import_settings][cms][parallelgroups]">
                    <option value="allinone"<?= $server['data']['import_settings']['cms']['parallelgroups'] === "allinone" ? " selected" : "" ?>><?= _("Eine Veranstaltung") ?></option>
                    <option value="many"<?= $server['data']['import_settings']['cms']['parallelgroups'] === "many" ? " selected" : "" ?>><?= _("Eine Veranstaltung pro Parallelgruppe") ?></option>
                    <option value="dozent"<?= $server['data']['import_settings']['cms']['parallelgroups'] === "dozent" ? " selected" : "" ?>><?= _("Eine Veranstaltung pro Dozent") ?></option>
                </select>
            </td>
        </tr>

        <tr>
            <td colspan="2"><hr class="grey"></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <a href="#" onClick="STUDIP.CC.participants.save_data(); return false;"><?= Studip\Button::create(_("speichern")) ?></a>
            </td>
        </tr>
    </tbody>
</table>
<br>
<br>