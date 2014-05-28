<?php

class CampusConnectTree extends SimpleORMap
{

    protected $tree_items = null;

    static public function findByForeignID($root_id, $participant_id) {
        $tree = self::findBySQL("root_id = ? AND participant_id = ?", array($root_id, $participant_id));
        if ($tree) {
            return $tree[0];
        } else {
             $tree = new CampusConnectTree();
             $tree['root_id'] = $root_id;
             $tree['participant_id'] = $participant_id;
             return $tree;
        }
    }

    function __construct($id = null)
    {
        $this->db_table = 'campus_connect_trees';
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

    public function getNodes($parent_id = null)
    {
        if ($this->tree_items === null) {
            $this->gecoverTreeItems();
        }
        if ($parent_id === null) {
            foreach ($this->tree_items as $node) {
                if (!$node['parent_id']) {
                    return array($node);
                }
                $ret[] = $node;
            }
            return $ret;
        } else {
            $ret = array();
            foreach ($this->tree_items[$parent_id]->children as $node_id) {
                $ret[] = $this->tree_items[$node_id];
            }
            return $ret;
        }
    }

    private function gecoverTreeItems()
    {
        $items = CampusConnectTreeItems::findBySQL("root_id = ? AND participant_id = ?", array($this['root_id'], $this['participant_id']));
        $ret = array();
        foreach ($items as $item) {
            $ret[$item['item_id']] = $item;
        }
        foreach ($ret as $value) {
            if ($value['parent_id'] && isset($ret[$value['parent_id']])) {
                $ret[$value['parent_id']]->children[] = $value['item_id'];
            }
        }
        $this->tree_items = $ret;
    }

    public function map($tree_item = null)
    {
        $participant = new CCParticipant($this['participant_id']);
        if ($tree_item['mapped_sem_tree_id']) {
            //Item wurde schon gemapped:
            $study_area = new CCStudyArea($tree_item['mapped_sem_tree_id']);
            $study_area['priority'] = $tree_item['data']['order'];
            if ($participant['data']['import_settings']['directory_tree']['override_title']) {
                $study_area['name'] = $tree_item['title'];
            }
            $study_area->store();
        } elseif ($tree_item['parent_id']
                && !$tree_item['mapped_sem_tree_id']
                && ($tree_item['parent_id'] !== $tree_item['root_id'])) {
            //item ist neu und wird eventuell in den sem_tree gemapped
            $parent_item = new CampusConnectTreeItems(array($tree_item['parent_id'], $this['participant_id']));
            if ($parent_item['mapped_sem_tree_id']) {
                $study_area = new CCStudyArea();
                $study_area['parent_id'] = $parent_item['mapped_sem_tree_id'];
                $study_area['priority'] = $tree_item['data']['order'];
                $study_area['name'] = $tree_item['title'];
                $study_area['type'] = 0;
                $study_area->store();
                
                $tree_item->map_softly();
            }
        } elseif ($tree_item['parent_id']
                && !$tree_item['mapped_sem_tree_id']
                && ($tree_item['parent_id'] === $tree_item['root_id'])) {
            $study_area = new CCStudyArea();
            $study_area['parent_id'] = $this['sem_tree_id'];
            $study_area['priority'] = $tree_item['data']['order'];
            $study_area['name'] = $tree_item['title'];
            $study_area['type'] = 0;
            $study_area->store();

            $tree_item->map_softly();
        }
    }
}