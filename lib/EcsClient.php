<?php

class EcsClient
{

    protected $config;
    protected $header = array();
    protected $request_method = 'GET';
    public $timeout = 5;
    public $last_cert_error;
    public $last_error;
    public $last_error_number;

    function __construct($config = array())
    {
        $this->setConfig($config);
    }

    function setConfig($config)
    {
        $this->config = $config;
    }

    function getConfig($config)
    {
        return $this->config;
    }

    function setHeader($a, $b)
    {
        if ($b === null) {
            unset($this->header[$a]);
        } else {
            $this->header[$a] = $b;
        }
    }

    function issetHeader($a)
    {
        return isset($this->header[$a]);
    }

    function unsetHeader()
    {
        $this->header = array();
    }

    function setRequestMethod($method)
    {
        $this->request_method = $method;
    }

    function getUrl($path)
    {
        return trim(trim($this->config['server'], ' /') . '/' . trim($path,'/'), ' /');
    }

    private function execute($path, $data = '')
    {
        $c = curl_init();
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $this->setHeader('Accept', 'application/json');
        curl_setopt($c, CURLOPT_URL, $this->getUrl($path));
        curl_setopt($c, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($c, CURLOPT_VERBOSE, 0);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        if ($GLOBALS['user']->id === "cli") {
            curl_setopt($c, CURLOPT_TIMEOUT, 60);
        }
        switch($this->config['auth_type'])
        {
            case 1:
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($c, CURLOPT_USERPWD,
                    $this->config['auth_user'] . ':' . $this->config['auth_pass']
                );
                break;
            case 2:
            default:
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
                if (!file_exists($this->config['ca_cert_path'])) {
                    $this->last_cert_error = "Cannot find CA cert at ".$this->config['ca_cert_path'];
                }
                curl_setopt($c, CURLOPT_CAINFO, $this->config['ca_cert_path']);
                if (!file_exists($this->config['client_cert_path'])) {
                    $this->last_cert_error = "Cannot find client cert at ".$this->config['client_cert_path'];
                }
                curl_setopt($c, CURLOPT_SSLCERT, $this->config['client_cert_path']);
                if (!file_exists($this->config['key_path'])) {
                    $this->last_cert_error = "Cannot find cert key at ".$this->config['key_path'];
                }
                curl_setopt($c, CURLOPT_SSLKEY, $this->config['key_path']);
                curl_setopt($c, CURLOPT_SSLKEYPASSWD, $this->config['key_password']);
                break;
        }

        switch($this->request_method)
        {
            case 'POST':
                if (!$this->issetHeader('Content-Type')) {
                    $this->setHeader('Content-Type', 'application/json');
                }
                curl_setopt($c, CURLOPT_POST, true);
                curl_setopt($c, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                if (!$this->issetHeader('Content-Type')) {
                    $this->setHeader('Content-Type', 'application/json');
                }
                curl_setopt($c, CURLOPT_PUT, true);
                $fp = fopen('php://temp', 'r+');
                fwrite($fp, $data);
                rewind($fp);
                curl_setopt($c, CURLOPT_UPLOAD, true);
                curl_setopt($c, CURLOPT_INFILESIZE, strlen($data));
                curl_setopt($c, CURLOPT_INFILE, $fp);
                break;
            case 'DELETE':
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                curl_setopt($c, CURLOPT_HTTPGET, true);
        }
        $header = array();
        foreach($this->header as $a => $b) $header[] = "$a: $b";
        curl_setopt($c, CURLOPT_HTTPHEADER, $header);
        $response_header = array();
        $headerfunc = function($handle, $headerdata) use (&$response_header)
        {
            foreach (explode("\r\n", $headerdata) as $line) {
                if (strpos($line, '.') === false) {
                    continue;
                }
                list($a, $b) = explode(':', $line, 2);
                $a = trim($a);
                $b = trim($b);
                if ($a) {
                    if (strpos($a, 'HTTP') !== false) {
                        $response_header['Status'] = $a;
                    } else {
                        $response_header[$a] = $b;
                    }
                }
            }
            return strlen($headerdata);
        };
        curl_setopt($c, CURLOPT_HEADERFUNCTION, $headerfunc);
        CampusConnectLog::_(sprintf('curl_exec: %s, %s, %s',$this->getUrl($path), $this->request_method, print_r($header,1)), CampusConnectLog::DEBUG);

        $result = curl_exec($c);
        $response_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        if ($result === false) {
            $this->last_error_number = curl_errno($c);
            $this->last_error = curl_error($c);
            CampusConnectLog::_('curl_exec failed: ' . $this->last_error, CampusConnectLog::WARNING);
        } else {
            $this->last_error_number = null;
            $this->last_error = null;
            CampusConnectLog::_(sprintf("curl_exec success: %s\n%s",$result, print_r($response_header,1)), CampusConnectLog::DEBUG);
            switch ($path) {
                case "/sys/memberships":
                    CCLog::log(
                        "CC-get_memberships",
                        sprintf('curl_exec: %s',$this->getUrl($path)),
                        json_decode($result, true)
                    );
            }
        }
        curl_close($c);
        $this->unsetHeader();
        return new EcsResult($result, $response_code, $response_header);
    }

    function getMemberships()
    {
        $this->setRequestMethod('GET');
        return $this->execute('/sys/memberships');
    }

    function getEvents($count = null)
    {
        $this->setRequestMethod('GET');
        return $this->execute('/sys/events' . ($count ? '?count=' . (int)$count : ''));
    }

    function getEventsFifo($count = null)
    {
        $this->setRequestMethod('GET');
        return $this->execute('/sys/events/fifo' . ($count ? '?count=' . (int)$count : ''));
    }

    function getAndRemoveEventsFifo($count = null)
    {
        $this->setRequestMethod('POST');
        return $this->execute('/sys/events/fifo' . ($count ? '?count=' . (int)$count : ''));
    }

    function getAuths($receiver_memberships, $realm = null, $url = null)
    {
        if (is_array($receiver_memberships)) {
            $receiver_memberships = join(',', $receiver_memberships);
        }
        $this->setHeader('X-EcsReceiverMemberships', $receiver_memberships);
        $this->setRequestMethod('POST');
        $params = array(
            'realm' => $realm,
            'url' => $url
        );
        return $this->execute('/sys/auths', $params);
    }

    function checkAuths($auth_hash)
    {
        $this->setRequestMethod('DELETE');
        return $this->execute('/sys/auths/' . $auth_hash);
    }

    function getResourceList($path)
    {
        $this->setRequestMethod('GET');
        return $this->execute($path);
    }

    function createResourceMessage($path, $message, $receiver_memberships = null, $receiver_communities = null, $secretly = false)
    {
        $this->setRequestMethod('POST');
        if ($receiver_memberships) {
            if (is_array($receiver_memberships)) {
                $receiver_memberships = join(',', $receiver_memberships);
            }
            $this->setHeader('X-EcsReceiverMemberships', $receiver_memberships);
        }
        if ($receiver_communities) {
            if (is_array($receiver_communities)) {
                $receiver_communities = join(',', $receiver_communities);
            }
            $this->setHeader('X-EcsReceiverCommunities', $receiver_communities);
        }
        if ($secretly) {
            $this->setHeader('Content-Type', 'text/uri-list');
            $ressource = new CCRessource();
            $ressource['json'] = $message;
            $ressource->store();
            $message = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/campusconnect/ressources/temporary_ressource/".$coursemember->getId();
        }
        return $this->execute($path, $message);
    }

    function getResourceListDetails($path)
    {
        $this->setRequestMethod('GET');
        return $this->execute($path . '/details');
    }

    function getResourceMessageDetails($path, $id)
    {
        $this->setRequestMethod('GET');
        return $this->execute($path . '/details/' . $id);
    }

    function getResourceMessage($path, $id = null)
    {
        $this->setRequestMethod('GET');
        $result = $this->execute($id ? $path . '/' . $id : $path);
        if ($result->getContentType() === "text/uri-list") {
            //wir müssen beim Proxy die Ressource abholen.
            //Auth-Token - Kontext angeben?
            $token = $this->getAuths($result->getSender(), "")->getResult();

            //Ressource abholen
            $url = reset($result->getResult());
            $url = URLHelper::getURL($url, array('ecs_hash' => $token['hash']), true);
            $proxy_client = new EcsClient(array_merge($this->config, array('server' => $url)));
            $proxy_result =  $proxy_client->execute("");
            $proxy_result->setSender($result->getSender());
            return $proxy_result;
        } else {
            return $result;
        }
    }

    function deleteResourceMessage($path, $id)
    {
        $this->setRequestMethod('DELETE');
        return $this->execute($path . '/' . $id);
    }

    //TODO: You must at least specify one of X-EcsReceiverMemberships or X-EcsReceiverCommunities header ???
    function updateResourceMessage($path, $id, $message, $receiver_memberships = null, $receiver_communities = null)
    {
        $this->setRequestMethod('PUT');
        if ($receiver_memberships) {
            if (is_array($receiver_memberships)) {
                $receiver_memberships = join(',', $receiver_memberships);
            }
            $this->setHeader('X-EcsReceiverMemberships', $receiver_memberships);
        }
        if ($receiver_communities) {
            if (is_array($receiver_communities)) {
                $receiver_communities = join(',', $receiver_communities);
            }
            $this->setHeader('X-EcsReceiverCommunities', $receiver_communities);
        }
        return $this->execute($path . '/' . $id, $message);
    }

}
