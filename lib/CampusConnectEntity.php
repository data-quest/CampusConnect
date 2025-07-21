<?php

class CampusConnectEntity extends SimpleORMap
{

    static protected function configure($config = array())
    {
        $config['db_table'] = 'campus_connect_entities';
        $config['belongs_to']['participant'] = [
            'class_name' => CampusConnectConfig::class,
            'foreign_key' => 'participant_id'
        ];
        $config['registered_callbacks']['before_store'][] = "cbSerializeData";
        $config['registered_callbacks']['after_store'][] = "cbUnserializeData";
        $config['registered_callbacks']['after_initialize'][] = "cbUnserializeData";
        parent::configure($config);
    }

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
