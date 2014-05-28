<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once 'lib/models/Institute.class.php';
require_once dirname(__file__)."/CampusConnectTreeItems.php";
require_once dirname(__file__)."/CampusConnectTree.php";

class CCStudyArea extends StudipStudyArea
{

    static public function createFromStudyAreaMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if (!in_array($participant['data']['import_settings']['course_entity_type'], array("cms"))) {
            return;
        }
        if (!$message['rootID']) {
            //Datensatz unbenutzbar
            return;
        }
        $tree = CampusConnectTree::findByForeignID($message['rootID'], $participant_id);
        if ($tree->isNew() || $participant['data']['import_settings']['directory_tree']['override_title']) {
            $tree['title'] = $message['directoryTreeTitle'];
            $tree['participant_id'] = $participant_id;
        }
        $node_ids = array();
        foreach ((array) $message['nodes'] as $node) {
            $virtual_tree_item = new CampusConnectTreeItems(array($node['id'], $participant_id));
            $virtual_tree_item['title'] = $node['title'];
            $virtual_tree_item['parent_id'] = $node['parent']['id'];
            $virtual_tree_item['root_id'] = $message['rootID'];
            $virtual_tree_item['data'] = $node;
            $virtual_tree_item->store();
            //Knoten eventuell schon mappen, wenn genügen Informationen da sind
            $tree->map($virtual_tree_item);
            $node_ids[] = $node['id'];
        }
        //alle nicht mehr im Baum befindlichen Knoten finden und löschen
        $old_nodes = CampusConnectTreeItems::findBySQL(
            "participant_id = ? " .
            "AND root_id = ? " .
        "", array($participant_id, $message['rootID']));
        foreach ($old_nodes as $node) {
            if (!in_array($node['item_id'], $node_ids)) {
                $node->delete();
            }
        }

        $tree['data'] = array(); //$message;
        $tree->store();
    }

    static public function deleteFromStudyAreaMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if (!in_array($participant['data']['import_settings']['course_entity_type'], array("cms"))) {
            return;
        }
        if (!$message['rootID']) {
            //Datensatz unbenutzbar
            return;
        }
        $old_nodes = CampusConnectTreeItems::findBySQL(
            "participant_id = ? " .
            "AND root_id = ? " .
        "", array($participant_id, $message['rootID']));
        foreach ($old_nodes as $node) {
            $node->delete();
        }

        //löschen
        $virtual_tree_item = new CampusConnectTreeItems(array($message['id'], $participant_id));
        $virtual_tree_item->delete();
    }

    public function delete()
    {
        $id = $this->getId();
        $success = parent::delete();
        DBManager::get()->exec("DELETE FROM seminar_sem_tree WHERE sem_tree_id = ".DBManager::get()->quote($id));
        return $success;
    }
}