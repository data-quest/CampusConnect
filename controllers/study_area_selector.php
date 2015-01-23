<?php
require __DIR__.'/application.php';
require_once __DIR__.'/../lib/StudyAreaSelector.class.php';

class StudyAreaSelectorController extends ApplicationController {

    function stage_action($sem_tree_id)
    {
        $selector = new \CampusConnect\StudyAreaSelector(Request::get("name"), Request::get("type"));
        $selector->setId(Request::option("id"));
        $this->render_json(array(
            'html' => $selector->renderChildren($sem_tree_id)
        ));
    }
}

