<?php
/*
 * Copyright (C) 2012 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Der Controller, der vom ECS angesteuert wird und die über Cronjob Änderungen
 * an den ECS übermittelt.
 */
class ConnectorController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException('Keine Berechtigung');
        }
        PageLayout::addHeadElement("script",
            array("src" => $this->plugin->getPluginURL().'/assets/javascripts/application.js'),
            "");
        PageLayout::addHeadElement("link",
            array("href" => $this->plugin->getPluginURL().'/assets/stylesheets/application.css',
                "rel" => "stylesheet"),
            "");
        parent::before_filter($action, $args);
    }

    function send_changes_action()
    {
        CampusConnector::send_changes();
        $this->render_nothing();
    }

    function receive_action()
    {
        CampusConnector::fetch_updates();
        $this->render_nothing();
    }

    function update_everything_action()
    {
        CampusConnector::send_everything();
        $this->render_nothing();
    }


}

