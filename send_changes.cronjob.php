<?php

class SendChangesJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('CampusConnect-Updates (zum ECS)');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Führt die Synchronisation mit dem ECS aus und sendet alle relevanten Änderungen von Stud.IP zum ECS.');
    }

    public function setUp() {
        require_once __DIR__.'/lib/CCParticipant.php';
        require_once __DIR__.'/lib/CampusConnectLog.php';
        require_once __DIR__.'/lib/CampusConnectHelper.php';
        require_once __DIR__.'/lib/EcsClient.php';
        require_once __DIR__.'/lib/EcsResult.php';
        require_once __DIR__.'/lib/CampusConnectClient.php';
        require_once __DIR__.'/lib/CampusConnectTriggerStack.php';
        require_once __DIR__.'/lib/CampusConnectEntity.php';
        require_once __DIR__.'/lib/CampusConnectSentItem.php';
        require_once __DIR__.'/lib/CCCourse.php';
        require_once __DIR__.'/lib/CCRessources.php';
        require_once __DIR__.'/lib/CampusConnector.php';
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
        CampusConnector::send_changes();
    }
}
