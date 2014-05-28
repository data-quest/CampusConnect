<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'lib/bootstrap.php';
require_once 'config/config_local.inc.php';
require_once dirname(__file__)."/MockPDO.class.php";
require_once 'lib/classes/DBManager.class.php';
$CACHING_ENABLE = false;
DBManager::getInstance()
    ->setConnection(
        'studip',
        new MockPDO(
            'mysql:host='.$GLOBALS['DB_STUDIP_HOST'].
            ';dbname='.$GLOBALS['DB_STUDIP_DATABASE'],
            $GLOBALS['DB_STUDIP_USER'],
            $GLOBALS['DB_STUDIP_PASSWORD']
        )
    );
$db = DBManager::get();
$db->dropMockTables();
foreach (preg_split("/;\s*\n/", file_get_contents(dirname(__FILE__) . '/../../../../../db/studip.sql')) as $statement) {
    if (trim($statement)) {
        $db->exec($statement);
    }
}