<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
$study_area = CCStudyArea::find($node['sem_tree_id']);
?>
<li id="directory_node_<?= htmlReady($node['item_id']) ?>" 
    class="tree_node<?= $node['sem_tree_id'] ? " mapped_directly" : "" ?><?= $node['mapped_sem_tree_id'] ? " mapped" : "" ?>"
    data-sem_tree_id="<?= $study_area ? htmlReady($node['sem_tree_id']) : "" ?>"
    data-sem_tree_id_title="<?= $study_area ? htmlReady($study_area->getName()) : "" ?>">
    <div class="title"><?= htmlReady($node['title']) ?></div>
    <ul>
        <? foreach ($tree->getNodes($node['item_id']) as $childnode) : ?>
        <?= $this->render_partial("config/_tree_node.php", array('tree' => $tree, 'node' => $childnode)) ?>
        <? endforeach ?>
    </ul>
</li>