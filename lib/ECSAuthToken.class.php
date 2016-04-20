<?php

/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class ECSAuthToken {

    protected $ecs_id = null;
    public $token_data = null;

    protected $parameter = array();
    protected $url = null;

    public $debugging = array();

    public function __construct($ecs_id) {
        $this->ecs_id = $ecs_id;
    }

    public function fetchTokenData($ecs_hash) {
        if (strripos($ecs_hash, "/") !== false) {
            $ecs_hash = substr($ecs_hash, strripos($ecs_hash, "/") + 1);
        }
        $ecs_server = new CampusConnectConfig($this->ecs_id);
        $ecs_client = new EcsClient($ecs_server['data']);

        $this->debugging[] = sprintf("ECSAuthToken: Checking ecs-token-hash %s on ecs '%s'", $ecs_hash, $this->data['name']);
        $result = $ecs_client->checkAuths($ecs_hash);
        $this->token_data = $result->getResult();
        CampusConnectLog::_(sprintf("ecs-auth: got result: %s", print_r($this->token_data, 1)), CampusConnectLog::DEBUG);
        return $result->isError() ? false : $this->token_data;
    }

    public function validate($parameter) {
        $url  = $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $url .= '://'.$_SERVER['SERVER_NAME'];
        if ($_SERVER['HTTPS'] == 'on' && $_SERVER['SERVER_PORT'] != 443 ||
            $_SERVER['HTTPS'] != 'on' && $_SERVER['SERVER_PORT'] != 80) {
            $url .= ':'.$_SERVER['SERVER_PORT'];
        }
        //URL bis zum ecs_hash_url -Parameter
        $url .= stripos($_SERVER['REQUEST_URI'], "?") === false
            ? $_SERVER['REQUEST_URI']
            : substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));

        $realm = self::getRealm(
            $url,
            $parameter
        );

        if ($realm !== $this->token_data['realm']) {
            CampusConnectLog::_(sprintf("ecs-auth: realm does not match: %s", $realm), CampusConnectLog::DEBUG);
            $this->debugging[] = sprintf("ECSAuthToken: Constructed realm %s does not match: %s", $realm, $this->token_data['realm']);
        }

        if ($realm === $this->token_data['realm']
            || (!$this->token_data['realm'] && $this->token_data['url'])) {
            $this->parameter = $parameter;
            return true;
        } else {
            return false;
        }
    }

    public function validateAndMap($ecs_hash, $parameter) {
        $this->fetchTokenData($ecs_hash);
        return $this->validate($parameter);
    }

    public function getHash($mid, $url, $parameter) {
        $ecs_server = new CampusConnectConfig($this->ecs_id);
        $ecs_client = new EcsClient($ecs_server['data']);

        $realm = $this->getRealm(
            $url,
            $parameter
        );
        $result = $ecs_client->getAuths($mid, $realm, $url);
        $ecs_auth = $result->getResult();
        $this->url = $ecs_client->getUrl('/sys/auths')."/".$ecs_auth['hash'];
        $this->parameter = $parameter;
        return $ecs_auth;
    }

    public function getRealm($url, $parameter) {
        return sha1($this->getRealmBeforeHashing(
            $url,
            $parameter
        ));
    }

    public function getRealmBeforeHashing($url, $parameter) {
        $output = $url;
        foreach ($parameter as $param_name => $param) {
            $output .= $param;
        }
        $output = studip_utf8encode($output);
        CampusConnectLog::_(sprintf("ecs-auth: constructed realm before hashing: %s", $output), CampusConnectLog::DEBUG);
        if ($this) {
            $this->debugging[] = sprintf("ECSAuthToken: Constructed realm before hashing: %s", $output);
        }
        return $output;
    }

    public function getURL() {
        return $this->url;
    }

    public function getParameter() {
        return $this->parameter;
    }

}

