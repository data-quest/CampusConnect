<?php
/*
 * Copyright (C) 2012 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require __DIR__.'/application.php';

require_once __DIR__."/../lib/CCCourse.php";
require_once __DIR__."/../lib/CCTerms.php";
require_once __DIR__."/../lib/CCInstitutes.php";
require_once __DIR__."/../lib/CCStudyArea.php";

/**
 * Der Controller, der vom ECS angesteuert wird und die über Cronjob Änderungen
 * an den ECS übermittelt.
 */
class ConnectorController extends ApplicationController
{

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) throw new AccessDeniedException('Keine Berechtigung');
        parent::before_filter($action, $args);
    }

    function send_changes_action()
    {
        $changes = CampusConnectTriggerStack::findAll();
        $ecs = CampusConnectConfig::findByType("server");
        foreach ($ecs as $ecs_server) {
            if ($ecs_server['active']) {
                $ecs_client = new ECSClient($ecs_server['data']);
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
                            break;
                        case "institut":
                            break;
                    }
                }
            }
        }
        CampusConnectTriggerStack::clear();
        $this->render_nothing();
    }

    function receive_action()
    {
        $ecs = CampusConnectConfig::findByType("server");
        $participants = CCParticipant::findAll();
        foreach ($ecs as $ecs_server) {
            if ($ecs_server['active']) {
                $ecs_client = new ECSClient($ecs_server['data']);
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
                        foreach ($sender as $sender_key => $s) {
                            foreach ($participants as $participant) {
                                if (in_array($s, $participant['data']['mid'])) {
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
                                case "organisation_units":
                                    foreach ($message as $organisation) {
                                        CCInstitutes::createFromOrganisationalUnitsMessage(
                                            $organisation,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "terms":
                                    foreach ($message as $term) {
                                        CCTerms::createFromTermsMessage(
                                            $term,
                                            $active_participant->getId()
                                        );
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
                                case "organisation_units":
                                    foreach ($message as $organisation) {
                                        CCInstitutes::deleteFromOrganisationalUnitsMessage(
                                            $organisation,
                                            $active_participant->getId()
                                        );
                                    }
                                    break;
                                case "terms":
                                    foreach ($message as $term) {
                                        CCTerms::deleteFromTermsMessage(
                                            $term,
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
        $this->render_nothing();
    }
}

