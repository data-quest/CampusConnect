<?php
require __DIR__.'/application.php';

class LogController extends ApplicationController {

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) throw new AccessDeniedException('Keine Berechtigung');
        parent::before_filter($action, $args);
    }
	
    function view_action()
    {
        $this->logfile = CampusConnectLog::get()->getHandler();
        $this->logfile = $this->logfile ? $this->logfile : $GLOBALS['TMP_PATH'] . '/studip.log';
    }
}

