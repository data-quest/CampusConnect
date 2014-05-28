<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CCStatusgruppe extends SimpleORMap {

    public function __construct($id = null) {
        $this->db_table = 'statusgruppen';
        parent::__construct($id);
    }

    public function delete() {
        DBManager::get()->exec(
            "DELETE FROM statusgruppe_user " .
            "WHERE statusgruppe_id = ".DBManager::get()->quote($this->getId())." " .
        "");
        parent::delete();
    }
}