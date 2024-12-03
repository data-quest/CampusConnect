<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CCCourse extends Course
{
    static protected $dozent = "CampusConnectDummyDozent";

    /**
     * Creates or updates a course by a courselink-message.
     * @param type $message
     * @param string $participant: ID of the participant to identify courses by their id.
     */
    static public function createFromCourseLinkMessage($message, $participant_id)
    {
        //if we import courselinks
        $participant = new CCParticipant($participant_id);
        if ($participant['data']['import_settings']['course_entity_type'] !== "kurslink") {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("course", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $course = new CCCourse($mapping['item_id']);
        } else {
            $course = new CCCourse();
        }
        $course['ects'] = $message['credits'] != -1 ? $message['credits'] : "";
        $course['name'] = $message['title'];
        $course['VeranstaltungsNummer'] = $message['number'];
        $course['status'] = self::getCourselinkSemType($message['courseType'], $participant_id);
        $course->study_areas = self::getStudyAreas($message['degreeProgrammes'], $participant_id);
        $course['Institut_id'] = self::getInstitut($participant_id);
        $course->start_semester = self::getSemester($message);
        if ($course->isNew()) {
            $dozent = self::getDummyDozent();
            $course->members[] = $dozent;
            //Semester
            $course['duration_time'] = 0;
        }
        $course->store();

        //termine
        self::createDates($course, (array) $message['datesAndVenues']);

        if ($message['avatar'] && (stripos($message['avatar'], "http") === 0)) {
            $file_content = file_get_contents($message['avatar']);
            if ($file_content) {
                $tmp_file = $GLOBALS['TMP_PATH']."/".md5(uniqid());
                file_put_contents($tmp_file, $file_content);
                CourseAvatar::getAvatar($course->getId())->createFrom($tmp_file);
                @unlink($tmp_file);
            }
        }

        $mapping = new CampusConnectEntity(array($course->getId(), "course"));
        $mapping['foreign_id'] = $message['id'];
        $mapping['participant_id'] = $participant_id;
        $mapping['data'] = $message;
        $mapping->store();

        return $course->getId();
    }

    static public function deleteFromCourseLinkMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if ($participant['data']['import_settings']['course_entity_type'] !== "kurslink") {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("course", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $course = new CCCourse($mapping['item_id']);
        }
        if ($course) {
            $course->delete();
        }
        $mapping->delete();
    }

    static public function deleteFromCourseMessage($message, $participant_id) {
        $participant = new CCParticipant($participant_id);
        if ($participant['data']['import_settings']['course_entity_type'] !== "kurs") {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("course", $message['id'], $participant_id);
        if ($mapping['item_id']) {
            $course = new CCCourse($mapping['item_id']);
        }
        if ($course) {
            $course->delete();
        }
        $mapping->delete();
    }


    static public function createCourse($message, $title, $id, $groups, $group_id, $participant_id)
    {
        $participant = new CCParticipant($participant_id);
        if (!in_array($participant['data']['import_settings']['course_entity_type'], array("kurs"))) {
            return;
        }

        $mapping = CampusConnectEntity::findByForeignID("course", $id, $participant_id);
        if ($mapping['item_id']) {
            $course = new CCCourse($mapping['item_id']);
        } else {
            $course = new CCCourse();
        }
        $course['ects'] = $message['credits'];
        $course['name'] = $title;

        $course['VeranstaltungsNummer'] = $message['number'];
        $course['status'] = self::getCourselinkSemType($message['courseType'], $participant_id);
        //$course[] = $message['recommendedReading'];
        //$course[] = $message['linkToCurriculum'];
        //$course[] = $message['comment1']; .. comment3
        //Datenfelder hierfür verwenden?
        $course['vorrausetzungen'] = $message['prerequisites'];
        $course['leistungsnachweis'] = $message['courseAssessmentMethod'];
        $course['Beschreibung'] = $message['courseTopics'];
        $course['teilnehmer'] = $message['targetAudiences'];

        $institute = self::getInstitutes($message['organisationalUnits'], $participant_id);
        $course['Institut_id'] = $institute[0];
        $course->start_semester = self::getSemester($message);
        if ($course->isNew()) {
            $dozent = self::getDummyDozent();
            $course->members[] = $dozent;
            //Semester
            $course['duration_time'] = 0;
        }
        //allocations? Welcher Baum ist das?
        //links? In Freie Informationsseite?
        //linkToCourse?
        //modules?
        $course->store();

        //Statusgruppen
        self::createStatusgruppen($course, $groups, $message, $participant_id);

        //termine
        self::createDates($course, (array) $message['datesAndVenues']);

        self::syncCourseStudyAreas($course, (array) $message['degreeProgrammes'], $participant_id);

        if ($message['avatar']) {
            $file_content = file_get_contents($message['avatar']);
            if ($file_content) {
                $tmp_file = $GLOBALS['TMP_PATH']."/".md5(uniqid());
                file_put_contents($tmp_file, $file_content);
                CourseAvatar::getAvatar($course->getId())->createFrom($tmp_file);
                @unlink($tmp_file);
            }
        }

        $mapping = new CampusConnectEntity(array($course->getId(), "course"));
        $mapping['foreign_id'] = $id;
        $mapping['participant_id'] = $participant_id;
        $message['statusgruppen'] = $groups;
        $mapping['data'] = $message;
        $mapping->store();

        return array($course->getId() => $group_id ? $group_id : 1);
    }

    static public function createStatusgruppen($course, $groups, $message, $participant_id) {
        $neue_gruppen_ids = array();
        $db = DBManager::get();
        foreach ((array) $groups as $group) {
            $mapping = CampusConnectEntity::findByForeignID("statusgruppe", $message['lectureID']."-".$group['id'], $participant_id);
            if ($mapping['item_id']) {
                $statusgruppe = new CCStatusgruppe($mapping['item_id']);
            } else {
                $statusgruppe = new CCStatusgruppe();
                $statusgruppe['range_id'] = $course->getId();
                $statusgruppe['name'] = $group['title']." - ".$group['id'];
            }
            $statusgruppe->store();

            $mapping = new CampusConnectEntity(array($statusgruppe->getId(), "statusgruppe"));
            $mapping['foreign_id'] = $message['lectureID']."-".$group['id'];
            $mapping['participant_id'] = $participant_id;
            $mapping['data'] = $group;
            $mapping->store();

            $neue_gruppen_ids[] = $statusgruppe->getId();
        }
        //alle Statusgruppen finden und die ungebrauchten l�schen:
        $gruppen_ids = $db->query(
                "SELECT statusgruppen.statusgruppe_id " .
                "FROM statusgruppen " .
                    "INNER JOIN campus_connect_entities ON (statusgruppen.statusgruppe_id = campus_connect_entities.item_id AND campus_connect_entities.type = 'statusgruppe') " .
                "WHERE campus_connect_entities.participant_id = ".$db->quote($participant_id)." " .
                    "AND statusgruppen.range_id = ".$db->quote($course->getId())." " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach (array_diff($gruppen_ids, $neue_gruppen_ids) as $delete_gruppe_id) {
            $gruppe = new CCStatusgruppe($delete_gruppe_id);
            $gruppe->delete();
        }
    }

    static public function getSemester($message)
    {
        //Erster Versuch
        if ($message['term']) {
            $semester = Semester::findBySQL("name = ?", array($message['term']));
            if ($semester[0]) {
                return $semester[0];
            }
        }
        if ($message['datesAndVenues']) {
            $countdates = 0;
            $datetimestamp = 0;
            foreach ($message['datesAndVenues'] as $date) {
                if ($date['firstDate']['startDatetime']) {
                    $countdates++;
                    $datetimestamp += strtotime($date['firstDate']['startDatetime']);
                }
                if ($date['lastDate']['startDatetime']) {
                    $countdates++;
                    $datetimestamp += strtotime($date['lastDate']['startDatetime']);
                }
            }
            if ($datetimestamp > 0) {
                $datetimestamp = (int) floor($datetimestamp / $countdates);
                $semester = Semester::findByTimestamp($datetimestamp);
                if ($semester) {
                    return $semester;
                }
            }
        }
        $semester = Semester::findNext();
        return $semester ? $semester : Semester::findCurrent();
    }

    /**
     * Creates single-dates for incoming datesAndVenues-structure if it has
     * attributes firstDate and lastDate. No seminar_cycle_dates will be generated
     * because both structures are slightly incompatible.
     * Old dates will be deleted only if they had been imported by CampusConnect
     * in the first place.
     * @param Course $course
     * @param array of arrays $datesAndVenues
     */
    static public function createDates(Course $course, $datesAndVenues)
    {
        $time = time();
        $marker1 = "CampusConnect";
        $marker2 = "CampusConnect_todo";
        $db = DBManager::get();
        $db->exec(
            "UPDATE termine " .
            "SET content = ".$db->quote($marker2)." " .
            "WHERE range_id = ".$db->quote($course->getId())." " .
                "AND content = ".$db->quote($marker1)." " .
        "");
        foreach ($datesAndVenues as $date) {
            //Problem metadates!! Die seminar_cycle_dates sind leider nicht
            //m�chtig genug, um alle eingehenden regelm��igen Termine zu umfassen.
            //In Stud.IP gehen cycle_dates immer �ber die gesamte Laufzeit der
            //Veranstaltung.
            //Deswegen wird einfach alles als Einzeltermin gespeichert.
            $first = array(
                (int) strtotime($date['firstDate']['startDatetime']),
                (int) strtotime($date['firstDate']['endDatetime'])
            );
            $last = array(
                (int) strtotime($date['lastDate']['startDatetime']),
                (int) strtotime($date['lastDate']['endDatetime'])
            );
            if (is_numeric($date['cycle']) && $date['cycle'] > 1) {
                switch ($date['cycle']) {
                    case "2": //t�glich
                        $factor = 86400;
                        break;
                    case "3": //w�chentlich
                        $factor = 86400 * 7;
                        break;
                    default:
                        if ($date['cycle'] >= 4) {
                            //mehrw�chentlich
                            $factor = 86400 * 7 * ($date['cycle'] - 2);
                        }
                        break;
                }
                if ($factor) {
                    for ($i = 1; $i * $factor + $first[0] < $last[0]; $i++) {
                        $middle_start = $i * $factor + $first[0];
                        $middle_end = $i * $factor + $first[1];
                        self::createDate($course, $middle_start, $middle_end, $marker1);
                    }
                }
            }
            self::createDate($course, $first[0], $first[1], $marker1);
            self::createDate($course, $last[0], $last[1], $marker1);
        }
        $db->exec(
            "DELETE FROM termine " .
            "WHERE range_id = ".$db->quote($course->getId())." " .
                "AND content = ".$db->quote($marker2)." " .
        "");
    }

    /**
     * Creates singledate.
     * @param Course $course
     * @param int $date_start
     * @param int $date_end
     * @param type $marker: marker for termine.content to identify imported dates
     */
    static public function createDate(Course $course, $date_start, $date_end, $marker) {
        if ($date_start && $date_end) {
            $db = DBManager::get();
            $termin_id = $db->query(
                "SELECT termin_id " .
                "FROM termine " .
                "WHERE range_id = ".$db->quote($course->getId())." " .
                    "AND date = ".$db->quote($date_start)." " .
                    "AND end_time = ".$db->quote($date_end)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            if (!$termin_id) {
                $user_id = $GLOBALS['user']->id ? $GLOBALS['user']->id : "__system__";
                $db->exec(
                    "INSERT INTO termine " .
                    "SET termin_id = ".$db->quote(md5(uniqid())).", " .
                        "range_id = ".$db->quote($course->getId()).", " .
                        "date = ".$db->quote($date_start).", " .
                        "end_time = ".$db->quote($date_end).", " .
                        "content = ".$db->quote($marker).", " .
                        "autor_id = ".$db->quote($user_id).", " .
                        "chdate = UNIX_TIMESTAMP(), " .
                        "mkdate = UNIX_TIMESTAMP() " .
                "");
            } else {
                $db->exec(
                    "UPDATE termine " .
                    "SET content = ".$db->quote($marker)." " .
                    "WHERE termin_id = ".$db->quote($termin_id)." " .
                "");
            }
        }
    }

    /**
     * Returns an instance of CourseMember with the user_id of the
     * CampusConnect dummy-teacher. If no dummyteacher exists, it will be created
     * as a non-visible User-object.
     * @return CourseMember
     */
    static public function getDummyDozent()
    {
        $dozent = new User(md5(self::$dozent));
        if ($dozent->isNew()) {
            $dozent['username'] = self::$dozent;
            $dozent['Email'] = self::$dozent."@localhost";
            $dozent['password'] = "";
            $dozent['locked'] = 1;
            $dozent['Vorname'] = "CampusConnect";
            $dozent['Nachname'] = "DummyDozent";
            $dozent['perms'] = "dozent";
            $dozent['visible'] = "never";
            $dozent->store();
        }
        $coursemember = new CourseMember();
        $coursemember['user_id'] = $dozent->getId();
        $coursemember['status'] = "dozent";
        $coursemember['position'] = 1;
        return $coursemember;
    }

    /**
     * Returns the correct sem_type-id (status) for a course-type of
     * the foreign system. This is either a by the configuration matched
     * sem_type or the default-sem_type of the participant.
     * @param string $foreign_course_type
     * @param string $participant_id
     * @return integer : sem_type id
     */
    static public function getCourselinkSemType($foreign_course_type, $participant_id)
    {
        $participant_info = new CCParticipant($participant_id);
        $import_settings = $participant_info['data']['import_settings'];
        if (isset($import_settings['sem_type_matching'][$foreign_course_type])) {
            return $import_settings['sem_type_matching'][$foreign_course_type];
        } else {
            return $import_settings['default_sem_type'];
        }
    }

    /**
     * Returns the institut_id of the main institute to which all courselinks
     * coming from the given participant should be appended.
     * @param string $participant_id
     * @return string : md5 institut_id
     */
    static public function getInstitut($participant_id)
    {
        $participant_info = new CCParticipant($participant_id);
        return $participant_info['data']['import_settings']['institute'];
    }

    static public function getInstitutes($organisationalUnits, $participant_id) {
        $institut_ids = array();
        if (is_array($organisationalUnits)) {
            foreach ($organisationalUnits as $unit) {
                $mapping = CampusConnectEntity::findByForeignID("institute", $unit['id'], $participant_id);
                if ($mapping) {
                    $institut_ids[] = $mapping['item_id'];
                } else {
                    $inst = Institute::findBySQL("Name = ?", array($unit['title']));
                    if (count($inst)) {
                        $institut_ids[] = $inst['Institut_id'];
                    }
                }
            }
        }
        return $institut_ids;
    }

    /**
     * Returns an array of sem_tree_ids for an array-structure of courselink-degreeProgrammes
     * @param type $degreeProgrammes
     * @param type $participant_id
     * @return array of StudipStudyArea
     */
    static public function getStudyAreas($degreeProgrammes, $participant_id)
    {
        $participant_info = new CCParticipant($participant_id);
        $sem_tree = array();
        if ($participant_info['data']['import_settings']['dynamically_add_semtree'] && count($degreeProgrammes) > 0) {
            foreach ($degreeProgrammes as $study_area_info) {
                $mapped = CampusConnectEntity::findByForeignID("studycourse", $study_area_info['code'], $participant_id);
                if ($mapped) {
                    $sem_tree[] = new StudipStudyArea($mapped['item_id']);
                } else {
                    //erzeugen
                    $new_sem_tree = new StudipStudyArea();
                    $new_sem_tree['name'] = $study_area_info['title'];
                    $new_sem_tree['parent_id'] = $participant_info['data']['import_settings']['sem_tree'];
                    $new_sem_tree['info'] = "";
                    $new_sem_tree['type'] = 0;
                    $new_sem_tree->store();
                    $sem_tree[] = $new_sem_tree;

                    $mapped = new CampusConnectEntity(array($new_sem_tree->getId(), "studycourse"));
                    $mapped['foreign_id'] = $study_area_info['code'];
                    $mapped['participant_id'] = $participant_id;
                    $mapped['data'] = $study_area_info;
                    $mapped->store();
                }
            }
        } else {
            $sem_tree[] = new StudipStudyArea($participant_info['data']['import_settings']['sem_tree']);
        }
        return $sem_tree;
    }

    static public function syncCourseStudyAreas($course, $degreeProgrammes, $participant_id)
    {
        $participant_info = new CCParticipant($participant_id);
        $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$course['status']]['class']];
        $db = DBManager::get();
        if ($sem_class['bereiche']) {
            $old_matchings = $db->query(
                "SELECT campus_connect_tree_items.item_id " .
                "FROM campus_connect_tree_items " .
                    "INNER JOIN seminar_sem_tree ON (campus_connect_tree_items.mapped_sem_tree_id = seminar_sem_tree.sem_tree_id) " .
                "WHERE seminar_sem_tree.seminar_id = ".$db->quote($course->getId())." " .
            "")->fetchAll(PDO::FETCH_COLUMN, 0);
            $new_matchings = array_map(function($param) { return $param['id']; }, $degreeProgrammes);
            foreach (array_diff($old_matchings, $new_matchings) as $degreeprogram) {
                $tree_item = new CampusConnectTreeItems(array($degreeprogram['id'], $participant_id));
                if ($tree_item['mapped_sem_tree_id']) {
                    $db->exec(
                        "DELETE FROM seminar_sem_tree " .
                        "WHERE seminar_id = ".$db->quote($course->getId())." " .
                            "AND sem_tree_id = ".$db->quote($tree_item['mapped_sem_tree_id'])." " .
                    "");
                }
            }
            foreach (array_diff($new_matchings, $old_matchings) as $tree_item_id) {
                $tree_item = new CampusConnectTreeItems(array($tree_item_id, $participant_id));
                if ($tree_item['mapped_sem_tree_id']) {
                    $db->exec(
                        "INSERT IGNORE INTO seminar_sem_tree " .
                        "SET seminar_id = ".$db->quote($course->getId()).", " .
                            "sem_tree_id = ".$db->quote($tree_item['mapped_sem_tree_id'])." " .
                    "");
                }
            }
            //Notfall-sem_tree-Eintrag hinzuf�gen, falls n�tig?
            if (count($new_matchings) === 0 && $participant_info['data']['import_settings']['sem_tree']) {
                $db->exec(
                    "INSERT IGNORE INTO seminar_sem_tree " .
                    "SET seminar_id = ".$db->quote($course->getId()).", " .
                        "sem_tree_id = ".$db->quote($participant_info['data']['import_settings']['sem_tree'])." " .
                "");
            }
        } else {
            $db->exec(
                "DELETE FROM seminar_sem_tree " .
                "WHERE seminar_id = ".$db->quote($course->getId())." " .
            "");
        }
    }

    /**
     * returns the member_ids of all the receiving participants
     * @return array : array($member_id1, $member_id2, ...)
     */
    public function getReceivingParticipantsForECS($ecs, $type = "kurslink")
    {
        $campus_connect_course = new CampusConnectEntity(array($this->getId(), "course"));
        if (!$campus_connect_course->isNew()) {
            return array();
        }
        $participants = CampusConnectConfig::findByType("participants");
        $receivers = array();
        foreach ($participants as $participant) {
            if ($participant['active'] && in_array($ecs['id'], $participant['data']['ecs']->getArrayCopy())) {
                $export_settings = $participant['data']['export_settings'];
                $export = true;
                if ($export_settings['course_entity_type'] !== $type) {
                    $export = false;
                }
                if ($export_settings['filter_sem_tree_activate']) {
                    $prefalse = false;
                    foreach ($this->study_areas as $studyarea) {
                        if ($export_settings['filter_sem_tree'][$studyarea->getId()]) {
                            $prefalse = true;
                        }
                    }
                    if (!$prefalse) {
                        $export = false;
                    }
                }
                if ($export_settings['filter_datafields_activate']) {
                    foreach ($this->datafields as $datefield_entry) {
                        if (($datefield_entry['datafield_id'] === $export_settings['filter_datafield']) && !$datefield_entry['content']) {
                            $export = false;
                            break;
                        }
                    }
                }
                if ($export) {
                    //TODO: nicht die erste mid zur�ckgeben, sondern konfigurierbar
                    //anhand der Datenfelder/Filterkriterien machen.
                    foreach ((array) $participant['data']['mid'] as $cid => $mid) {
                        $receivers[] = $mid;
                        break;
                    }
                }
            }
        }
        $receivers = array_unique($receivers);
        return $receivers;
    }

    /**
     * Returns an object of class stdClass that fits as a courselink-message
     * @return array : courselink-message of that course
     */
    public function getCourselinkMessage()
    {
        $db = DBManager::get();
        $dozenten = $db->query(
            "SELECT auth_user_md5.Vorname AS firstName, auth_user_md5.Nachname AS lastName " .
            "FROM auth_user_md5 " .
                "INNER JOIN seminar_user ON (auth_user_md5.user_id =  seminar_user.user_id AND seminar_user.status = 'dozent') " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this->getId())." " .
            "ORDER BY seminar_user.position ASC " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $semester_name = $this->start_semester['name'];

        $study_areas = array();
        foreach($this->study_areas as $study_area) {
            $study_areas[] = array(
                'title' => $study_area['Name'] ? $study_area['Name'] : Institute::find($study_area['studip_object_id'])->Name,
                'code' => $study_area->getId()
            );
        }

        $datesAndVenues = array();
        $metatermine = $db->query(
            "SELECT seminar_cycle_dates.*, MIN(termine.date) AS first_start_time, MIN(termine.end_time) AS first_end_time, MAX(termine.date) AS last_start_time, MAX(termine.end_time) AS last_end_time " .
            "FROM seminar_cycle_dates " .
                "INNER JOIN termine ON (termine.metadate_id = seminar_cycle_dates.metadate_id) " .
            "WHERE seminar_cycle_dates.seminar_id = ".$db->quote($this->getId())." " .
            "GROUP BY metadate_id " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $days = array("Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag","Sonntag");
        foreach ($metatermine as $metatermin) {
            $datesAndVenues[] = array(
                'day' => $days[$metatermin['weekday'] - 1],
                'start' => $metatermin['start_time']." Uhr",
                'end' => $metatermin['end_time']." Uhr",
                'cycle' => $metatermin['cycle'] + 3,
                'venue' => "", //Raumangabe
                'firstDate' => array(
                    'startDatetime' => date("c", $metatermin['first_start_time']),
                    'endDatetime' => date("c", $metatermin['first_end_time'])
                ),
                'lastDate' => array(
                    'startDatetime' => date("c", $metatermin['last_start_time']),
                    'endDatetime' => date("c", $metatermin['last_end_time'])
                )
            );
        }
        $termine = $db->query(
            "SELECT * " .
            "FROM termine " .
            "WHERE termine.range_id = ".$db->quote($this->getId())." " .
                "AND metadate_id IS NULL " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($termine as $termin) {
            if (!$firstDate) {
                $firstDate = date("j.n.Y G:i", $termin['date'])." Uhr";
            }
            $datesAndVenues[] = array(
                'day' => $days[date("w", $termin['date'])],
                'start' => date("G:i", $termin['date'])." Uhr",
                'end' => date("G:i", $termin['end_time'])." Uhr",
                'cycle' => "0",
                'venue' => "", //Raumangabe
                'firstDate' => array(
                    'startDatetime' => date("c", $termin['date']),
                    'endDatetime' => date("c", $termin['end_time'])
                ),
                'lastDate' => array(
                    'startDatetime' => date("c", $termin['date']),
                    'endDatetime' => date("c", $termin['end_time'])
                )
            );
        }

        $resource = array();
        $resource['title'] = $this['Name'];
        $resource['url'] = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/campusconnect/courselink/to/".$this->getId();
        $resource['lang'] = $GLOBALS['DEFAULT_LANGUAGE'];
        $resource['hoursPerWeek'] = 0;
        $resource['id'] = $this->getId();
        $resource['number'] = $this['VeranstaltungsNummer'];
        $resource['term'] = $semester_name;
        $ects = strpos($this['ects'], ",") !== false
            ? str_replace(",", ".", $this['ects'])
            : $this['ects'];
        if (is_numeric($ects)) {
            $resource['credits'] = intval($ects);
        } else {
            $ects = intval($ects);
            $resource['credits'] = $ects ? $ects : -1;
        }
        $resource['status'] = "online";
        $resource['courseType'] = $GLOBALS['SEM_TYPE'][$this['status']]['name'];
        $resource['firstDate'] = $firstDate ? $firstDate : "";
        $resource['datesAndVenues'] = $datesAndVenues;
        $resource['degreeProgrammes'] = $study_areas;
        $resource['lecturers'] = $dozenten;
        $resource['allocations'] = array();
        $resource['status'] = "online";
        $resource['availability'] = array(
            'status' => "online"
        );

        //additional nondocumented infos
        $resource['avatar'] = $GLOBALS['ABSOLUTE_URI_STUDIP'].CourseAvatar::getAvatar($this->getId())->getURL(Avatar::NORMAL);

        return $resource;
    }

    public function getCourseMessage()
    {
        $db = DBManager::get();
        $dozenten = $db->query(
            "SELECT auth_user_md5.Vorname AS firstName, auth_user_md5.Nachname AS lastName " .
            "FROM auth_user_md5 " .
                "INNER JOIN seminar_user ON (auth_user_md5.user_id =  seminar_user.user_id AND seminar_user.status = 'dozent') " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this->getId())." " .
            "ORDER BY seminar_user.position ASC " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $semester_name = $this->start_semester['name'];

        $study_areas = array();
        foreach((array) $this->study_areas as $study_area) {
            $study_areas[] = array(
                'title' => $study_area['Name'] ? $study_area['Name'] : Institute::find($study_area['studip_object_id'])->Name,
                'code' => $study_area->getId()
            );
            //TODO $study_area->getId() fatal error: Call to a member function getId() on a non-object
        }

        $datesAndVenues = array();
        $metatermine = $db->query(
            "SELECT seminar_cycle_dates.*, MIN(termine.date) AS first_start_time, MIN(termine.end_time) AS first_end_time, MAX(termine.date) AS last_start_time, MAX(termine.end_time) AS last_end_time " .
            "FROM seminar_cycle_dates " .
                "INNER JOIN termine ON (termine.metadate_id = seminar_cycle_dates.metadate_id) " .
            "WHERE seminar_cycle_dates.seminar_id = ".$db->quote($this->getId())." " .
            "GROUP BY metadate_id " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $days = array("Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag","Sonntag");
        foreach ($metatermine as $metatermin) {
            $datesAndVenues[] = array(
                'day' => $days[$metatermin['weekday'] - 1],
                'start' => $metatermin['start_time']." Uhr",
                'end' => $metatermin['end_time']." Uhr",
                'cycle' => $metatermin['cycle'] + 3,
                'venue' => "", //Raumangabe
                'firstDate' => array(
                    'startDatetime' => date("c", $metatermin['first_start_time']),
                    'endDatetime' => date("c", $metatermin['first_end_time'])
                ),
                'lastDate' => array(
                    'startDatetime' => date("c", $metatermin['last_start_time']),
                    'endDatetime' => date("c", $metatermin['last_end_time'])
                )
            );
        }
        $termine = $db->query(
            "SELECT * " .
            "FROM termine " .
            "WHERE termine.range_id = ".$db->quote($this->getId())." " .
                "AND metadate_id IS NULL " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($termine as $termin) {
            if (!$firstDate) {
                $firstDate = date("j.n.Y G:i", $termin['date'])." Uhr";
            }
            $datesAndVenues[] = array(
                'day' => $days[date("w", $termin['date'])],
                'start' => date("G:i", $termin['date'])." Uhr",
                'end' => date("G:i", $termin['end_time'])." Uhr",
                'cycle' => "0",
                'venue' => "", //Raumangabe
                'firstDate' => array(
                    'startDatetime' => date("c", $termin['date']),
                    'endDatetime' => date("c", $termin['end_time'])
                ),
                'lastDate' => array(
                    'startDatetime' => date("c", $termin['date']),
                    'endDatetime' => date("c", $termin['end_time'])
                )
            );
        }

        $resource = array();
        $resource['title'] = $this['Name'];
        $resource['hoursPerWeek'] = "";
        $resource['lectureID'] = $this->getId();
        $resource['number'] = $this['VeranstaltungsNummer'];
        $resource['credits'] = $this['ects'];
        $resource['term'] = $semester_name;
        $resource['courseType'] = $GLOBALS['SEM_TYPE'][$this['status']]['name'];
        $resource['groupScenario'] = 1;

        $resource['lecturers'] = $dozenten;

        //$resource['allocations'] = array();
        //$resource['url'] = $GLOBALS['ABSOLUTE_URI_STUDIP']."seminar_main.php?cid=".$this->getId()."&again=yes";
        //$resource['lang'] = $GLOBALS['DEFAULT_LANGUAGE'];
        //$resource['credits'] = $this['ects'];
        //$resource['firstDate'] = $firstDate ? $firstDate : "";
        //$resource['datesAndVenues'] = $datesAndVenues;
        //$resource['degreeProgrammes'] = $study_areas;

        //additional nondocumented infos
        $resource['avatar'] = CourseAvatar::getAvatar($this->getId())->getURL(Avatar::NORMAL);

        return $resource;
    }

    public function getCourseMemberMessage()
    {
        $db = DBManager::get();
        $members = $db->query(
            "SELECT auth_user_md5.*, seminar_user.status AS seminarstatus " .
            "FROM auth_user_md5 " .
                "INNER JOIN seminar_user ON (auth_user_md5.user_id =  seminar_user.user_id) " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this->getId())." " .
            "ORDER BY seminar_user.position ASC " .
        "")->fetchAll(PDO::FETCH_ASSOC);

        $resource = array();
        $resource['lectureID'] = $this->getId();
        $resource['title'] = $this['Name'];
        $resource['members'] = array();
        $rights = array(
            'dozent' => 0,
            'tutor' => 2,
            'autor' => 1
        );
        $groups = $db->query(
            "SELECT statusgruppe_user.user_id, statusgruppen.* " .
            "FROM statusgruppen " .
                "INNER JOIN statusgruppe_user ON (statusgruppen.statusgruppe_id = statusgruppe_user.statusgruppe_id) " .
            "WHERE statusgruppen.range_id = ".$db->quote($this->getId())." " .
            "ORDER BY statusgruppen.position ASC, statusgruppe_user.position ASC " .
        "")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
        foreach ($members as $member) {
            $member_groups = array();
            foreach ((array) $groups[$member['user_id']] as $groupmember) {
                $member_groups[] = array(
                    'num' => $groupmember['pos'],
                    'role' => $rights[$member['seminarstatus']]
                );
            }
            $resource['members'][] = array(
                'personID' => $member['user_id'],
                'role' => $rights[$member['seminarstatus']]
            );
        }

        return $resource;
    }



    static public function setCourseUrls($course_urls, $participant_id)
    {
        $participant = new CCParticipant($participant_id);
        if ($participant['data']['export_settings']['course_entity_type'] === "kurslink") {
            return;
        }
        $delete_statement = DBManager::get()->prepare(
            "DELETE FROM campus_connect_course_url " .
            "WHERE participant_id = :participant_id " .
                "AND course_url NOT IN (:urls) " .
                "AND seminar_id = :course_id " .
        "");
        $delete_statement->execute(array(
            'participant_id' => $participant_id,
            'course_id' => $course_urls['cms_lecture_id'],
            'urls' => array_map(function ($url) { return $url['url']; }, $course_urls['lms_course_urls'])
        ));

        foreach ($course_urls['lms_course_urls'] as $url) {
            $insert_statement = DBManager::get()->prepare(
                "INSERT IGNORE INTO campus_connect_course_url " .
                "SET participant_id = :participant_id, " .
                    "course_url = :url, " .
                    "seminar_id = :course_id, " .
                    "linktitle = :linktitle, " .
                    "mkdate = UNIX_TIMESTAMP() " .
            "");
            $insert_statement->execute(array(
                'course_id' => $course_urls['cms_lecture_id'],
                'participant_id' => $participant_id,
                'url' => $url['url'],
                'linktitle' => $url['title']
            ));
        }
    }

    public function getCourseUrls() {
        $get_query = DBManager::get()->prepare(
            "SELECT * " .
            "FROM campus_connect_course_url " .
            "WHERE seminar_id = :seminar_id " .
        "");
        $get_query->execute(array(
            'seminar_id' => $this->getId()
        ));
        return $get_query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function already_synced($type = "kurslink") {
        $resource = CampusConnectSentItem::findBySQL("item_id = ? AND object_type = ?", array($this->getId(), $type));
        return count($resource) ? $resource[0]['resource_id'] : false;
    }
}
