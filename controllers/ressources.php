<?php

class RessourcesController extends PluginController
{

	function temporary_ressource_action($id)
    {
        $ressource = new CCRessource($id);
        if ($ressource->isNew() || $ressource['mkdate'] < time() - 60 * 30) {
            $ressource->delete();
            CCLog::log("RESOURCE_LOOKUP_FAIL", sprintf("Unsuccessful lookup of ressource %s ",$id));
            throw new Exception("Unknown ressource");
        }
        CCLog::log("RESOURCE_LOOKUP_SUCCESS", sprintf("Successful lookup of ressource %s ",$id));
        $this->render_json($ressource['json']);
    }
}
