<?php

class CampusConnectEntity extends SimpleORMap
{

    static function findByType($type = "course") {
        return self::findBySQL('type = ?', array($type));
    }

    static public function findByForeignID($type, $foreign_id, $participant_id) {
        $result = self::findBySQL('type = ? AND foreign_id = ? AND participant_id = ? ', array($type, $foreign_id, $participant_id));
        if (count($result) === 1) {
            return $result[0];
        } else {
            return false;
        }
    }

    function __construct($id = null)
    {
        $this->db_table = 'campus_connect_entities';
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
}