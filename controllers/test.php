<?php
class TestController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) {
            throw new Studip_AccessDeniedException('Keine Berechtigung');
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

    function index_action()
    {
        Navigation::activateItem("/admin/campusconnect/index");
        $this->testergebnis = file_get_contents($this->plugin->getPluginURL()."/simpletest/unit_test.php");
    }

    function raw_test_action()
    {
        $starttime = time();
        $this->render_text(htmlReady(file_get_contents($this->plugin->getPluginURL()."/simpletest/unit_test.php")));
        $_SESSION['unit_test_progress_time'] = (time() - $starttime) * 1000;
    }
}

