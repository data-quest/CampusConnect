<?php
class AddLogs extends Migration
{
	function up(){
        Config::get()->delete('CAMPUSCONNECT_LOGFILE');

        DBManager::get()->exec("
            CREATE TABLE `campus_connect_logs` (
                `log_id` char(32) NOT NULL,
                `log_type` varchar(32) NOT NULL,
                `log_text` text DEFAULT NULL,
                `log_json` text DEFAULT NULL,
                `user_id` text NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                `chdate` bigint(20) DEFAULT NULL,
                PRIMARY KEY (`log_id`),
                KEY `log_type` (`log_type`),
                KEY `mkdate` (`mkdate`)
            )
        ");
	}

    function down() {
        DBManager::get()->exec("
            DROP TABLE `campus_connect_logs`
        ");
    }
}
