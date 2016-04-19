<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once 'lib/CCParticipant.php';
require_once 'lib/CampusConnectLog.php';
require_once "lib/CampusConnectHelper.php";
require_once 'lib/EcsClient.php';
require_once 'lib/EcsResult.php';
require_once 'lib/CampusConnectClient.php';
require_once 'lib/CampusConnectTriggerStack.php';


class CampusConnectLink extends StudIPPlugin implements StandardPlugin, SystemPlugin
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getDisplayName()
    {
        return _("Direktlink");
    }

    public function getTabNavigation($course_id)
    {
        $navigation = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "courselink/link"));
        $navigation->setImage(Assets::image_path("icons/16/white/learnmodule"), array('title' => _("Direkt zur Veranstaltung")));
        return array('link' => $navigation);
    }

    public function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "courselink/link"));
        $navigation->setImage(Assets::image_path("icons/16/grey/learnmodule"), array('title' => _("Direkt zur Veranstaltung")));
        return $navigation;
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
        if (get_config("CAMPUSCONNECT_LOGFILE")) {
            CampusConnectLog::get()->setHandler($GLOBALS['TMP_PATH']."/".get_config("CAMPUSCONNECT_LOGFILE"));
        }
        CampusConnectLog::get()->setLogLevel(CampusConnectLog::DEBUG);
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
