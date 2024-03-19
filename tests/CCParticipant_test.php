<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CCParticipant.php";

class CCParticipantTestCase extends UnitTestCase {


    function setUp()
    {
        CampusConnectLog::get()->setLogLevel(10000);
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`))");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
    }


    function test_updateECSParticipants()
    {
        $new_data = array();
        $participants = CCParticipant::findAll();
        $communities = CampusConnectConfig::findBySQL("type = 'community' ORDER BY id ASC");
        $new_data = array(
            array(
                'community' => array(
                    'name' => "Hoschis",
                    'description' => "Hoschis Beschreibung"
                ),
                'participants' => array(
                    array(
                        'name' => "Stud.IP1",
                        'itsyou' => false,
                        'org' => array(
                            'name' => "Leifos",
                            'abbr' => "LEI"
                        ),
                        'mid' => 1,
                        'description' => "",
                        'dns' => "n/a",
                        'email' => "ras@fuhse.org"
                    ),
                    array(
                        'name' => "Stud.IP2",
                        'itsyou' => true,
                        'org' => array(
                            'name' => "data-quest",
                            'abbr' => "DQ"
                        ),
                        'mid' => 2,
                        'description' => "",
                        'dns' => "n/a",
                        'email' => "fuhse@data-quest"
                    )
                )
            )
        );
        CCParticipant::updateECSParticipants($new_data, $communities, $participants, $ecs_id);
        $server = CampusConnectConfig::findBySQL("1=1");
        $this->assertEqual(count($server), 2); //Community und ein Participant
    }

}


