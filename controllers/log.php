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
        if ($GLOBALS['CAMPUSCONNECT_LOGFILE']) {
            CampusConnectLog::get()->setHandler($GLOBALS['CAMPUSCONNECT_LOGFILE']);
        }
        $this->logfile = $this->logfile ? $this->logfile : (CampusConnectLog::get()->getHandler() ?: $GLOBALS['TMP_PATH']."/studip.log");
    }
}

