<?php

class StudyAreaSelectorController extends PluginController
{

    function stage_action($sem_tree_id)
    {
        $selector = new CampusConnectStudyAreaSelector(Request::get("name"), Request::get("type"));
        $selector->setId(Request::option("id"));
        $this->render_json(array(
            'html' => $selector->renderChildren($sem_tree_id)
        ));
    }
}

