<?php
class CampusConnectClient
{
    private $ecsclient;
    private $resources_path = 'campusconnect';
    private $available_resources = array(
        'courselinks',
        'course_urls',
        'course_members',
        'terms',
        'directory_trees',
        'organization_units',
        'courses'
    );
    private $method_map = array(
        'get' => array(
            's' => 'getResourceMessage',
            'p' => 'getResourceList'
        ),
        'getdetailed' => array(
            's' => 'getResourceListDetails',
            'p' => 'getResourceMessageDetails'
        ),
        'create' => array('s' => 'createResourceMessage'),
        'update' => array('s' => 'updateResourceMessage'),
        'delete' => array('s' => 'deleteResourceMessage')
    );
    
    function __construct($ecsclient)
    {
        $this->ecsclient = $ecsclient;
    }
    
    function __call($name, $arguments)
    {
        list($found_prefix, $found_resource, $plural) = array_values($this->parseMethodName($name));
        $method_to_call = $this->method_map[$found_prefix][$plural];
        if (!$method_to_call) {
            throw new MethodNotAllowedException($name . ' is unknown' );
        }
        array_unshift($arguments, '/' . $this->resources_path . '/' . $found_resource);
        return call_user_func_array(array($this->ecsclient, $method_to_call), $arguments);
        //TODO courses
        //This resource representation is of Content-Type: text/uri-list and points to the real course representation on LSF-Proxy
    }
    
    function parseMethodName($method)
    {
        $methodname = strtolower($method);
        $prefixes = array_keys($this->method_map);
        $found_prefix = null;
        foreach($prefixes as $p) {
            $found = strpos($methodname, $p);
            if ($found !== false) {
                $found_prefix = $p;
                $part = substr($methodname, strlen($p));
                break;
            }
        }
        if ($found_prefix) {
            $plural = substr($part,-1) === 's' ? 'p' : 's';
            if ($plural === 's') $part .= 's';
            foreach ($this->available_resources as $r) {
                if ($r === $part || str_replace('_', '', $r) === $part) {
                    $found_resource = $r;
                    break;
                }
            }
        }
        return compact('found_prefix', 'found_resource', 'plural');
    }
}