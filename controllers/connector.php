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
        CampusConnector::send_changes();
        $this->render_nothing();
    }

    function receive_action()
    {
        CampusConnector::fetch_updates();
        $this->render_nothing();
    }
}

