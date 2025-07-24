<?php

class LogController extends PluginController
{

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
        PageLayout::addHeadElement("script",
            array("src" => $this->plugin->getPluginURL().'/assets/javascripts/application.js'),
            "");
        PageLayout::addHeadElement("link",
            array("href" => $this->plugin->getPluginURL().'/assets/stylesheets/application.css',
                "rel" => "stylesheet"),
            "");
        PageLayout::setTitle(_("CampusConnect Log"));
    }


    public function view_action()
    {
        $page = Request::int('page', 0);
        $per_page = Config::get()->ENTRIES_PER_PAGE;
        if (Request::get("type")) {
            $this->totalEntries = CCLog::countBySQL("log_type = ?", [Request::get("type")]);
            $this->entries = CCLog::findBySQL("log_type = ? ORDER BY mkdate DESC LIMIT ".($per_page * $page).", ".$per_page." ", [Request::get("type")]);
        } elseif (Request::get("text")) {
            $this->totalEntries = CCLog::countBySQL("log_text = ?", [Request::get("text")]);
            $this->entries = CCLog::findBySQL("log_text = ? ORDER BY mkdate DESC LIMIT ".($per_page * $page).", ".$per_page." ", [Request::get("text")]);
        } elseif(Request::get("search")) {
            $this->totalEntries = CCLog::countBySQL("log_json LIKE ?", ["%".Request::get("search")."%"]);
            $this->entries = CCLog::findBySQL("log_json LIKE ? ORDER BY mkdate DESC LIMIT ".($per_page * $page).", ".$per_page." ", ["%".Request::get("search")."%"]);
        } else {
            $this->totalEntries = CCLog::countBySQL("1");
            $this->entries = CCLog::findBySQL("1 ORDER BY mkdate DESC LIMIT ".($per_page * $page).", ".$per_page." ");
        }
        $this->pagination = Pagination::create($this->totalEntries, Request::int('page', 0));
    }

    public function details_action($log_id) {
        PageLayout::setTitle(_("CC-Logeintrag auslesen"));
        $this->entry = CCLog::find($log_id);
    }
}

