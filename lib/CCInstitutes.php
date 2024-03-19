<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


class CCInstitutes extends Institute
{
    static public function createFromOrganisationalUnitsMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if (!in_array($participant['data']['import_settings']['course_entity_type'], array("cms"))) {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("institute", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $institute = new CCInstitutes($mapping['item_id']);
        } else {
            $institute = CCInstitutes::findBySQL("Name = ?", array($message['title']));
            if (!$institute) {
                $institute = new CCInstitutes();
            }
        }
        $institute['Name'] = $message['title'];
        $institute['Strasse'] = $message['street'];
        $institute['Plz'] = $message['postalCode'];
        $institute['url'] = $message['href'];
        $institute['telefon'] = $message['telephone'];
        $institute['email'] = $message['email'];
        $institute['fax'] = $message['fax'];

        $institute->store();

        $mapping = new CampusConnectEntity(array($institute->getId(), "institute"));
        $mapping['foreign_id'] = $message['id'];
        $mapping['participant_id'] = $participant_id;
        $mapping['data'] = $message;
        $mapping->store();
    }
}
