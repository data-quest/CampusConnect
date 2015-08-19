<?php

require __DIR__.'/application.php';
require_once __DIR__."/../lib/CCCourse.php";
require_once __DIR__."/../lib/ECSAuthToken.class.php";
require_once __DIR__."/../lib/ECSLegacyAuthToken.class.php";

class CourselinkController extends ApplicationController {

    function overview_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("user", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniesException("Kein Zugriff");
        }
        if (Navigation::hasItem("/course/main")) {
            Navigation::getItem("/course/main")->setImage(Assets::image_path("icons/16/black/infopage"));
        }
        $this->course = new Course($_SESSION['SessionSeminar']);
        $sem_type = $GLOBALS['SEM_TYPE'][$this->course['status']];
        $this->coursedata = new CampusConnectEntity(array($_SESSION['SessionSeminar'], "course"));

        PageLayout::setTitle($sem_type['name'].": ".$this->course['name']);
    }

    function link_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("autor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniesException("Kein Zugriff");
        }
        
        if (Request::get("url")) {
            $get_participant_id = DBManager::get()->prepare(
                "SELECT participant_id " .
                "FROM campus_connect_course_url " .
                "WHERE course_url = :url " .
                    "AND seminar_id = :seminar_id " .
            "");
            $get_participant_id->execute(array(
                'url' => Request::get("url"),
                'seminar_id' => $_SESSION['SessionSeminar']
            ));
            $participant_id = $get_participant_id->fetch(PDO::FETCH_COLUMN, 0);
            if ($participant_id) {
                $participant = new CCParticipant($participant_id);
                $url = Request::get("url");
            }
        } else {
            $coursedata = new CampusConnectEntity(array($_SESSION['SessionSeminar'], "course"));
            $participant = new CCParticipant($coursedata['participant_id']);
            $url = $coursedata['data']['url'];
        }
        if ($url) {
            if (in_array($participant['data']['import_settings']['auth'], array("ecs_token", "legacy_ecs_token"))) {
                $user = new User($GLOBALS['user']->id);

                $auth_token_parameter = array(
                    'ecs_login' => $user['username'],
                    'ecs_firstname' => $user['Vorname'],
                    'ecs_lastname' => $user['Nachname'],
                    'ecs_email' => $user['Email'],
                    'ecs_institution' => "",
                    'ecs_uid' => $user->getId()
                );
                if ($participant['data']['import_settings']['auth'] === "ecs_token") {
                    $auth_token_parameter['ecs_person_id_type'] = $participant['data']['import_settings']['auth_token']['id_type'];
                    if (!in_array($auth_token_parameter['ecs_person_id_type'], array("ecs_uid", "ecs_email", "ecs_login"))) {
                        $auth_token_parameter[$auth_token_parameter['ecs_person_id_type']]
                            = $participant['data']['import_settings']['auth_token']['id'];
                    }
                    foreach ($participant['data']['import_settings']['auth_token']['attributes'] as $index => $value) {
                        if ($user->isField($index)) {
                            $auth_token_parameter[$index] = $user[$index];
                        } else {
                            $datafield_entry = DatafieldEntryModel::findBySQL("datafield_id = ? AND range_id = ?", array($value, $user->getId()));
                            $datafield_entry = $datafield_entry[0];
                            $auth_token_parameter[$index] = $datafield_entry['content'];
                        }
                    }
                }

                $ecs_data = new CampusConnectConfig($participant['data']['ecs'][0]);

                if ($participant['data']['import_settings']['auth'] === "ecs_token") {
                    $token = new ECSAuthToken($participant['data']['ecs'][0]);
                } else {
                    $token = new ECSLegacyAuthToken($participant['data']['ecs'][0]);
                    $auth_token_parameter['ecs_uid_hash'] = $user->getId();
                }
                $mid = array_values($participant['data']['mid']);
                $ecs_auth = $token->getHash(
                    $mid[0],
                    $url,
                    $auth_token_parameter
                );
                $url_parameter = array('ecs_hash_url' => $token->getURL());
                foreach ($auth_token_parameter as $index => $value) {
                    $url_parameter[$index] = $value;
                }
                $url = URLHelper::getURL($url, $url_parameter, true);
            }
            CampusConnectLog::_(sprintf("ecs-auth: refering user now to %s", $url), CampusConnectLog::DEBUG);
            header("Location: ".$url);
            exit;
        }
        $this->sem = new Seminar($_SESSION['SessionSeminar']);
        $sem_type = $GLOBALS['SEM_TYPE'][$this->sem->status];

        PageLayout::setTitle($sem_type['name'].": ".$this->sem->getName());
        Navigation::getItem("/course/link")->setImage(Assets::image_path("icons/16/black/learnmodule"));
    }

    /**
     * Incoming user from another lms that probably has an auth-token.
     * Logs user in and redirects him/her to the course.
     * @param string $cid : Seminar_id
     */
    public function to_action($cid) {
        CampusConnectLog::_(sprintf("ecs-auth: start: %s", print_r($_REQUEST,1), CampusConnectLog::DEBUG));
        CCLog::log("CC-user_jumps_in", "User comes from another system and jumps in.", array(
            'request' => $_REQUEST,
            'user_id' => $GLOBALS['user']->id
        ));
        $course_url = URLHelper::getURL("details.php", array('sem_id' => $cid));
        $ecs_hash = Request::get("ecs_hash")
            ? studip_utf8decode(Request::get("ecs_hash"))
            : studip_utf8decode(Request::get("ecs_hash_url"));
        $ecs_uid_hash = Request::get("ecs_uid")
            ? studip_utf8decode(Request::get("ecs_uid"))
            : studip_utf8decode(Request::get("ecs_uid_hash"));
        if ($GLOBALS['user']->id === 'nobody'
                && $ecs_hash
                && Request::get("ecs_login")
                && ($ecs_uid_hash || Request::get("ecs_person_id_type"))
                && Request::get("ecs_email")) {
            //ECS anhand der URL ausfindig machen.
            //Schauen, ob ECS und Auth über ECS lokal aktiviert ist.
            $accept = false;
            CampusConnectLog::_(sprintf("ecs-auth: checking hash: %s", $ecs_hash), CampusConnectLog::DEBUG);
            $ecs_found = false;
            $ecs_hash = substr($ecs_hash, strripos($ecs_hash, "/") + 1);
            foreach (CampusConnectConfig::findByType("server") as $ecs) {
                $ecs_url = parse_url($ecs['data']['server']);
                $token_url = parse_url(Request::get("ecs_hash_url"));
                if (($token_url['host'] === $ecs_url['host'])
                        && $ecs['active']
                        ) {
                    $ecs_found = true;
                    CampusConnectLog::_(sprintf("ecs-auth: found-ecs to auth-token: %s", $ecs['data']['server']), CampusConnectLog::DEBUG);

                    $user_mapping = CampusConnectEntity::findByForeignID(
                        'user',
                        $ecs_uid_hash,
                        $ecs['id']
                    );
                    $user = new User($user_mapping['item_id']);

                    if (Request::get("ecs_person_id_type")) {
                        $token = new ECSAuthToken($ecs->getId());
                        $parameter = array();
                        foreach ($_GET as $index => $value) {
                            if ($index !== "ecs_hash_url") {
                                $parameter[$index] = studip_utf8decode($value);
                            }
                        }
                        $accept = $token->validateAndMap(
                            $ecs_hash,
                            $parameter,
                            $user
                        );
                    } else {
                        $token = new ECSLegacyAuthToken($ecs->getId());
                        $accept = $token->validate(
                            $ecs_hash,
                            array(
                                'ecs_login' => studip_utf8decode(Request::get("ecs_login")),
                                'ecs_firstname' => studip_utf8decode(Request::get("ecs_firstname")),
                                'ecs_lastname' => studip_utf8decode(Request::get("ecs_lastname")),
                                'ecs_email' => studip_utf8decode(Request::get("ecs_email")),
                                'ecs_institution' => studip_utf8decode(Request::get("ecs_institution")),
                                'ecs_uid' => $ecs_uid_hash
                            )
                        );
                    }
                    $active_ecs = $ecs;
                    break;
                }
            }
            $participant_id = $active_ecs['id'];

            if ($accept) {
                //Gegebenenfalls neuen Nutzeraccount und Mapping anlegen.
                if (!$user_mapping || $user->isNew()) {
                    if ($user_mapping) {
                        $user_mapping->delete();
                    }
                    $i = "";
                    while (get_userid(studip_utf8decode(Request::get("ecs_login")).$i)) {
                        $i++;
                    }
                    $user = new User();
                    $user['username'] = studip_utf8decode(Request::get("ecs_login")).$i;
                    $user['Vorname'] = studip_utf8decode(Request::get("ecs_firstname"));
                    $user['Nachname'] = studip_utf8decode(Request::get("ecs_lastname"));
                    $user['Email'] = studip_utf8decode(Request::get("ecs_email"));
                    $user['perms'] = "autor";
                    $user['password'] = "";
                    $user['validation_key'] = "";
                    $user['auth_plugin'] = "ecs";
                    $user['hobby'] = "";
                    $user['publi'] = "";
                    $user['schwerp'] = "";
                    $user['Home'] = "";
                    $user['privatnr'] = "";
                    $user['privatcell'] = "";
                    if ($user->isField("privatadr")) {
                        $user['privatadr'] = "";
                    }
                    if ($user->isField("privadr")) {
                        $user['privadr'] = "";
                    }
                    $user['title_front'] = "";
                    $user['title_rear'] = "";
                    $user['smsforward_rec'] = "";
                    if ($user->isField("smiley_favourite")) {
                        $user['smiley_favourite'] = "";
                    }
                    $user['motto'] = "";
                    $user->store();

                    $user_mapping = new CampusConnectEntity();
                    $user_mapping['item_id'] = $user->getId();
                    $user_mapping['type'] = "user";
                    $user_mapping['foreign_id'] = $ecs_uid_hash;
                    $user_mapping['participant_id'] = $participant_id;
                    $user_mapping['data'] = array();
                    $user_mapping->store();
                    CampusConnectLog::_(sprintf("ecs-auth: created new user with user_id %s and foreign_id %s ", $user_mapping['item_id'], $user_mapping['foreign_id']), CampusConnectLog::DEBUG);
                } else {
                    $user = new User($user_mapping['item_id']);
                    $user['Vorname'] = studip_utf8decode(Request::get("ecs_firstname"));
                    $user['Nachname'] = studip_utf8decode(Request::get("ecs_lastname"));
                    $user['Email'] = studip_utf8decode(Request::get("ecs_email"));

                    $user->store();
                }

                //Datenabgleich:
                if (Request::get("ecs_person_id_type")) {
                    $ecs = new CCParticipant($participant_id);
                    $participant = null;
                    foreach (CCParticipant::findAll() as $p) {
                        if ($p['data']['pid'] === $token->token_data['pid']) {
                            $participant = $p;
                            break;
                        }
                    }
                    if ($participant) {
                        foreach ((array) $participant['data']['export_settings']['auth_token']['attributes'] as $name => $map) {
                            if (Request::get(studip_utf8encode($name))) {
                                $value = studip_utf8decode(Request::get(studip_utf8encode($name)));
                                if (in_array($name, array("user_id", "username", "email"))) {
                                    $user[$name] = $value;
                                    $user->store();
                                } elseif($name === "institut") {
                                    $institut = Institute::findBySQL("Name = ? LIMIT 1", array($value));
                                    $institut = $institut[0];
                                    $institut_member = new InstituteMember(array($institut->getId(), $user->getId()));
                                    $institut_member->store();
                                } else {
                                    $datafield_entry = new DatafieldEntryModel(array($map, $user->getId(), ""));
                                    $datafield_entry['content'] = $value;
                                    $datafield_entry->store();
                                }
                            }
                        }
                    }
                }


                //register user-session
                if (!$user->isNew()) {
                    global $sess,$auth;
                    $sess->regenerate_session_id(array('auth'));
                    $auth->unauth();
                    $auth->auth["jscript"] = true;
                    $auth->auth["perm"]  = $user["perms"];
                    $auth->auth["uname"] = $user["username"];
                    $auth->auth["auth_plugin"]  = $user["auth_plugin"];
                    $auth->auth_set_user_settings($user->user_id);
                    $auth->auth["uid"] = $user["user_id"];
                    $auth->auth["exp"] = time() + (60 * $auth->lifetime);
                    $auth->auth["refresh"] = time() + (60 * $auth->refresh);
                }
            } else {
                CampusConnectLog::_(sprintf("ecs-auth: token is not accepted: %s", $ecs_hash), CampusConnectLog::DEBUG);
            }
        } else {
            $error = "";
            if ($GLOBALS['user']->id !== 'nobody') {
                $error .= "User already loggin in. ";
            }
            if (!$ecs_hash) {
                $error .= "Parameter ecs_hash_url or ecs_hash are missing. ";
            }
            if (!Request::get("ecs_login")) {
                $error .= "Parameter ecs_login is missing. ";
            }
            if (!$ecs_uid_hash && !Request::get("ecs_person_id_type")) {
                if (!$ecs_uid_hash) {
                    $error .= "Parameter ecs_uid_hash or ecs_uid are missing. ";
                } else {
                    $error .= "Parameter ecs_person_id_type is missing. ";
                }
            }
            if (!Request::get("ecs_email")) {
                $error .= "Parameter ecs_email is missing. ";
            }
            CCLog::log("CC-user_jumps_in", "User comes from another system and won't get logged in: ".$error, array(
                'request' => $_REQUEST,
                'user_id' => $GLOBALS['user']->id
            ));
        }
        //Redirect:
        if ($user) {
            $is_user_registered_to_course = DBManager::get()->prepare(
                "SELECT 1 " .
                "FROM seminar_user " .
                "WHERE Seminar_id = :cid " .
                    "AND user_id = :user_id " .
            "");
            $is_user_registered_to_course->execute(array(
                'cid' => $cid,
                'user_id' => $user->getId()
            ));
            if ($is_user_registered_to_course->fetch(PDO::FETCH_COLUMN, 0)) {
                $course_url = URLHelper::getURL($course_url, array('cid' => $cid));
            }
        }
        header("Location: ".$course_url);
        $this->render_nothing();
    }

    public function extern_action() {
        $course = new CCCourse($_SESSION['SessionSeminar']);
        $this->course_urls = $course->getCourseUrls();
        if (count($this->course_urls) === 1 || Request::get("course_url")) {
            //only one way to go
            if (Request::get("course_url")) {
                foreach ($this->course_urls as $url_data) {
                    if ($url_data['course_url'] === Request::get("course_url")) {
                        $course_url = $url_data;
                    }
                }
            } else {
                $course_url = $this->course_urls[0];
            }
            $participant = new CCParticipant($course_url['participant_id']);

            $user = new User($GLOBALS['user']->id);
            $ecs_login = $user['username'];
            $ecs_firstname = $user['Vorname'];
            $ecs_lastname = $user['Nachname'];
            $ecs_email = $user['Email'];
            $ecs_institution = "";
            $ecs_uid = $user->getId();

            $ecs_data = new CampusConnectConfig($participant['data']['ecs'][0]);
            $ecs = new EcsClient($ecs_data['data']);

            $token = new ECSAuthToken($participant['data']['ecs'][0]);
            $mid = array_values($participant['data']['mid']);
            $ecs_auth = $token->getHash(
                $mid[0],
                $course_url['course_url'],
                $ecs_login,
                $ecs_firstname,
                $ecs_lastname,
                $ecs_email,
                $ecs_institution,
                $ecs_uid
            );

            $url = URLHelper::getURL($course_url['course_url'], array(
                'ecs_hash_url' => $token->getURL(),
                'ecs_login' => studip_utf8encode($user['username']),
                'ecs_firstname' => studip_utf8encode($user['Vorname']),
                'ecs_lastname' => studip_utf8encode($user['Nachname']),
                'ecs_email' => studip_utf8encode($user['Email']),
                'ecs_institution' => studip_utf8encode(""),
                'ecs_uid' => $user->getId(),
                'ecs_uid_hash' => $user->getId() //deprecated
            ), true);
            header("Location: ".$url);
            exit;
        }
        if (Navigation::hasItem("/course/campusconnect_extern")) {
            Navigation::getItem("/course/campusconnect_extern")->setImage("icons/16/black/link-extern");
        }
    }

    public function test_to_action() {
        $output = array();
        $output['already_logged_in'] = $GLOBALS['user']->id !== 'nobody' ? 1 : 0;
        $url = Request::get("ecs_hash_url");
        $login = Request::get("ecs_login");
        $firstname = Request::get("ecs_firstname");
        $lastname = Request::get("ecs_lastname");
        $email = Request::get("ecs_email");
        $institution = Request::get("ecs_institution");
        $uid = Request::get("ecs_uid_hash") ? Request::get("ecs_uid_hash") : Request::get("ecs_uid");
        $output['realm'] = ECSAuthToken::getRealm(
            $url, $login, $firstname, $lastname, $email, $institution, $uid
        );
        $output['realm_without_sha1'] = ECSAuthToken::getRealmBeforeHashing(
            $url, $login, $firstname, $lastname, $email, $institution, $uid
        );

        $ecs_found = false;
        $ecs_hash = substr(Request::get("ecs_hash_url"), strripos(Request::get("ecs_hash_url"), "/") + 1);
        $output['token'] = $ecs_hash;
        foreach (CampusConnectConfig::findByType("server") as $ecs) {
            if ((stripos(Request::get("ecs_hash_url"), $ecs['data']['server']) === 0)
                    && $ecs['active']
                    ) {
                $ecs_found = true;
                $output['ecs_server_found'] = $ecs['data']['server'];
                $token = new ECSAuthToken($ecs->getId());
                $accept = $token->validate(
                    $ecs_hash,
                    Request::get("ecs_login"),
                    Request::get("ecs_firstname"),
                    Request::get("ecs_lastname"),
                    Request::get("ecs_email"),
                    Request::get("ecs_institution"),
                    Request::get("ecs_uid_hash") ? Request::get("ecs_uid_hash") : Request::get("ecs_uid")
                );
                $active_ecs = $ecs;
                break;
            }
        }
        $output['token_accepted'] = $accept ? 1 : 0;

        $this->render_json(CampusConnectHelper::rec_utf8_encode($output));
    }
}

