<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
$study_area = $study_areas[$sem_tree_id];
?>
<li id="sem_tree_<?= $study_area['sem_tree_id'] ?>">
    <label>
        <?= htmlReady($study_area['name']) ?>
        <input type="checkbox" name="data[export_settings][filter_sem_tree][<?= htmlReady($study_area['sem_tree_id']) ?>]"<?= $server['data']['export_settings']['filter_sem_tree'][$study_area['sem_tree_id']] ? " checked" : "" ?> value="1">
    </label>
    <? if (count($study_area['children'])) : ?>
    <ul class="sem_tree_list">
    <? foreach ($study_area['children'] as $next_sem_tree_id) :?>
        <?= $this->render_partial("config/_export_study_area", array(
            'study_areas' => $study_areas,
            'sem_tree_id' => $next_sem_tree_id,
            'server' => $server
        )) ?>
    <? endforeach ?>
    </ul>
    <? endif ?>
</li>