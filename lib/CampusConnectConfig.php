<?php

class CampusConnectConfig extends SimpleORMap
{
    protected static $types = array();

    static protected function configure($config = array())
    {
        $config['db_table'] = 'campus_connect_config';
        /*$config['registered_callbacks']['before_store'][] = "cbSerializeData";
        $config['registered_callbacks']['after_store'][] = "cbUnserializeData";
        $config['registered_callbacks']['after_initialize'][] = "cbUnserializeData";*/
        $config['serialized_fields']['data'] = JSONArrayObject::class;
        parent::configure($config);
    }

    static public function findByType($type)
    {
        if (empty(self::$types[$type])) {
            self::$types[$type] = self::findBySQL('type = ?', array($type));
        }
        return self::$types[$type];
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
