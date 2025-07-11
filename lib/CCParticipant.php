<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CCParticipant extends CampusConnectConfig
{

    static protected function configure($config = array())
    {
        $config['default_values']['type'] = 'participants';
        $config['default_values']['active'] = 0;
        parent::configure($config);
    }

    static function findAll() {
        return self::findBySQL("type = 'participants' ORDER BY id ASC");
    }

    static function updateParticipantsFromECS() {
        foreach (self::findBySQL("type = 'server' ORDER BY id ASC") as $ecs) {
            if (!$ecs['active']) {
                continue;
            }
            $c = new EcsClient($ecs['data']);
            $memberships = (array) $c->getMemberships()->getResult();
            $participants = self::findAll();
            $communities = CampusConnectConfig::findBySQL("type = 'community' ORDER BY id ASC");
            foreach ($participants as $p) {
                $data = $p['data'];
                $data['communities'] = array();
                $p['data'] = $data;
            }
            self::updateECSParticipants($memberships, $communities, $participants, $ecs->getId());
        }
    }

    static public function updateECSParticipants($new_data, $communities, $participants, $ecs_id) {
        if (!is_array($new_data)) {
            return false;
        }
        foreach ($participants as $key => $participant) {
            $data = $participant['data'];
            $data['mid'] = array();
            $participants[$key]['data'] = $data;
        }
        foreach ($new_data as $community_data) {
            $community_data['community'];
            $new_community = false;
            unset($community);
            foreach ($communities as $c) {
                if ($c['data']['cid'] == $community_data['community']['cid']) {
                    $community = $c;
                    break;
                }
            }
            if (empty($community)) {
                $community = new CampusConnectConfig();
                $community['type'] = "community";
                $new_community = true;
            }
            $data = array(
                'cid' => $community_data['community']['cid'],
                'name' => $community_data['community']['name'],
                'description' => $community_data['community']['description']
            );
            $community['data'] = CampusConnectHelper::rec_array_merge($community['data'], $data);
            $community->store();
            if ($new_community) {
                $communities[] = $community;
            }

            foreach((array) $community_data['participants'] as $participant_data) {
                if ($participant_data['itsyou']) {
                    continue;
                }
                unset($participant);
                $new = false;
                //Kennen wir den Teilnehmer in dieser Community schon?
                foreach ($participants as $p) {
                    $data = $p->data->getArrayCopy();
                    if (!empty($data['pid']) && $data['pid'] == $participant_data['pid']
                            && in_array($ecs_id, $data['ecs']) ) {
                        $participant = $p;
                    }
                }
                if (empty($participant)) {
                    //Kennen wir den Teilnehmer von einer anderen Community bzw. einem anderen ECS?
                    foreach ($participants as $p) {
                        if ($p['data']['name'] === $participant_data['name']) {
                            $participant = $p;
                        }
                    }
                }
                if (empty($participant)) {
                    //Wir kennen den Teilnehmer scheinbar noch gar nicht
                    $participant = new CCParticipant();
                    $new = true;
                }
                $data = array(
                    'dns' => $participant_data['dns'],
                    'name' => $participant_data['name'],
                    'pid' => $participant_data['pid'],
                    'mid' => array($community_data['community']['cid'] => $participant_data['mid']),
                    'org' => array(
                        'name' => $participant_data['org']['name'],
                        'abbr' => $participant_data['org']['abbr'],
                    ),
                    'email' => $participant_data['email'],
                    'description' => $participant_data['description'],
                    'ecs' => array($ecs_id),
                    'communities' => array($community['data']['cid'])
                );
                $data = CampusConnectHelper::rec_array_merge($participant['data'], $data);
                $participant['data'] = $data;
                $participant->store();
                if ($new) {
                    $participants[] = $participant;
                }
            }
        }
        return true;
    }


    public function getPid() {
        return $this['data']['pid'];
    }

}
