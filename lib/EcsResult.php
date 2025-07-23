<?php

class EcsResult
{
    private $result;
    private $response_code;
    private $response_header;
    private $parsed_result;
    private $sender;
    private $receiver_communities;
    private $content_type;

    function __construct($result, $response_code, $response_header)
    {
        $this->result = $result;
        $this->response_code = $response_code;
        $this->response_header = $response_header;
        if (!$this->isError()) {
            $this->parseResult();
        }
    }

    function getResponseCode()
    {
        return $this->response_code;
    }

    function getResponseHeader()
    {
        return $this->response_header;
    }

    function getRawResult()
    {
        return $this->result;
    }

    function getResult()
    {
        return $this->parsed_result;
    }

    function isError()
    {
        return  !($this->response_code >= 200 && $this->response_code <= 300);
    }

    function getSender() : array
    {
        return (array) $this->sender;
    }

    function setSender($sender)
    {
        $this->sender = $sender;
    }

    function getReceiverCommunities()
    {
        return $this->receiver_communities;
    }

    function getContentType() {
        return $this->content_type;
    }

    function parseResult()
    {
        foreach ($this->response_header as $a => $b) {
            if (strtolower($a) == 'content-type') {
                $this->content_type = strstr($b, ';', true);
            }
            if (stripos($a,'x-ecssender') !== false) {
                $this->sender = array_map('trim', explode(',', $b));
            }
            if (stripos($a,'x-ecsreceivercommunities') !== false) {
                $this->receiver_communities = array_map('trim', explode(',', $b));
            }
            /*if (stripos($a,'location') !== false && $this->response_code == 201) {
                $this->parsed_result = trim($b);
            }*/
        }
        if ($this->content_type == 'text/uri-list')
        {
            $this->parsed_result = array_filter(array_map('trim', explode("\n", $this->result)));
        }
        if ($this->content_type == 'application/json' || !$this->content_type)
        {
            $parsed_result = $this->result ? json_decode($this->result, true) : [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                CCLog::log("PARSE_ERROR", 'json_decode() failed', $this->result);
            }

            $this->parsed_result = $parsed_result;
        }
    }
}
