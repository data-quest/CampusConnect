<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CCStudyArea extends StudipStudyArea
{

    public function delete()
    {
        $id = $this->getId();
        $success = parent::delete();
        DBManager::get()->exec("DELETE FROM seminar_sem_tree WHERE sem_tree_id = ".DBManager::get()->quote($id));
        return $success;
    }
}
