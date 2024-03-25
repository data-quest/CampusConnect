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
<style>
    table.infobox td.infobox-img {
        background-color: white;
    }
    .infobox-img > img {
        border-top-left-radius: 16px;
        width: 250px;
    }
    #info_header {
        background-image: linear-gradient(to top, #cccccc, #fafafa, #f3f3f3);
        background-size: 100% 100%;
        padding: 1px;
    }
    #info_header h2 {
        margin-top: 3px;
        margin-bottom: 3px;
    }
    #info_header ul {
        margin: 0px;
        padding: 0px;
    }
    #info_header ul li {
        display: inline-block;
        margin-right: 3px;
    }
</style>
<div class="overview">
    <div class="overview_header"><?= htmlReady($course['name']) ?></div>
    <table>
        <tbody>
            <? if ($coursedata['data']['lecturers']) : ?>
            <tr>
                <td><?= _("Dozenten") ?></td>
                <td>
                    <ul>
                    <? foreach ($coursedata['data']['lecturers'] as $key => $lecturer) : ?>
                        <li><?= htmlReady($lecturer['firstName']." ".$lecturer['lastName']) ?></li>
                    <? endforeach ?>
                    </ul>
                </td>
            </tr>
            <? endif ?>
            <tr>
                <td><?= _("Veranstaltungstyp") ?></td>
                <td><?= htmlReady($coursedata['data']['courseType']) ?></td>
            </tr>
            <tr>
                <td><?= _("Semester") ?></td>
                <td>
                    <? $term = $coursedata['data']['term'] ? $coursedata['data']['term'] : $course->start_semester['name'] ?>
                    <?= htmlReady($term) ?>
                    <? if (($term !== $course->start_semester['name']) && $course->start_semester['name']) : ?>
                    (<?= sprintf(_("%s in Stud.IP"), htmlReady($course->start_semester['name'])) ?>)
                    <? endif ?>
                </td>
            </tr>
            <? if ($coursedata['data']['number']) : ?>
            <tr>
                <td><?= _("Veranstaltungsnummer") ?></td>
                <td><?= htmlReady($coursedata['data']['number']) ?></td>
            </tr>
            <? endif ?>
            <? if ($coursedata['data']['hoursPerWeek']) : ?>
            <tr>
                <td><?= _("Wochenstunden") ?></td>
                <td><?= htmlReady($coursedata['data']['hoursPerWeek']) ?></td>
            </tr>
            <? endif ?>
            <? if ($coursedata['data']['credits']) : ?>
            <tr>
                <td><?= _("ECTS-Punkte") ?></td>
                <td><?= htmlReady($coursedata['data']['credits']) ?></td>
            </tr>
            <? endif ?>
            <? if (count($course->members) > 1) : ?>
            <tr>
                <td><?= sprintf(_("Bekannte Teilnehmer von %s"), $GLOBALS['UNI_NAME_CLEAN']) ?></td>
                <td>
                    <ul>
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
</div>



<?

$infobox = array(
    array(
        'kategorie' => _("Information"),
        'eintrag' => array(
            array(
                'icon' => "icons/16/black/info",
                'text' => sprintf(
                            _("Diese Veranstaltung wird von einem angekoppeltem Fremdsystem angeboten. Wenn Sie hier daran angemeldet sind, werden Ihnen die Termine (sofern bekannt) in den Kalender eingetragen und Sie können über den %s direkt dorthin springen (das geht auch von der Seminarübersicht aus)."),
                            '<a href="'.URLHelper::getLink("plugins.php/campusconnect/courselink/link").'">'.Icon::create("learnmodule")->asImg(20, array('class' => "text-bottom"))." "._("Direktlink").'</a>'
                        )
            )
        )
    )
);
$avatar = CourseAvatar::getAvatar($course->getId());
$infobox = array(
    'picture' => $avatar->is_customized() ? $avatar->getURL(Avatar::NORMAL) : $assets_url . "/images/library_infobox.jpg",
    'content' => $infobox
);
