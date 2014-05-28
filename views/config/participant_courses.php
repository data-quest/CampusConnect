<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

foreach ($ecs_data as $ecs) :?>
<h2><?= htmlReady($ecs['ecs']['data']['name']) ?></h2>
<div>
    <ul>
    <? foreach ($ecs['course_links'] as $result ) : ?>
        <li>
            <? var_dump($result->getResult()) ?><br>
        </li>
    <? endforeach ?>
    </ul>
</div>
<?php endforeach; ?>
