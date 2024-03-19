<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

$sem_tree_search = new SemTreeSearch();
?>

<? if (count($trees)) : ?>
<div id="mapping_trees">
    <? foreach ($trees as $tree) : ?>
    <h3><?= htmlReady($tree['title']) ?></h3>
    <div class="tree_matching_body" id="mapping_<?= htmlReady($tree['tree_id']) ?>">
        <div style="display: table; width: 100%;">
        <div style="display: table-row; width: 100%;">
            <div class="tree_mapping_tree" style="display: table-cell; width: 50%;">
                <ul>
                    <? foreach ($tree->getNodes(null) as $root_node) : ?>
                    <?= $this->render_partial("config/_tree_node", array('node' => $root_node, 'tree' => $tree)) ?>
                    <? endforeach ?>
                </ul>
            </div>
            <div class="tree_mapping_window" style="display: table-cell; width: 50%; visibility: hidden; text-align: center;">
                <input type="hidden" class="directory_id">
                <table align="center">
                    <thead>
                        <tr>
                            <th width="*"><?= _("Externes Verzeichnis") ?></th>
                            <th></th>
                            <th width="*"><?= _("Studienbereich") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="tree_mapping_directory"></span></td>
                            <td><?= Assets::img("icons/16/yellow/arr_2right", array('class' => "text-bottom", 'title' => _("Wird angehängt unter den Studienbereich"))) ?></td>
                            <td>
                                <?= QuickSearch::get("sem_tree_id", $sem_tree_search)->render() ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <a href="" class="send_tree_matching"><?= \Studip\Button::create("Mapping durchführen") ?></a>
            </div>
        </div>
        </div>
    </div>
    <? endforeach ?>
</div>
<script>jQuery("#mapping_trees").accordion({
    fillSpace: true
});</script>
<? else : ?>
<?= MessageBox::info(_("Keine importierten Verzeichnisbäume vorhanden.")) ?>
<? endif ?>
