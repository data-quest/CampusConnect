<?php

/*
 * Copyright (C) 2012 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CCCourse.php";

class CCCourseTestCase extends UnitTestCase {

    function setUp()
    {
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_entities` (
                `item_id` varchar(256) NOT NULL,
                `type` varchar(20) NOT NULL,
                `foreign_id` varchar(64) DEFAULT NULL,
                `participant_id` int(11) NOT NULL,
                `data` text NOT NULL,
                PRIMARY KEY (`item_id`,`type`)
            ) ");
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=MyISAM");
        DBManager::get()->exec(
            "CREATE TABLE `campus_connect_course_group` (
                `cg_id` VARCHAR( 32 ) NOT NULL ,
                `Seminar_id` VARCHAR( 32 ) NOT NULL ,
                `parallelgroup_id` VARCHAR( 64 ) NULL
            )
        ");
        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_trees` (
                `tree_id` varchar(32) NOT NULL,
                `root_id` varchar(64) NOT NULL,
                `participant_id` int(11) NOT NULL,
                `title` varchar(128) NOT NULL,
                `mapping` enum('pending','all','manual') NOT NULL DEFAULT 'pending',
                `sem_tree_id` varchar(32) DEFAULT NULL,
                `data` text NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`tree_id`)
            )
        ");

        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_tree_items` (
                `item_id` varchar(64) NOT NULL,
                `participant_id` int(11) NOT NULL,
                `title` varchar(128) NOT NULL,
                `parent_id` varchar(64) NULL,
                `root_id` varchar(64) NOT NULL,
                `sem_tree_id` varchar(32) DEFAULT NULL,
                `mapped_sem_tree_id` varchar(32) DEFAULT NULL,
                `data` text NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`item_id`,`participant_id`)
            )
        ");
        DBManager::get()->exec("TRUNCATE TABLE `auth_user_md5`");
        DBManager::get()->exec("TRUNCATE TABLE `user_info`");
        DBManager::get()->exec("TRUNCATE TABLE `seminare`");
        DBManager::get()->exec("TRUNCATE TABLE `termine`");
        DBManager::get()->exec("TRUNCATE TABLE `sem_tree`");
        DBManager::get()->exec("TRUNCATE TABLE `semester_data`");
        DBManager::get()->exec("TRUNCATE TABLE `statusgruppen`");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_entities`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_course_group`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_trees`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_tree_items`");

        DBManager::get()->exec("TRUNCATE TABLE `auth_user_md5`");
        DBManager::get()->exec("TRUNCATE TABLE `user_info`");
        DBManager::get()->exec("TRUNCATE TABLE `seminare`");
        DBManager::get()->exec("TRUNCATE TABLE `termine`");
        DBManager::get()->exec("TRUNCATE TABLE `sem_tree`");
        DBManager::get()->exec("TRUNCATE TABLE `semester_data`");
        DBManager::get()->exec("TRUNCATE TABLE `statusgruppen`");
    }

    function test_getSemester() {
        $db = DBManager::get();
        $db->exec("
            INSERT INTO semester_data (semester_id, name, beginn, ende)
            VALUES
                ('12066f06c6cb5f16d82cdb19bdaee24e','Letztes Semester', UNIX_TIMESTAMP()-(86400 * 200), UNIX_TIMESTAMP()-(86400 * 100)-1 ),
                ('22066f06c6cb5f16d82cdb19bdaee24f','Dieses Semester', UNIX_TIMESTAMP()-(86400 * 100), UNIX_TIMESTAMP()+(86400 * 100) ),
                ('32066f06c6cb5f16d82cdb19bdaee24a','Naechstes Semester', UNIX_TIMESTAMP()+(86400 * 100)+1, UNIX_TIMESTAMP()+(86400 * 200) )
        ");
        Semester::getAll(true); //reset
        $message = array(
            'term' => "Letztes Semester"
        );
        $semester = CCCourse::getSemester($message);
        $this->assertEqual($semester->getId(), '12066f06c6cb5f16d82cdb19bdaee24e');
        $message['term'] = "hutzibutzi";
        $semester = CCCourse::getSemester($message);
        $this->assertEqual($semester->getId(), '32066f06c6cb5f16d82cdb19bdaee24a');
        $message['term'] = "Letztes Semester";
        $semester = CCCourse::getSemester($message);
        $this->assertEqual($semester->getId(), '12066f06c6cb5f16d82cdb19bdaee24e');

        unset($message['term']);
        $message['datesAndVenues'] = array(
            array(
                'firstDate' => array(
                    'startDatetime' => date("c", time() - (86400 * 180)),
                    'endDatetime' => date("c", time() - (86400 * 180) + 60 * 60 * 2)
                ),
                'lastDate' => array(
                    'startDatetime' => date("c", time() + (86400 * 120)),
                    'endDatetime' => date("c", time() + (86400 * 120) + 60 * 60 * 2)
                )
            )
        );
        $semester = CCCourse::getSemester($message);
        $this->assertEqual($semester->getId(), "22066f06c6cb5f16d82cdb19bdaee24f");
    }

    function test_createFromCourseLinkMessage() {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM seminare")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $message = array(
            'id' => "2",
            'credits' => "3",
            'number' => "5",
            'title' => "Test 4",
            'number' => "5",
            'degreeProgrammes' => array(),
            'datesAndVenues' => array()
        );
        $participant = new CCParticipant();
        $participant->store();
        CCCourse::createFromCourseLinkMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM seminare")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $participant['data'] = array('import_settings' => array('course_entity_type' => "kurslink"));
        $participant->store();
        CCCourse::createFromCourseLinkMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM seminare")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
    }





    function test_createFromCoursesMessage_dozent() {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM seminare")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $message = array(
            'lectureID' => "23",
            'title' => "Test 5",
            'number' => "7",
            'lecturers' => array(
                array('firstName' => "bla1", 'lastName' => "bla1"),
                array('firstName' => "bla1", 'lastName' => "bla2")
            ),
            'degreeProgrammes' => array(),
            'datesAndVenues' => array(),
            'groupScenario' => 3,
            'groups' => array(
                array(
                    'id' => 1,
                    'lecturers' => array(
                        array('firstName' => "bla1", 'lastName' => "bla1")
                    )
                ),
                array(
                    'id' => 2,
                    'lecturers' => array(
                        array('firstName' => "bla1", 'lastName' => "bla1")
                    )
                ),
                array(
                    'id' => 3,
                    'lecturers' => array(
                        array('firstName' => "bla1", 'lastName' => "bla2")
                    )
                )
            )
        );
        $participant = new CCParticipant();
        $data = array();
        $data['import_settings']['cms']['parallelgroups'] = "dozent";
        $data['import_settings']['course_entity_type'] = "cms";
        $participant['data'] = $data;
        $participant->store();
        CCCourse::createFromCoursesMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM seminare")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 2);
        $anzahl = $db->query("SELECT COUNT(*) FROM statusgruppen")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 3);
    }

    function test_getStudyAreas() {
        $db = DBManager::get();
        $db->exec(
            "INSERT INTO sem_tree " .
            "SET name = 'Yeah', " .
                "sem_tree_id = MD5('Yeah'), " .
                "parent_id = 'root' " .
        "");
        //Participant erzeugen:
        $participant = new CCParticipant();
        $data = array();
        $data['import_settings']['sem_tree'] = md5("Yeah");
        $data['import_settings']['dynamically_add_semtree'] = false;
        $participant['data'] = $data;
        $participant->store();

        $degreeProgrammes = array(
            array(
                'title' => "Erster Studienbereich",
                'code' => "1"
            ),
            array(
                'title' => "Zweiter Studienbereich",
                'code' => "2"
            )
        );

        $sem_tree = CCCourse::getStudyAreas($degreeProgrammes, $participant->getId());
        $this->assertEqual(count($sem_tree), 1);
        $count = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 1);

        $data['import_settings']['dynamically_add_semtree'] = true;
        $participant['data'] = $data;
        $participant->store();
        $sem_tree = CCCourse::getStudyAreas($degreeProgrammes, $participant->getId());
        $this->assertEqual(count($sem_tree), 2);
        $count = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 3);

        $degreeProgrammes = array(
            array(
                'title' => "Erster Studienbereich",
                'code' => "1"
            ),
            array(
                'title' => "Dritter Studienbereich",
                'code' => "3"
            )
        );
        $sem_tree = CCCourse::getStudyAreas($degreeProgrammes, $participant->getId());
        $this->assertEqual(count($sem_tree), 2);
        $count = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 4);
    }

    function test_getCourselinkSemType()
    {
        $participant = new CCParticipant();
        $data = array();
        $data['import_settings']['sem_type_matching'] = array(
            'Seminar' => 8,
            'bla' => 9
        );
        $data['import_settings']['default_sem_type'] = 7;
        $participant['data'] = $data;
        $participant->store();

        $status = CCCourse::getCourselinkSemType("blubb", $participant->getId());
        $this->assertEqual($status, 7);

        $status = CCCourse::getCourselinkSemType("Seminar", $participant->getId());
        $this->assertEqual($status, 8);

        $status = CCCourse::getCourselinkSemType("bla", $participant->getId());
        $this->assertEqual($status, 9);
    }

    function test_getInstitut()
    {
        $participant = new CCParticipant();
        $data = array();
        $data['import_settings']['institute'] = md5("yeah");
        $participant['data'] = $data;
        $participant->store();

        $institut_id = CCCourse::getInstitut($participant->getId());
        $this->assertEqual($institut_id, md5("yeah"));
    }

    function test_getDummyDozent()
    {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM auth_user_md5")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);
        $dozent = CCCourse::getDummyDozent();
        $this->assertIsA($dozent, "CourseMember");
        $anzahl = $db->query("SELECT COUNT(*) FROM auth_user_md5")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        $dozent = CCCourse::getDummyDozent();
        $this->assertIsA($dozent, "CourseMember");
        $this->assertEqual($dozent['status'], "dozent");
        $anzahl = $db->query("SELECT COUNT(*) FROM auth_user_md5")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
    }

    function test_createDates() {
        $course = new CCCourse();
        $course['name'] = "bla";
        $course->store();

        $db = DBManager::get();
        $count = $db->query(
            "SELECT COUNT(*) FROM termine " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 0);
        $datesAndVenues = array(
            array(),
            array()
        );
        CCCourse::createDates($course, $datesAndVenues);
        $count = $db->query(
            "SELECT COUNT(*) FROM termine " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 0);
        $datesAndVenues[] = array(
            'firstDate' => array('startDatetime' => "2011-02-19T08:00:00+01:00", 'endDatetime' => "2011-02-19T10:00:00+01:00"),
            'lastDate' => array()
        );
        CCCourse::createDates($course, $datesAndVenues);
        $count = $db->query(
            "SELECT COUNT(*) FROM termine " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($count, 1);

    }

    function test_getCourselinkMessage()
    {
        //Kurs erzeugen:
        $course = new CCCourse();
        $course['name'] = "Test-Lehrveranstaltung";
        $course['status'] = 7;
        $course['ects'] = "23 Punkte";
        $course['beschreibung'] = "";
        $course->store();

        $message = $course->getCourselinkMessage();
        $this->assertEqual($message['title'], "Test-Lehrveranstaltung");
        $this->assertIsA($message['credits'], "integer");
        $this->assertEqual($message['credits'], 23); //wandelt sogar "23 Punkte" in korrektes Integer um

        $course['ects'] = "";
        $course->store();
        $message = $course->getCourselinkMessage();
        $this->assertEqual($message['credits'], -1);

        $course['ects'] = "klumpatsch";
        $course->store();
        $message = $course->getCourselinkMessage();
        $this->assertEqual($message['credits'], -1);
    }

    function test_getReceivingParticipantsForECS()
    {
        $db = DBManager::get();

        $ecs = new CampusConnectConfig();
        $ecs['type'] = "server";
        $ecs['active'] = 1;
        $ecs['id'] = 1;

        $course = new CCCourse();
        $course['name'] = "Test-Lehrveranstaltung";
        $course['status'] = 7;
        $course['beschreibung'] = "";
        $course->store();

        $campus_connect_course = new CampusConnectEntity(array($course->getId(), "course"));
        $campus_connect_course['participant_id'] = 1;
        $campus_connect_course->store();

        $participant1 = new CCParticipant();
        $participant1['active'] = 1;
        $data = array(
            'ecs' => array(1)
        );
        $data['export_settings']['course_entity_type'] = "kurslink";
        $data['mid'] = "aaa";
        $participant1['data'] = $data;
        $participant1->store();

        $participant2 = new CCParticipant();
        $participant2['active'] = 1;
        $data = array();
        $data['export'] = 1;
        $data['ecs'] = array(1);
        $participant2['data'] = $data;
        $participant2->store();

        $receiver = $course->getReceivingParticipantsForECS($ecs, $type = "kurslink");
        $this->assertIsA($receiver, "array");
        $this->assertEqual(count($receiver), 0);

        $campus_connect_course->delete(); //ab jetzt ist der Kurs keine CampusConnectEntity

        $receiver = $course->getReceivingParticipantsForECS($ecs, $type = "kurslink");
        $this->assertIsA($receiver, "array");
        $this->assertEqual(count($receiver), 1);
        $this->assertEqual($receiver[0], "aaa");
    }

    function test_createCourseMembers() {
        $this->test_createFromCoursesMessage_allinone();
        $db = DBManager::get();

        $test_autor = new User();
        $test_autor['username'] = "test_autor";
        $test_autor['perms'] = "autor";
        $test_autor->store();
        $test_autor = new User();
        $test_autor['username'] = "test_tutor";
        $test_autor['perms'] = "tutor";
        $test_autor->store();
        $test_dozent = new User();
        $test_dozent['username'] = "test_dozent";
        $test_dozent['perms'] = "dozent";
        $test_dozent->store();

        $coursemember_message = array(
            'lectureID' => "23",
            'members' => array(
                array(
                    'personID' => "test_autor",
                    'role' => 1
                )
            )
        );
        $participant = new CCParticipant(1);
        $data = array();
        $data['import_settings']['course_entity_type'] = "kurslink";
        $data['import_settings']['cms']['author_identifier'] = "username";
        $data['import_settings']['cms']['parallelgroups'] = "allinone";
        $data['import_settings']['cms']['user_identifier'] = 'username';
        $participant['data'] = $data;
        $participant->store();
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar = CampusConnectEntity::findBySQL("type = 'course' AND foreign_id = '23'");
        $seminar = $seminar[0];
        $seminar_user = $db->query("SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_user, 1); //nur der Dummydozent, weil der Participant nicht als cms importiert wird

        $data['import_settings']['course_entity_type'] = "cms";
        $participant['data'] = $data;
        $participant->store();
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar_user = $db->query("SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_user, 2); //Dummydozent und test_autor


        $coursemember_message = array(
            'lectureID' => "23",
            'members' => array(
                array(
                    'personID' => "test_autor",
                    'role' => 1
                ),
                array(
                    'personID' => "test_dozent",
                    'role' => 3
                )
            )
        );
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar_user = $db->query("SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_user, 2); //test_dozent und test_autor
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar_user = $db->query("SELECT auth_user_md5.user_id, auth_user_md5.username, status FROM seminar_user INNER JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEqual(count($seminar_user), 2); //test_dozent und test_autor

        $coursemember_message = array(
            'lectureID' => "23",
            'members' => array(
                array(
                    'personID' => "test_autor",
                    'role' => 1
                ),
                array(
                    'personID' => "test_tutor",
                    'role' => 1
                ),
                array(
                    'personID' => "test_dozent",
                    'role' => 3
                )
            )
        );
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar_user = $db->query("SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_user, 3); //test_dozent und test_autor

        //und wieder Rückgängig:
        $coursemember_message = array(
            'lectureID' => "23",
            'members' => array(
            )
        );
        CCCourse::createCourseMembers($coursemember_message, $participant->getId());
        $seminar_user = $db->query("SELECT COUNT(*) FROM seminar_user WHERE Seminar_id = ".$db->quote($seminar['item_id']))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_user, 1); //nur noch DummyDozent
    }

}


