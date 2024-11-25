<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


class CampusConnect extends StudIPPlugin implements SystemPlugin, StandardPlugin
{

    public function __construct()
    {
        parent::__construct();
        StudipAutoloader::addAutoloadPath(__DIR__ . '/lib');
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

            $navigation = new Navigation("Log", PluginEngine::getURL($this, array(), "log/view"));
            Navigation::addItem('/admin/campusconnect/log', $navigation);

            $navigation = new Navigation("Log (old)", PluginEngine::getURL($this, array(), "log/view2"));
            Navigation::addItem('/admin/campusconnect/log2', $navigation);
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
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "DatafieldDidUpdate");


        /*******************************************************************
         *               Navigation für Kurse mit Kurs-URLs                *
         *******************************************************************/
        if (Navigation::hasItem("/course") && Context::getId()) {
            $course = new CCCourse(Context::getId());
            $course_urls = $course->getCourseUrls();
            if (count($course_urls) > 0) {
                $tab = new AutoNavigation(_("Lernplattformen"), PluginEngine::getUrl($this, array(), 'courselink/extern'));
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
        if (strpos($event, "DataFieldEntry") === 0) {
            $range_id = $object->getRangeID();
            $object_type = $object->structure->getID();
            if ($object_type === "sem") {
                $type = "course";
                $id = $range_id;
            } elseif($object_type === "inst") {
                $type = "institute";
                $id = $range_id;
            }
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
        if (Config::Get()->CAMPUSCONNECT_LOGFILE) {
            CampusConnectLog::get()->setHandler($GLOBALS['TMP_PATH']."/".Config::get()->CAMPUSCONNECT_LOGFILE);
        }
        CampusConnectLog::get()->setLogLevel(CampusConnectLog::DEBUG);
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, '', 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
