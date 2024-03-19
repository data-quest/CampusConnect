<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once 'lib/models/Institute.class.php';

class CCRessources extends SimpleORMap
{

    static protected function configure($config = array())
    {
        $config['db_table'] = 'campus_connect_ressources';
        $config['registered_callbacks']['before_store'][] = "cbSerializeData";
        $config['registered_callbacks']['after_store'][] = "cbUnserializeData";
        $config['registered_callbacks']['after_initialize'][] = "cbUnserializeData";
        parent::configure($config);
    }

    function cbSerializeData()
    {
        $this->content['data'] = json_encode(studip_utf8encode($this->content['data']));
        $this->content_db['data'] = json_encode(studip_utf8encode($this->content_db['data']));
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['data'] = studip_utf8decode((array) json_decode($this->content['data']));
        $this->content_db['data'] = studip_utf8decode((array) json_decode($this->content_db['data']));
        return true;
    }
}
