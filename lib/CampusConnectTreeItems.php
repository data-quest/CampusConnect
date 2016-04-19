<?php

require_once __DIR__."/CCStudyArea.php";

class CampusConnectTreeItems extends SimpleORMap
{

    public $children = array();

    static public function findBySemTreeId($sem_tree_id)
    {
        $result = self::findBySQL("mapped_sem_tree_id = ?", array($sem_tree_id));
        return $result[0];
    }

    function __construct($id = null)
    {
        $this->db_table = 'campus_connect_tree_items';
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
        $this->content['data'] = (array) unserialize($this->content['data']);
        $this->content_db['data'] = (array) unserialize($this->content_db['data']);
        return true;
    }

    public function delete() {
        if ($this['mapped_sem_tree_id']) {
            $study_area = new CCStudyArea($this['mapped_sem_tree_id']);
            $study_area->delete();
        }
        return parent::delete();
    }


    public function map($sem_tree_id)
    {
        if ($sem_tree_id) {
            $this->attach($sem_tree_id);
        } else {
            if ($this['parent_id']) {
                $this['sem_tree_id'] = null;
                $this->store();
                $parent = new CampusConnectTreeItems(array($this['parent_id'], $this['participant_id']));
                if ($parent['mapped_sem_tree_id']) {
                    $this->attach_softly($parent['mapped_sem_tree_id']);
                } else {
                    $this->detach();
                }
            } else {
                $this->detach();
            }
        }
    }

    public function map_softly()
    {
        if ($this['mapped_sem_tree_id']) {
            $this->attach_softly($this['mapped_sem_tree_id']);
        } else {
            if ($this['parent_id']) {
                $parent = new CampusConnectTreeItems(array($this['parent_id'], $this['participant_id']));
                if ($parent['mapped_sem_tree_id']) {
                    $this->attach_softly($parent['mapped_sem_tree_id']);
                } else {
                    $this->detach_softly();
                }
            } else {
                $this->detach_softly();
            }
        }
    }

    protected function attach($sem_tree_id)
    {
        $sem_tree = new CCStudyArea($this['mapped_sem_tree_id']);
        $new = $sem_tree->isNew();
        if ((!$sem_tree['name'] || false) && !$sem_tree['studip_object_id']) {
            $sem_tree['name'] = $this['title'];
        }
        if (true) {
            $sem_tree['priority'] = $this['data']['order'];
        }
        $sem_tree['parent_id'] = $sem_tree_id;
        $sem_tree->store();

        $this['sem_tree_id'] = $sem_tree_id;
        $this['mapped_sem_tree_id'] = $sem_tree->getId();
        $this->store();

        foreach ($this->getChildren() as $childnode) {
            $childnode->attach_softly($sem_tree->getId());
        }

        //Kurse eintragen
        $this->match_courses();
    }

    protected function attach_softly($sem_tree_id)
    {
        if (!$this['sem_tree_id']) { //wenn nicht anders gemapped
            $sem_tree = new CCStudyArea($this['mapped_sem_tree_id']);
            $new = $sem_tree->isNew();
            if ((!$sem_tree['name'] || false) && !$sem_tree['studip_object_id']) {
                $sem_tree['name'] = $this['title'];
            }
            if (true) {
                $sem_tree['priority'] = $this['data']['order'];
            }
            $sem_tree['parent_id'] = $sem_tree_id;
            $sem_tree->store();

            $this['mapped_sem_tree_id'] = $sem_tree->getId();
            $this->store();

            foreach ($this->getChildren() as $childnode) {
                $childnode->attach_softly($sem_tree->getId());
            }
            
            //Kurse eintragen
            $this->match_courses();
        }
    }

    protected function match_courses()
    {
        $courses = CampusConnectEntity::findBySQL("type = 'course' AND participant_id = ? ", array($this['participant_id']));
        $db = DBManager::get();
        $old_seminars = $db->query(
            "SELECT seminar_sem_tree.seminar_id " .
            "FROM seminar_sem_tree " .
                "INNER JOIN campus_connect_entities ON (campus_connect_entities.type = 'course' AND campus_connect_entities.item_id = seminar_id) " .
            "WHERE seminar_sem_tree.sem_tree_id = ".$db->quote($this['mapped_sem_tree_id'])." " .
                "AND campus_connect_entities.participant_id = ".$db->quote($this['participant_id'])." " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        $new_seminars = array();
        foreach ($courses as $course) {
            foreach ($course['data']['degreeProgrammes'] as $degree_programmes) {
                if ($degree_programmes['id'] == $this['item_id']) {
                    $new_seminars[] = $course['item_id'];
                    $db->exec(
                        "INSERT IGNORE INTO seminar_sem_tree " .
                        "SET seminar_id = ".$db->quote($course['item_id']).", " .
                            "sem_tree_id = ".$db->quote($this['mapped_sem_tree_id'])." " .
                    "");
                }
            }
        }
        //Ehemalige Kurse wieder austragen
        foreach (array_diff($old_seminars, $new_seminars) as $old_seminar_id) {
            $db->exec(
                "DELETE FROM seminar_sem_tree " .
                "WHERE seminar_id = ".$db->quote($old_seminar_id)." " .
                    "AND sem_tree_id = ".$db->quote($this['mapped_sem_tree_id'])." " .
            "");
        }
    }

    protected function detach()
    {
        $this['sem_tree_id'] = null;
        $this->store();
        $this->detach_softly();
    }

    protected function detach_softly()
    {
        if (!$this['sem_tree_id']) {
            $sem_tree = new CCStudyArea($this['mapped_sem_tree_id']);
            if (!$sem_tree->isNew()) {
                $sem_tree->delete();
            }
            $this['mapped_sem_tree_id'] = null;
            $this->store();
            foreach ($this->getChildren() as $childnode) {
                $childnode->detach_softly();
            }
        }
    }
    
    public function getChildren() 
    {
        return CampusConnectTreeItems::findBySQL("parent_id = ? AND participant_id = ?", array($this['item_id'], $this['participant_id']));
    }
}