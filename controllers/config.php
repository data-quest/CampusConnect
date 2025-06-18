<?php

class ConfigController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        if(!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException('Keine Berechtigung');
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
        if (!function_exists("curl_init")) {
            PageLayout::postMessage(MessageBox::error(_("Das PHP-cURL Modul ist nicht aktiv. Ohne das wird dieser Konnektor nicht arbeiten können.")));
        }
        if (!$this->isNobodyAllowed()) {
            PageLayout::postMessage(MessageBox::error(_("Das CampusConnect-Plugin ist nicht für nobody zugelassen. Damit werden Kurslinks nicht korrekt funktionieren.")));
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

    function ecs_edit_action($config_id = null)
    {
        $this->server = new CampusConnectConfig($config_id);
        PageLayout::setTitle(
            $this->server->isNew() ? _('Neuen ECS eintragen') : _('ECS-Server bearbeiten')
        );
    }

    function ecs_save_action($config_id = null)
    {
        if (Request::isPost()) {
            if (Request::submitted('save')) {
                $server = new CampusConnectConfig($config_id);
                if (!$server->isNew() && $server['type'] !== "server") {
                    return;
                }
                $data_array = Request::getArray("data");
                $server['type'] = "server";
                $server['active'] = Request::int("active", 0);
                $server['data'] = CampusConnectHelper::rec_array_merge($server['data'], $data_array);
                $server->store();
            }
            if (Request::submitted('delete')) {
                $server = new CampusConnectConfig($config_id);
                $server->delete();
            }
        }

        PageLayout::postSuccess(_('Daten wurden gespeichert.'));
        $this->redirect(PluginEngine::getURL($this->plugin, [], 'config/ecs'));
    }

    function ecs_connectivity_action()
    {
        $data = Request::getArray("data");
        $client = new EcsClient($data);
        $result = $client->getMemberships();
        $result_header = $result->getResponseHeader();
        $this->render_json(array(
            'is_error' => $result->isError(),
            'status'   => $result_header['Status'] ?? '',
            'error'    => $client->last_cert_error ?: $client->last_error
        ));
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
        $statement = DBManager::get()->prepare(
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
        $this->communities = [];
        foreach (CampusConnectConfig::findByType("community") as $community) {
            $this->communities[$community['data']['cid']] = $community;
        }
	}

    function participant_save_action()
    {
        $server = new CampusConnectConfig(Request::get("id"));
        if ((!$server->isNew() && $server['type'] !== "participants") || (!Request::isPost())) {
            return;
        }
        $server['type'] = "participants";
        $server['active'] = Request::int("active", 0);

        //clear only some arrays:
        $data = $server['data'];
        $data['import_settings']['sem_type_matching'] = array();
        $data['import_settings']['auth_token'] = array();
        $data['export_settings']['auth_token'] = array();
        $server['data'] = $data;

        $data_array = Request::getArray("data");
        $data_array['import_settings']['sem_tree'] = Request::option("data__import_settings____sem_tree__");
        $server['data'] = CampusConnectHelper::rec_array_merge($server['data'], $data_array);
        $server->store();
        $this->render_json(array(
            'message' => (string) MessageBox::success(_("Teilnehmerdaten gespeichert")),
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

    private function isNobodyAllowed()
    {
        $rolepersistence = new RolePersistence();
        $plugin_roles = $rolepersistence->getAssignedPluginRoles($this->plugin->getPluginId());
        $nobody_allowed = false;
        foreach ($plugin_roles as $role) {
            if ($role->getRolename() === "Nobody") {
                $nobody_allowed = true;
            }
        }
        return $nobody_allowed;
    }

}

