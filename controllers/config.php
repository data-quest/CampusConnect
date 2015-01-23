<?php
require __DIR__.'/application.php';
if (file_exists('lib/classes/Institute.class.php')) {
    require_once 'lib/classes/Institute.class.php';
} //otherwise we have autoloader
require_once __DIR__.'/../lib/CampusConnectTreeItems.php';
require_once __DIR__.'/../lib/CampusConnectTree.php';
require_once __DIR__.'/../lib/SemTreeSearch.class.php';
require_once __DIR__.'/../lib/StudyAreaSelector.class.php';


class ConfigController extends ApplicationController {

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) throw new AccessDeniedException('Keine Berechtigung');
        parent::before_filter($action, $args);
    }

    function after_filter($action, $args)
    {
        if (Request::isXHR() && $this->response->body) {
            $this->response->body = studip_utf8encode($this->response->body);
        }
        parent::after_filter($action, $args);
    }

    function index_action()
    {
        if (!function_exists("curl_init")) {
            PageLayout::postMessage(MessageBox::error(_("Das PHP-cURL Modul ist nicht aktiv. Ohne das wird dieser Konnektor nicht arbeiten können.")));
        }
        $this->imported_courses = count(CampusConnectEntity::findByType("course"));
        $this->imported_users = count(CampusConnectEntity::findByType("user"));
        $this->imported_institutes = count(CampusConnectEntity::findByType("institute"));
        $this->imported_semesters = count(CampusConnectEntity::findByType("semester"));
        $this->imported_studyareas = DBManager::get()->query(
            "SELECT COUNT(*) " .
            "FROM sem_tree " .
                "INNER JOIN campus_connect_tree_items ON (campus_connect_tree_items.mapped_sem_tree_id = sem_tree.sem_tree_id) " .
        "")->fetch(PDO::FETCH_COLUMN, 0);;
    }

    function ecs_action()
    {
        $this->servers = CampusConnectConfig::findBySQL("type = 'server' ORDER BY id ASC");
    }

    function ecs_save_action()
    {
        $server = new CampusConnectConfig(Request::get("id") ? Request::get("id") : null);
        if ((!$server->isNew() && $server['type'] !== "server") || (!count($_POST))) {
            return;
        }
        $data_array = Request::getArray("data");
        $data_array = CampusConnectHelper::rec_utf8_decode($data_array);
        $server['type'] = "server";
        $server['active'] = Request::int("active");
        $server['data'] = CampusConnectHelper::rec_array_merge($server['data'], $data_array);
        $server->store();
        $this->render_json(array(
            'message' => studip_utf8encode(Request::get("id")
                ? MessageBox::success(_("Serverdaten gespeichert"))
                : MessageBox::success(_("Neuen Server erstellt"))),
            'id' => $server->getId()
        ));
    }

    function ecs_get_data_action()
    {
        $server = new CampusConnectConfig(Request::get("id"));
        $data = array(
            'id' => $server['id'],
            'type' => $server['type'],
            'active' => $server['active'],
            'data' => (array) $server['data']
        );
        $this->render_json($data);
    }

    function ecs_delete_action()
    {
        if (count($_POST) && Request::get("id")) {
            $server = new CampusConnectConfig(Request::get("id"));
            $server->delete();
            echo "1";
        } else {
            echo "0";
        }
        $this->render_nothing();
    }


    function participants_action()
    {
        CCParticipant::updateParticipantsFromECS();
        $this->communities = CampusConnectConfig::findByType("community");
        $this->servers = CCParticipant::findAll();
    }

    function participant_action()
    {
        if (!Request::get("id")) {
            return;
        }
        $db = DBManager::get();
        $statement = $db->prepare(
            "SELECT sem_tree.sem_tree_id, sem_tree.parent_id, sem_tree.priority, IF(Institute.Name IS NULL, sem_tree.name, Institute.Name) AS name " .
            "FROM sem_tree " .
                "LEFT JOIN Institute ON (Institute.Institut_id = sem_tree.studip_object_id) " .
            "ORDER BY priority ASC " .
        "");
        $statement->execute();
        $study_areas2 = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->study_areas = array();
        foreach ($study_areas2 as $area) {
            $this->study_areas[$area['sem_tree_id']] = $area;
        }
        foreach ($this->study_areas as $key => $area) {
            $this->study_areas[$area['parent_id']]['children'][] = $key;
        }

        $statement = $db->prepare(
            "SELECT * " .
            "FROM datafields " .
            "WHERE object_type = 'sem' " .
            "ORDER BY name ASC " .
        "");
        $statement->execute();
        $this->datafields = $statement->fetchAll(PDO::FETCH_ASSOC);

        Navigation::activateItem("/admin/campusconnect/participants");
		$this->server = new CampusConnectConfig(Request::get("id"));
        $this->institute = Institute::getInstitutes();
        $this->import_cms_allowed = !$this->anotherCMS(Request::get("id"), true);
	}

    function participant_save_action()
    {
        $server = new CampusConnectConfig(Request::get("id") ? Request::get("id") : null);
        if ((!$server->isNew() && $server['type'] !== "participants") || (!Request::isPost())) {
            return;
        }
        $server['type'] = "participants";
        $server['active'] = Request::int("active");

        //clear only some arrays:
        $data = $server['data'];
        $data['import_settings']['sem_type_matching'] = array();
        $server['data'] = $data;

        $data_array = Request::getArray("data");
        $data_array['import_settings']['sem_tree'] = Request::option("data__import_settings____sem_tree__");
        $data_array = CampusConnectHelper::rec_utf8_decode($data_array);
        $server['data'] = CampusConnectHelper::rec_array_merge($server['data'], $data_array);
        if ($server['active'] && $server['data']['import_setting']['course_entity_type'] === "cms") {
            if ($this->anotherCMS($server->getId(), true)) {
                $server['active'] = 0;
            }
        }
        $server->store();
        $this->render_json(array(
            'message' => studip_utf8encode((string) MessageBox::success(_("Teilnehmerdaten gespeichert"))),
            'id' => $server->getId()
        ));
    }

    function participant_get_trees_action()
    {
        $participant = new CampusConnectConfig(Request::get("id") ? Request::get("id") : null);
        if ((!$participant->isNew() && $participant['type'] !== "participants")) {
            return;
        }
        $this->layout = null;
        $this->trees = CampusConnectTree::findBySQL("participant_id = ?", array($participant->getId()));
    }

    function match_tree_action()
    {
        $participant = new CampusConnectConfig(Request::get("participant_id") ? Request::get("participant_id") : null);
        if ((!$participant->isNew() && $participant['type'] !== "participants")) {
            return;
        }
        $node = new CampusConnectTreeItems(array(Request::get("node_id"), $participant->getId()));
        $node->map(Request::get("sem_tree_id"));
        
        $tree = new CampusConnectTree(Request::get("tree_id"));
        if (!$node['parent_id'] && !Request::get("sem_tree_id")) {
            $tree['mapping'] = "pending";
        } elseif($node['parent_id']) {
            $tree['mapping'] = "all";
        } else {
            $tree['mapping'] = "manual";
        }
        $tree->store();
        
        $this->render_nothing();
    }

    function ecs_connectivity_action()
    {
        $client = new EcsClient(Request::getArray("data"));
        $result = $client->getMemberships();
        $result_header = $result->getResponseHeader();
        $this->render_json(array(
            'is_error' => $result->isError(),
            'status'   => $result_header['Status'],
            'error'    => $client->last_cert_error ?: $client->last_error
        ));
    }

    protected function anotherCMS($participant_id, $import = true)
    {
        foreach (CampusConnectConfig::findByType("participant") as $participant) {
            if ($participant->getId() != $participant_id
                    && $participant['data'][($import ? "import" : "export").'_setting']['entity_type'] === "cms") {
                return true;
            }
        }
        return false;
    }

}

