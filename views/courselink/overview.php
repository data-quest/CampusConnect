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

<article class="studip">
    <header><h1><?= htmlReady($course['name']) ?></h1></header>
    <table class="default nohover">
        <tbody>
            <tr>
                <td><?= _('Standort') ?></td>
                <td><?= htmlReady($coursedata->participant['data']['name']) ?></td>
            </tr>
            <? if (!empty($coursedata['data']['lecturers'])) : ?>
            <tr>
                <td><?= _("Dozenten") ?></td>
                <td>
                    <ul class="clean">
                    <? foreach ($coursedata['data']['lecturers'] as $key => $lecturer) : ?>
                        <li><?= htmlReady($lecturer['firstName']." ".$lecturer['lastName']) ?></li>
                    <? endforeach ?>
                    </ul>
                </td>
            </tr>
            <? endif ?>
            <tr>
                <td><?= _("Veranstaltungstyp") ?></td>
                <? $semType = $course->getSemType() ?>
                <td><?= htmlReady($coursedata['data']['courseType'] ?? $semType['name']) ?></td>
            </tr>
            <tr>
                <td><?= _("Semester") ?></td>
                <td>
                    <? $term = !empty($coursedata['data']['term']) ? $coursedata['data']['term'] : $course->start_semester['name'] ?>
                    <?= htmlReady($term) ?>
                    <? if (($term !== $course->start_semester['name']) && $course->start_semester['name']) : ?>
                    (<?= sprintf(_("%s in Stud.IP"), htmlReady($course->start_semester['name'])) ?>)
                    <? endif ?>
                </td>
            </tr>
            <? if (!empty($coursedata['data']['number'])) : ?>
                <tr>
                    <td><?= _("Veranstaltungsnummer") ?></td>
                    <td><?= htmlReady($coursedata['data']['number']) ?></td>
                </tr>
            <? endif ?>
            <? if (!empty($coursedata['data']['abstract'])) : ?>
                <tr>
                    <td><?= _("Beschreibung") ?></td>
                    <td><?= htmlReady($coursedata['data']['abstract']) ?></td>
                </tr>
            <? endif ?>
            <? if (!empty($coursedata['data']['hoursPerWeek'])) : ?>
            <tr>
                <td><?= _("Wochenstunden") ?></td>
                <td><?= htmlReady($coursedata['data']['hoursPerWeek']) ?></td>
            </tr>
            <? endif ?>
            <? if (!empty($coursedata['data']['credits'])) : ?>
            <tr>
                <td><?= _("ECTS-Punkte") ?></td>
                <td><?= htmlReady($coursedata['data']['credits']) ?></td>
            </tr>
            <? endif ?>
            <? if (count($course->members) > 1) : ?>
            <tr>
                <td><?= htmlReady(_("Bekannte Teilnehmer")) ?></td>
                <td>
                    <ul class="clean">
                    <? foreach ($course->members as $coursemember) : ?>
                        <? if ($coursemember['user_id'] !== CCCourse::getDummyDozent()->user_id) : ?>
                        <li>
                            <a href="<?= URLHelper::getLink("about.php", array('username' => get_username($coursemember['user_id']))) ?>">
                                <?= Avatar::getAvatar($coursemember['user_id'])->getImageTag(Avatar::SMALL) ?>
                                <?= get_fullname($coursemember['user_id']) ?>
                            </a>
                        </li>
                        <? endif ?>
                    <? endforeach ?>
                    </ul>
                </td>
            </tr>
            <? endif ?>
        </tbody>
    </table>
</article>



