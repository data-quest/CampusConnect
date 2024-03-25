<?php

class CampusConnectStudyAreaSelector {

    protected $id;
    protected $name;
    protected $type;
    protected $defaults = array();
    protected $style = array();

    static public function create($name, $type = "single")
    {
        $selector = new CampusConnectStudyAreaSelector($name, $type);
        return $selector;
    }

    public function __construct($name, $type = "single")
    {
        $this->name = $name;
        $this->type = $type;
        $this->setHeight("100px");
        $this->setId(substr(md5(uniqid()), 0, 5));
    }

    public function setDefault($value_s)
    {
        if (is_array($value_s)) {
            $this->defaults = $value_s;
        } else {
            $this->defaults[] = $value_s;
        }
        return $this;
    }

    public function setHeight($height)
    {
        $this->setStyle("height", $height);
        $this->setStyle("max-height", $height);
        $this->setStyle("overflow", "auto");
        return $this;
    }

    public function setStyle($attribute_or_array, $value = null)
    {
        if (is_array($attribute_or_array)) {
            $this->style = array_merge($this->style, $attribute_or_array);
        } else {
            $this->style[$attribute_or_array] = $value;
        }
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function render()
    {
        $entries = \DBManager::get()->prepare("
            SELECT * FROM sem_tree WHERE parent_id = 'root' ORDER BY priority
        ");
        $entries->execute();
        $entries = $entries->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
        $entries = array_map(function ($a) { return $a[0]; }, $entries);

        if (count($this->defaults)) {
            $statement = \DBManager::get()->prepare("
                SELECT * FROM sem_tree WHERE parent_id IN (
                    SELECT parent_id FROM sem_tree WHERE sem_tree_id IN (:sem_tree_ids)
                )
            ");
            $statement->execute(array('sem_tree_ids' => $this->defaults));
            $necessary = $statement->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
            $necessary = array_map(function ($a) { return $a[0]; }, $necessary);
            $i = 0;
            do {
                $parent_ids = array();
                foreach ($necessary as $sem_tree_id => $nec) {
                    if ($nec['parent_id'] !== "root" && !in_array($nec['parent_id'], array_keys($necessary))) {
                        $parent_ids[] = $nec['parent_id'];
                    }
                    if ($necessary[$nec['parent_id']]) {
                        $necessary[$nec['parent_id']]['children'][] = $sem_tree_id;
                        array_unique($necessary[$nec['parent_id']]['children']);
                    }
                }
                array_unique($parent_ids);
                $statement->execute(array('sem_tree_ids' => $parent_ids));
                $more = $statement->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
                $more = array_map(function ($a) { return $a[0]; }, $more);
                $necessary = array_merge($necessary, $more);
                $i++;
            } while (count($parent_ids) && $i < 20);
        }

        $template_factory = new \Flexi_TemplateFactory(__DIR__."/../views/");
        $template = $template_factory->open("study_area_selector/selector.php");
        $template->set_attribute("id", $this->id);
        $template->set_attribute("name", $this->name);
        $template->set_attribute("type", $this->type);
        $template->set_attribute("style", $this->style);
        $template->set_attribute("entries", $entries);
        $template->set_attribute("defaults", $this->defaults);
        $template->set_attribute("necessary", $necessary);
        return $template->render();
    }

    public function renderChildren($sem_tree_id)
    {
        $entries = \DBManager::get()->prepare("
            SELECT * FROM sem_tree WHERE parent_id = :sem_tree_id ORDER BY priority
        ");
        $entries->execute(array('sem_tree_id' => $sem_tree_id));
        $entries = $entries->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
        $entries = array_map(function ($a) { return $a[0]; }, $entries);

        $template_factory = new \Flexi_TemplateFactory(__DIR__."/../views/");
        $template = $template_factory->open("study_area_selector/_children.php");
        $template->set_attribute("id", $this->id);
        $template->set_attribute("name", $this->name);
        $template->set_attribute("type", $this->type);
        $template->set_attribute("entries", $entries);
        return $template->render();
    }
}
