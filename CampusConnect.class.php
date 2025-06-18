<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


if (!interface_exists('AdminCourseWidgetPlugin')) {
    interface AdminCourseWidgetPlugin {}
}

class CampusConnect extends StudIPPlugin implements SystemPlugin, StandardPlugin, AdminCourseWidgetPlugin
{

    public function __construct()
    {
        parent::__construct();
        StudipAutoloader::addAutoloadPath(__DIR__ . '/lib');
        if ($GLOBALS['perm']->have_perm("root")) {
            /*******************************************************************
             *                       Einstellungsseiten                        *
             *******************************************************************/
            $navigation = new Navigation($this->getDisplayName(), PluginEngine::getURL($this, array(), "config/index"));
            Navigation::addItem('/admin/campusconnect', $navigation);

            $navigation = new AutoNavigation("Übersicht", PluginEngine::getURL($this, array(), "config/index"));
            Navigation::addItem('/admin/campusconnect/index', $navigation);

            $navigation = new AutoNavigation("Teilnehmer/LMS", PluginEngine::getURL($this, array(), "config/participants"));
            Navigation::addItem('/admin/campusconnect/participants', $navigation);

            $navigation = new AutoNavigation("ECS", PluginEngine::getURL($this, array(), "config/ecs"));
            Navigation::addItem('/admin/campusconnect/ecs', $navigation);

            $navigation = new Navigation("Log", PluginEngine::getURL($this, array(), "log/view"));
            Navigation::addItem('/admin/campusconnect/log', $navigation);
        }

        /*******************************************************************
         *               Notifications für sendenswerte Daten              *
         *******************************************************************/
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidCreatOrUpdate");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidGetMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidDeleteMember");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidCreateOrUpdate");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidDelete");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeSchedule");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeStudyArea");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "CourseDidChangeInstitutes");
        NotificationCenter::addObserver($this, "synchronizeStudipItems", "DatafieldDidUpdate");


        /*******************************************************************
         *               Navigation für Kurse mit Kurs-URLs                *
         *******************************************************************/
        if (Navigation::hasItem("/course") && Context::getId()) {
            $course = new CCCourse(Context::getId());
            $course_urls = $course->getCourseUrls();
            if (count($course_urls) > 0) {
                $tab = new AutoNavigation(_("Lernplattformen"), PluginEngine::getUrl($this, array(), 'courselink/extern'));
                Navigation::addItem("/course/campusconnect_extern", $tab);
            }
        }
    }

    /**
     * Adds a semininar or institute to the stack of objects, that need to be
     * synced via CampusConnect.
     * @param string $event : name of event
     * @param mixed $object : seminar- or institute-object
     * @param mixed $user_data : possible user_data, mostly useless here
     */
    public function synchronizeStudipItems($event, $object, $user_data)
    {
        if (strpos($event, "Course") === 0) {
            $type = "course";
            $id = $object->getId();
        }
        if (strpos($event, "Institute") === 0) {
            $type = "institute";
            $id = $object->getId();
        }
        if (strpos($event, "DataFieldEntry") === 0) {
            $range_id = $object->getRangeID();
            $object_type = $object->structure->getID();
            if ($object_type === "sem") {
                $type = "course";
                $id = $range_id;
            } elseif($object_type === "inst") {
                $type = "institute";
                $id = $range_id;
            }
        }
        if ($type) {
            CampusConnectTriggerStack::add($type, $id);
        }
    }

    public function getDisplayName()
    {
        return _("CampusConnect");
    }

    public function getTabNavigation($course_id)
    {
        $navigation = new Navigation(_("Informationen"), PluginEngine::getURL($this, array(), "courselink/overview"));
        $navigation->addSubNavigation('overview', new AutoNavigation(_("Informationen"), PluginEngine::getURL($this, array(), "courselink/overview")));
        $navigation->addSubNavigation('details', new Navigation(_("Details"), URLHelper::getURL("details.php")));
        return array('main' => $navigation);
    }

    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        return null;
    }

    public function getInfoTemplate($course_id)
    {
        return null;
    }

    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return null;
    }

    /**
     * Returns a list of widgets for the admin courses page.
     *
     * @return AdminCourseOptionsWidget[]
     */
    public function getWidgets(): iterable {
        $widgets = [];

        $widget = new AdminCourseOptionsWidget(
            _('CampusConnect')
        );
        $participants = CCParticipant::findBySQL('`type` = ? AND `active` = 1 ORDER BY `id` ASC ', array('participants'));
        $options = [
            '' => ''
        ];
        foreach ($participants as $participant) {
            if (!empty($participant['data']['export'])) {
                $options['export_' . $participant->id] = _('Exportieren nach ').$participant['data']['name'];
                break;
            }
        }
        foreach ($participants as $participant) {
            if (!empty($participant['data']['import'])) {
                $options['import_' . $participant->id] = _('Importiert von ').$participant['data']['name'];
            }
        }


        $widget->addSelect(
            _("CampusConnect-Filter"),
            'campusconnect',
            $options,
            $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING")
        );
        $widgets['campusconnect'] = $widget;
        return $widgets;
    }

    /**
     * Return the filter values this widget provides. Return an associative
     * array with filter names as indices and filter values as values.
     *
     * @return array
     */
    public function getFilters(): array {
        return [
            'campusconnect' => $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING")
        ];
    }

    /**
     * Apply the set filters to the AdminCourseFilter query.
     *
     * @param AdminCourseFilter $filter
     */
    public function applyFilters(AdminCourseFilter $filter): void
    {
        if ($GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING")) {
            list($way, $participant_id) = explode('_', $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING"));
            if ($way === 'export') {
                $participant = CampusConnectConfig::find($participant_id);
                if ($participant && !empty($participant['data']['export_settings']) && $participant['data']['export_settings']['course_entity_type'] === 'kurslink') {
                    $export_settings = $participant['data']['export_settings'];
                    if ($export_settings['filter_sem_tree_activate']) {
                        $sem_tree_ids = array_keys($export_settings['filter_sem_tree']);
                        $filter->query->join('seminar_sem_tree', "seminar_sem_tree.seminar_id = seminare.Seminar_id", 'INNER JOIN');
                        $filter->query->where('seminar_sem_tree', "seminar_sem_tree.sem_tree_id IN (:sem_tree_ids) ", [
                            'sem_tree_ids' => $sem_tree_ids
                        ]);
                    }
                    if ($export_settings['filter_datafields_activate']) {
                        $datafield_id = $export_settings['filter_datafield'];
                        $filter->query->join('datafields_entries', "datafields_entries.range_id = seminare.Seminar_id", 'INNER JOIN');
                        $filter->query->where('datafields_entries', "datafields_entries.datafield_id = :cc_datafield_id AND datafields_entries.content != '' AND datafields_entries.content != '0' AND datafields_entries.content IS NOT NULL", [
                            'cc_datafield_id' => $datafield_id
                        ]);
                    }
                }
            } else {
                list($way, $participant_id) = explode('_', $GLOBALS['user']->cfg->getValue("CAMPUSCONNECT_FILTER_SETTING"));
                if ($way === 'import') {
                    $filter->query->join('campus_connect_entities', "campus_connect_entities.item_id = seminare.Seminar_id AND campus_connect_entities.type = 'course'", 'INNER JOIN');
                    $filter->query->where('campus_connect_import_participants', "campus_connect_entities.participant_id = :participant_id", [
                        'participant_id' => $participant_id
                    ]);
                }
            }

        }
    }


    /**
     * Set filters from the admin course page. You will be given an associative
     * array according to getFilters().
     *
     * @param array $filters
     */
    public function setFilters(array $filters): void
    {
        foreach ($filters as $name => $value) {
            if ($name === 'campusconnect') {
                $GLOBALS['user']->cfg->store("CAMPUSCONNECT_FILTER_SETTING", $value);
            }
        }
    }
}
