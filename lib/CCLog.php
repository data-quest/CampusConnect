<?php

class CCLog extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'campus_connect_logs';
        $config['serialized_fields']['log_json'] = JSONArrayObject::class;
        parent::configure($config);
    }



    static public function log($type, $text, string $subtext = '')
    {
        $logentry = new static();
        $logentry->log_type = $type;
        $logentry->log_text = $text;
        $logentry->log_json = $subtext ? [[$subtext, time()]] : [];
        $logentry->user_id = User::findCurrent()->id;
        $logentry->store();
        return $logentry;
    }

    public function addLog(string $subtext) {
        $this->log_json[] = [$subtext, time()];
        $this->store();
        return $this;
    }

}
