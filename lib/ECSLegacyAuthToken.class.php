<?php

/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class ECSLegacyAuthToken {

    protected $ecs_id = null;
    protected $url = null;

    public function __construct($ecs_id) {
        $this->ecs_id = $ecs_id;
    }

    public function validate($ecs_hash, $parameter) {
        $ecs_server = new CampusConnectConfig($this->ecs_id);
        $ecs_client = new EcsClient($ecs_server['data']);

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
        CampusConnectLog::_(sprintf("ecs-auth: checking realm: %s\n%s",$realm, print_r($this->ecs,1)), CampusConnectLog::DEBUG);
        $result = $ecs_client->checkAuths($ecs_hash);
        $ecs_token = $result->getResult();
        CampusConnectLog::_(sprintf("ecs-auth: got result: %s", print_r($ecs_token,1)), CampusConnectLog::DEBUG);
        if ($realm !== $ecs_token['realm']) {
            CampusConnectLog::_(sprintf("ecs-auth: realm does not match: %s", $realm), CampusConnectLog::DEBUG);
        }

        return $realm === $ecs_token['realm']
            || (!$ecs_token['realm'] && $ecs_token['url']);
    }

    public function getHash($mid, $url, $parameter) {
        $ecs_server = new CampusConnectConfig($this->ecs_id);
        $ecs_client = new EcsClient($ecs_server['data']);

        $realm = self::getRealm(
            $url,
            $parameter
        );
        $result = $ecs_client->getAuths($mid, $realm, $url);
        $ecs_auth = $result->getResult();
        $this->url = $ecs_client->getUrl('/sys/auths')."/".$ecs_auth['hash'];
        return $ecs_auth;
    }

    static public function getRealm($url, $parameter) {
        return sha1(self::getRealmBeforeHashing(
            $url, $parameter
        ));
    }

    static public function getRealmBeforeHashing($url, $parameter) {
        $output = $url;
        foreach ($parameter as $param_name => $param) {
            $output .= $param;
        }
        $output = studip_utf8encode($output);
        CampusConnectLog::_(sprintf("ecs-auth: constructed realm before hashing: %s", $output), CampusConnectLog::DEBUG);
        return $output;
    }

    public function getURL() {
        return $this->url;
    }

}