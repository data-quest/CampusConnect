<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CampusConnectLink extends StudIPPlugin implements StandardPlugin, SystemPlugin
{

    public function __construct()
    {
        StudipAutoloader::addAutoloadPath(__DIR__ . '/lib');
        parent::__construct();
    }

    public function getDisplayName()
    {
        return _("Direktlink");
    }

    public function getTabNavigation($course_id)
    {
        $navigation = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "courselink/link"));
        return array('campusconnect_link' => $navigation);
    }

    public function getIconNavigation($course_id, $last_visit, $user_id) {
        $navigation = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "courselink/link"));
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
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'show');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
