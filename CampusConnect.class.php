<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once __DIR__.'/lib/CCParticipant.php';
require_once __DIR__.'/lib/CampusConnectLog.php';
require_once __DIR__.'/lib/CampusConnectHelper.php';
require_once __DIR__.'/lib/EcsClient.php';
require_once __DIR__.'/lib/EcsResult.php';
require_once __DIR__.'/lib/CampusConnectClient.php';
require_once __DIR__.'/lib/CampusConnectTriggerStack.php';
require_once __DIR__.'/lib/CampusConnectEntity.php';
require_once __DIR__.'/lib/CampusConnectSentItem.php';
require_once __DIR__.'/lib/CCCourse.php';
require_once __DIR__.'/lib/CCRessources.php';
require_once __DIR__.'/lib/CampusConnector.php';


class CampusConnect extends StudIPPlugin implements SystemPlugin, StandardPlugin
{

    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            /*******************************************************************
             *                       Einstellungsseiten                        *
             *******************************************************************/
            $navigation = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "config/index"));
            Navigation::addItem('/admin/campusconnect', $navigation);
            
            $navigation = new AutoNavigation("Übersicht", PluginEngine::getURL($this, array(), "config/index"));
            Navigation::addItem('/admin/campusconnect/index', $navigation);
            
            $navigation = new AutoNavigation("Teilnehmer/LMS", PluginEngine::getURL($this, array(), "config/participants"));
            Navigation::addItem('/admin/campusconnect/participants', $navigation);

            $navigation = new AutoNavigation("ECS", PluginEngine::getURL($this, array(), "config/ecs"));
            Navigation::addItem('/admin/campusconnect/ecs', $navigation);

            $navigation = new AutoNavigation("Log", PluginEngine::getURL($this, array(), "log/view"));
            Navigation::addItem('/admin/campusconnect/log', $navigation);
        }

        /*******************************************************************
         *               Notifications für sendenswerte Daten              *
         *******************************************************************/
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidCreatOrUpdate");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidGetMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidDeleteMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidCreateOrUpdate");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidDelete");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeSchedule");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeStudyArea");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeInstitutes");

        /*******************************************************************
         *               Navigation für Kurse mit Kurs-URLs                *
         *******************************************************************/
        if (Navigation::hasItem("/course") && $_SESSION['SessionSeminar']) {
            $course = new CCCourse($_SESSION['SessionSeminar']);
            $course_urls = $course->getCourseUrls();
            if (count($course_urls) > 0) {
                $tab = new AutoNavigation(_("Lernplattformen"), PluginEngine::getUrl($this, array(), 'courselink/extern'));
                $tab->setImage(Assets::image_path("icons/16/white/link-extern"));
                Navigation::addItem("/course/campusconnect_extern", $tab);
            }
        }
    }

    /**
     * Adds a semininar or institute to the stack of objects, that need to be
     * synced via CampusConnect.
     * @param string $event : name of event
     * @param mixed $object : seminar- or institute-object
     * @param mixed $user_data : possible user_data, mostly useless here
     */
    public function synchronizeStudipItems($event, $object, $user_data)
    {
        if (strpos($event, "Course") === 0) {
            $type = "course";
            $id = $object->getId();
        }
        if (strpos($event, "Institute") === 0) {
            $type = "institute";
            $id = $object->getId();
        }
        if ($type) {
            CampusConnectTriggerStack::add($type, $id);
        }
    }

    public function getDisplayName()
    {
        return _("CampusConnect");
    }

    public function getTabNavigation($course_id)
    {
        $navigation = new Navigation(_("Informationen"), PluginEngine::getURL($this, array(), "courselink/overview"));
        $navigation->setImage(Assets::image_path("icons/16/white/infopage"), array('title' => _("Direkt zur Veranstaltung")));
        $navigation->addSubNavigation('overview', new AutoNavigation(_("Informationen"), PluginEngine::getURL($this, array(), "courselink/overview")));
        $navigation->addSubNavigation('details', new Navigation(_("Details"), URLHelper::getURL("details.php")));
        return array('main' => $navigation);
    }

    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        return null;
    }

    public function getInfoTemplate($course_id)
    {
        return null;
    }

    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return null;
    }

    /**
    * This method dispatches and displays all actions. It uses the template
    * method design pattern, so you may want to implement the methods #route
    * and/or #display to adapt to your needs.
    *
    * @param  string  the part of the dispatch path, that were not consumed yet
    *
    * @return void
    */
    public function perform($unconsumed_path)
    {
        if(!$unconsumed_path) {
            header("Location: " . PluginEngine::getUrl($this), 302);
            return false;
        }
        CampusConnectLog::get()->setLogLevel(CampusConnectLog::DEBUG);
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
