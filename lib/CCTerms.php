<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CCTerms extends Semester
{
    static public function createFromTermsMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if (!in_array($participant['data']['import_settings']['course_entity_type'], array("cms"))) {
            return;
        }

        $title = $message['title'] ? $message['title'] : $message['shortTitle'];
        $mapping = CampusConnectEntity::findByForeignID("semester", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $semester = new CCTerms($mapping['item_id']);
        } else {
            $semester = CCTerms::findBySQL("name = ?", array($title));
            if (!$semester) {
                $semester = new CCTerms();
            } else {
                $semester = $semester[0];
            }
        }
        $semester['name'] = $title;
        $semester['beginn'] = strtotime($message['start']);
        $semester['ende'] = strtotime($message['end']);
        $semester['vorles_beginn'] = strtotime($message['lecturePeriodStart']);
        $semester['vorles_ende'] = strtotime($message['lecturePeriodEnd']);
        if (isset($message['longTitle'])) {
            $semester['description'] = $message['longTitle'];
        }

        //Check for overlapping semesters
        if ($semester->isNew()) {
            $db = DBManager::get();
            $statement = $db->prepare(
                "SELECT 1 " .
                "FROM semester_data " .
                "WHERE (beginn >= :beginn AND beginn < :ende) ".
                    "OR (ende > :beginn AND ende <= :ende) " .
                    "OR (beginn <= :beginn AND ende >= :ende) " .
            "");
            $statement->execute(array(
                'beginn' => $semester['beginn'],
                'ende' => $semester['ende']
            ));
            $overlapping = $statement->fetch(PDO::FETCH_COLUMN, 0);
            if ($overlapping) {
                throw new Exception("Imported semester overlaps with existent semester.");
            }
        }

        $semester->store();

        $mapping = new CampusConnectEntity(array($semester->getId(), "semester"));
        $mapping['foreign_id'] = $message['id'];
        $mapping['participant_id'] = $participant_id;
        $mapping['data'] = $message;
        $mapping->store();
    }

    static public function deleteFromTermsMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if ($participant['data']['import_settings']['course_entity_type'] !== "cms") {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("semester", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $semester = new CCTerms($mapping['item_id']);
        }
        if ($semester) {
            $semester->delete();
        }
        $mapping->delete();
    }
}
