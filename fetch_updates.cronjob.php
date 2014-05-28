<?php

class FetchUpdatesJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('CampusConnect-Updates');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Führt die Synchronisation mit dem ECS aus.');
    }

    /**
     * Setup method. Loads neccessary classes and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        //require_once dirname(__file__)."/models/ExternalDataURL.class.php";
    }

    /**
     * Return the paremeters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return array();
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = array())
    {
        $plugin = PluginManager::getInstance()->getPlugin("CampusConnect");
        $plugin->perform("connector/send_changes");
    }
}
