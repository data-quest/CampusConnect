<?php
require __DIR__.'/application.php';

class LogController extends ApplicationController {

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException('Keine Berechtigung');
        }
        parent::before_filter($action, $args);
        Navigation::activateItem("/admin/campusconnect/log");
        if (Request::isAjax()) {
            $this->set_layout(null);
        }
    }


    public function view_action()
    {

        if (get_config("CAMPUSCONNECT_LOGFILE")) {
            CampusConnectLog::get()->setHandler($GLOBALS['TMP_PATH']."/".get_config("CAMPUSCONNECT_LOGFILE"));
        }
        $this->entries = CCLog::read();
    }

    public function details_action($log_id) {
        PageLayout::setTitle(_("CC-Logeintrag auslesen"));
        $this->entry = CCLog::read("log_id = ? ", array($log_id));
        $this->entry = $this->entry[0];
    }

    function view2_action()
    {
        if (get_config("CAMPUSCONNECT_LOGFILE")) {
            CampusConnectLog::get()->setHandler($GLOBALS['TMP_PATH']."/".get_config("CAMPUSCONNECT_LOGFILE"));
        }
        $this->logfile = $this->logfile ? $this->logfile : (CampusConnectLog::get()->getHandler() ?: $GLOBALS['TMP_PATH']."/studip.log");
    }
}

