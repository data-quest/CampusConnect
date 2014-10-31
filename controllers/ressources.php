<?php
require __DIR__.'/application.php';

class RessourcesController extends ApplicationController {

	function temporary_ressource_action($id)
    {
        $ressource = new CCRessource($id);
        if ($ressource->isNew() || $ressource['mkdate'] < time() - 60 * 30) {
            $ressource->delete();
            CampusConnectLog::_(sprintf("Unsuccessful lookup of ressource %s ",$id), CampusConnectLog::DEBUG);
            throw new Exception("Unknown ressource");
        }
        CampusConnectLog::_(sprintf("Successful lookup of ressource %s ",$id), CampusConnectLog::DEBUG);
        $this->render_json($ressource['json']);
    }
}
