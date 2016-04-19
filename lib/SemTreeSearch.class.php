<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest>
 * @copyright   2012 data-quest
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    CampusConnect
*/

require_once 'lib/classes/StudipSemSearchHelper.class.php';

class SemTreeSearch extends SQLSearch
{
    /**
     * returns an object of type SQLSearch with parameters to constructor
     *
     * @param string $search
     *
     * @return SQLSearch
     */
    static public function get()
    {
        return new SemTreeSearch();
    }

    public function __construct($title = "")
    {
        $sql = 
            "SELECT sem_tree_id, name " .
            "FROM sem_tree " .
            "WHERE name LIKE :input " .
                "AND sem_tree_id NOT IN (SELECT DISTINCT mapped_sem_tree_id FROM campus_connect_tree_items WHERE mapped_sem_tree_id IS NOT NULL) " .
        "";
        parent::__construct($sql, $title);
    }

    /**
     * Returns the path to this file, so that this class can be autoloaded and is
     * always available when necessary.
     * Should be: "return __file__;"
     *
     * @return string   path to this file
     */
    public function includePath()
    {
        return __FILE__;
    }
}
