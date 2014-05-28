<?php

class CampusConnectConfig extends SimpleORMap
{
    protected static $types = array();

    static public function findByType($type)
    {
        if (!self::$types[$type]) {
            self::$types[$type] = self::findBySQL('type = ?', array($type));
        }
        return self::$types[$type];
    }

    function __construct($id = null)
    {
        $this->db_table = 'campus_connect_config';
        $this->registerCallback('before_store', 'cbSerializeData');
        $this->registerCallback('after_store after_initialize', 'cbUnserializeData');
        parent::__construct($id);
    }

    function cbSerializeData()
    {
        $this->content['data'] = serialize($this->content['data']);
        $this->content_db['data'] = serialize($this->content_db['data']);
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['data'] = (array)unserialize($this->content['data']);
        $this->content_db['data'] = (array)unserialize($this->content_db['data']);
        return true;
    }

    function store()
    {
        $ret = parent::store();
        if ($ret) {
            unset(self::$types[$this['type']]);
        }
        return $ret;
    }
}