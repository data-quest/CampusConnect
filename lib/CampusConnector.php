<?php

class CampusConnector {

    static public function sendChanges()
    {
        $changes = CampusConnectTriggerStack::findAll();
        $ecs = CampusConnectConfig::findByType("server");
        foreach ($ecs as $ecs_server) {
            if ($ecs_server['active']) {
                $ecs_client = new EcsClient($ecs_server['data']);
                foreach ($changes as $change) {

                    //Für jeden Kurs müssen wir pro ECS generell zwei Nachrichten absetzen:
                    //Die erste für Courselinks und die zweite für Courses.
                    switch ($change['object_type']) {
                        case "course":

                            $course = new CCCourse($change['object_id']);
                            //Courselinks:
                            $path = "/campusconnect/courselinks";
                            $receiver_participants = $course->getReceivingParticipantsForECS($ecs_server, "kurslink");
                            $message = $course->getCourselinkMessage();
                            $resource_id = $course->already_synced('kurslink');
                            if ($resource_id) {
                                $result = $ecs_client->updateResourceMessage(
                                    $path,
                                    $resource_id,
                                    $message,
                                    $receiver_participants
                                );
                                if ($result->getResponseCode() >= 400) {
                                    echo "\n".$result->getResult();
                                }
                            } elseif(count($receiver_participants)) {
                                $result = $ecs_client->createResourceMessage(
                                    $path,
                                    $message,
                                    $receiver_participants
                                );
                                $header = $result->getResponseHeader();
                                $resource_id = false;
                                foreach ($header as $key => $value) {
                                    if (strtolower($key) === "location") {
                                        if (strrpos($value, "/") !== false) {
                                            $resource_id = substr($value, strrpos($value, "/") + 1);
                                        } else {
                                            $resource_id = $value;
                                        }
                                    }
                                }
                                if ($resource_id) {
                                    $sent_item = new CampusConnectSentItem();
                                    $sent_item['item_id'] = $course->getId();
                                    $sent_item['object_type'] = "kurslink";
                                    $sent_item['resource_id'] = $resource_id;
                                    $sent_item->store();
                                }
                            }

                            //Courses:
                            $path = "/campusconnect/courses";
                            $receiver_participants = $course->getReceivingParticipantsForECS($ecs_server, "kurs");
                            $resource_id = $course->already_synced('kurslink');
                            if (count($receiver_participants)) {
                                //$message = array($course->getCourseMessage());
                                $ecs_client->createResourceMessage(
                                    $path,
                                    $course->getCourseMessage(),
                                    $receiver_participants
                                );
                                $ecs_client->createResourceMessage(
                                    "/campusconnect/course_members",
                                    $course->getCourseMemberMessage(),
                                    $receiver_participants,
                                    true //means we only send the uri-list and the other participants have to fetch the info from us.
                                );
                            }
                            break;
                        case "institut":
                            break;
                    }
                }
            }
        }
        CampusConnectTriggerStack::clear();
    }

    static public function fetchUpdates()
    {
        $ecs = CampusConnectConfig::findByType("server");
        $participants = CCParticipant::findAll();
        foreach ($ecs as $ecs_server) {
            if ($ecs_server['active']) {
                $ecs_client = new EcsClient($ecs_server['data']);
                $result_object = $ecs_client->getAndRemoveEventsFifo(10); //gibt nur einen zurück
                $i = 0;
                while (count((array) $result_object->getResult()) > 0) {
                    //get participant_id
                    foreach ((array) $result_object->getResult() as $ressource) {
                        $type = preg_split("/\//", $ressource['ressource'], -1, PREG_SPLIT_NO_EMPTY);
                        $resource_id = $type[2];
                        $type = $type[1];

                        $response = $ecs_client->getResourceMessage($ressource['ressource']);
                        $sender = $response->getSender();
                        $communities = $response->getReceiverCommunities();
                        if ($response->getResponseCode() >= 300) {
                            continue;
                        }
                        $message = $response->getResult();
                        $allowed = false;
                        //schaue für alle Teilnehmer, ob der Kurslink erlaubt ist - eine Erlaubnis reicht aus
                        //here the error occurs that $sender is empty. It should come from the X-EcsSender header.
                        foreach ($sender as $sender_key => $s) {
                            foreach ($participants as $participant) {
                                $participant_data = $participant['data']->getArrayCopy();
                                if (isset($participant_data['mid']) && in_array($s, $participant_data['mid'])) {
                                    if ($participant['active']) {
                                        $active_participant = $participant;
                                        $allowed = true;
                                    }
                                }
                            }
                        }
                        if (array_keys($message) !== range(0, count($message) - 1)) {
                            $message = array($message);
                        }
                        if (in_array($ressource['status'], array("created","updated")) && $allowed) {
                            switch ($type) {
                                case "courselinks":
                                    foreach ($message as $courselink) {
                                        CCCourse::createFromCourseLinkMessage(
                                            $courselink,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "courses":
                                    foreach ($message as $course) {
                                        $seminar_ids = CCCourse::createFromCoursesMessage(
                                            $course,
                                            $active_participant->getId()
                                        );
                                        if ($seminar_ids) {
                                            //Course-URLS zurück schicken:
                                            $seminar_urls = array_map(function ($id) {
                                                $url = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/campusconnect/courselink/to/".$id;
                                                return array(
                                                    'url' => $url,
                                                    'title' => $GLOBALS['UNI_NAME_CLEAN']." - ".CCCourse::find($id)->name . " " .(array_search($id, $seminar_ids) + 1)
                                                );
                                            }, array_keys($seminar_ids));
                                            $course_urls = array(
                                                'cms_course_id' => $course['lectureID'],
                                                'ecs_course_url' => $course['lectureID'],
                                                'lms_course_urls' => $seminar_urls
                                            );
                                            $membership_id = array_shift(array_values($active_participant['data']['mid']));
                                            $ecs_client->createResourceMessage(
                                                "campusconnect/course_urls",
                                                $course_urls,
                                                array($membership_id)
                                            );
                                            //Coursemember mit user_id
                                        }
                                    }
                                    break;
                                case "course_members":
                                    foreach ($message as $course_members) {
                                        CCCourse::createCourseMembers(
                                            $course_members,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "directory_trees":
                                    foreach ($message as $directory_tree) {
                                        CCStudyArea::createFromStudyAreaMessage(
                                            $directory_tree,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "course_urls":
                                    foreach ($message as $course_urls) {
                                        CCCourse::setCourseUrls(
                                            $course_urls,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                            }
                        } elseif($ressource['status'] === "destroyed") {
                            //Ressource holen, Item initialisieren und löschen.
                            switch ($type) {
                                case "courselinks":
                                    foreach ($message as $courselink) {
                                        CCCourse::deleteFromCourseLinkMessage(
                                            $courselink,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "courses":
                                    foreach ($message as $course) {
                                        CCCourse::deleteFromCoursesMessage(
                                            $course,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "course_members":
                                    foreach ($message as $course_members) {
                                        CCCourse::deleteCourseMembers(
                                            $course_members,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "directory_trees":
                                    foreach ($message as $directory_tree) {
                                        CCStudyArea::deleteFromStudyAreaMessage(
                                            $directory_tree,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                            }
                        } //mehr status gibt es nicht, oder?
                    }
                    $result_object = $ecs_client->getAndRemoveEventsFifo(); //weiter in der while-schleife
                    $i++;
                }
            }
        }
    }

    static public function sendEverything()
    {

//        list($way, $participant_id) = explode('_', $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING"));
//        if ($way === 'export') {
//            $participant = CampusConnectConfig::find($participant_id);
//            if ($participant && !empty($participant['data']['export_settings']) && $participant['data']['export_settings']['course_entity_type'] === 'kurslink') {
//                $export_settings = $participant['data']['export_settings'];
//                if ($export_settings['filter_sem_tree_activate']) {
//                    $sem_tree_ids = array_keys($export_settings['filter_sem_tree']);
//                    $filter->query->join('seminar_sem_tree', "seminar_sem_tree.seminar_id = seminare.Seminar_id", 'INNER JOIN');
//                    $filter->query->where('seminar_sem_tree', "seminar_sem_tree.sem_tree_id IN (:sem_tree_ids) ", [
//                        'sem_tree_ids' => $sem_tree_ids
//                    ]);
//                }
//                if ($export_settings['filter_datafields_activate']) {
//                    $datafield_id = $export_settings['filter_datafield'];
//                    $filter->query->join('datafields_entries', "datafields_entries.range_id = seminare.Seminar_id", 'INNER JOIN');
//                    $filter->query->where('datafields_entries', "datafields_entries.datafield_id = :cc_datafield_id AND datafields_entries.content != '' AND datafields_entries.content != '0' AND datafields_entries.content IS NOT NULL", [
//                        'cc_datafield_id' => $datafield_id
//                    ]);
//                }
//            }
//        } else {
//            list($way, $participant_id) = explode('_', $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING"));
//            if ($way === 'import') {
//                $filter->query->join('campus_connect_entities', "campus_connect_entities.item_id = seminare.Seminar_id AND campus_connect_entities.type = 'course'", 'INNER JOIN');
//                $filter->query->where('campus_connect_import_participants', "campus_connect_entities.participant_id = :participant_id", [
//                    'participant_id' => $participant_id
//                ]);
//            }
//        }
        $participants = CCParticipant::findBySQL('`type` = ? AND `active` = 1 ORDER BY `id` ASC ', array('participants'));




        $ecs = CampusConnectConfig::findByType("server");
        foreach ($ecs as $ecs_server) {
            if ($ecs_server['active']) {
                $ecs_client = new EcsClient($ecs_server['data']);

                foreach ($participants as $participant) {
                    if (in_array($ecs_server->id, $participant['data']['ecs']->getArrayCopy()) && !empty($participant['data']['export'])) {
                        $getCourses = SQLQuery::table('seminare');
                        if (!empty($participant['data']['export_settings']['filter_datafields_activate']) && !empty($participant['data']['export_settings']['filter_datafield'])) {
                            $getCourses->join('datafields_entries','datafields_entries', "`datafields_entries`.`range_id` = `seminare`.`Seminar_id` AND `datafields_entries`.`datafield_id` = ".DBManager::get()->quote($participant['data']['export_settings']['filter_datafield']), 'INNER JOIN');
                            $getCourses->where('datafields_entries', "`datafields_entries`.`content` = '1' ");
                        }

                        $courses = $getCourses->fetchAll(Course::class);
                        foreach ($courses as $course) {
                            //Für jeden Kurs müssen wir pro ECS generell zwei Nachrichten absetzen:
                            //Die erste für Courselinks und die zweite für Courses.

                            $course = new CCCourse($course->id);
                            //Courselinks:
                            $path = "/campusconnect/courselinks";

                            $receiver_participants = $course->getReceivingParticipantsForECS($ecs_server, "kurslink");
                            $message = $course->getCourselinkMessage();
                            $resource_id = $course->already_synced('kurslink');
                            if ($resource_id) {
                                $result = $ecs_client->updateResourceMessage(
                                    $path,
                                    $resource_id,
                                    $message,
                                    $receiver_participants
                                );
                                if ($result->getResponseCode() >= 400) {
                                    echo "\n".$result->getResult();
                                }
                            } elseif(count($receiver_participants)) {
                                $result = $ecs_client->createResourceMessage(
                                    $path,
                                    $course->getCourselinkMessage(),
                                    $receiver_participants
                                );
                                $header = $result->getResponseHeader();
                                $resource_id = strrpos($header['Location'], "/") !== false
                                    ? substr($header['Location'], strrpos($header['Location'], "/") + 1)
                                    : $header['Location'];
                                if ($resource_id) {
                                    $sent_item = new CampusConnectSentItem();
                                    $sent_item['item_id'] = $course->getId();
                                    $sent_item['object_type'] = "kurslink";
                                    $sent_item['resource_id'] = $resource_id;
                                    $sent_item->store();
                                }
                            }

                            //Courses:
                            $path = "/campusconnect/courses";
                            $receiver_participants = $course->getReceivingParticipantsForECS($ecs_server, "kurs");
                            $resource_id = $course->already_synced('kurslink');
                            if (count($receiver_participants)) {
                                //$message = array($course->getCourseMessage());
                                $ecs_client->createResourceMessage(
                                    $path,
                                    $course->getCourseMessage(),
                                    $receiver_participants
                                );
                                $ecs_client->createResourceMessage(
                                    "/campusconnect/course_members",
                                    $course->getCourseMemberMessage(),
                                    $receiver_participants,
                                    true //means we only send the uri-list and the other participants have to fetch the info from us.
                                );
                            }

                        }

                    }
                }



            }
        }
    }
}
