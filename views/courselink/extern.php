<?php

/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<ul>
    <? foreach ($course_urls as $course_url) : ?>
    <li class="overview">
        <a href="<?= URLHelper::getLink("plugins.php/campusconnect/courselink/link", array('url' => $course_url['course_url'])) ?>"><?= htmlReady($course_url['linktitle']) ?></a>
    </li>
    <? endforeach ?>
</ul>