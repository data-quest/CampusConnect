<?php

class CCLog {

    static public $log_file = null;
    static protected $db = null;

    static public function log($type, $text, $array = null)
    {
        $db = self::getDB();
        $success = $db->exec("
            INSERT INTO cclogs (log_type, log_text, log_json, user_id, mkdate)
            VALUES (".$db->quote($type).",
                ".$db->quote($text).",
                ".$db->quote($array ? json_encode($array) : null).",
                ".$db->quote($GLOBALS['user']->id).",
                ".$db->quote(time()).")
        ");
        if (!$success) {
            throw new Exception(sprintf("CCLog-Fehler in SQLite: ", implode(" ", $db->errorInfo())));
        }
        return $success;
    }

    static public function read($where = "", $params = array())
    {
        $statement = self::getDB()->prepare("
            SELECT *
            FROM cclogs
            ".($where ? "WHERE ".$where : "")."
            ORDER BY log_id DESC
        ");
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function getDB() {
        if (!self::$db) {
            if (!self::$log_file) {
                if (!empty($GLOBALS['CAMPUSCONNECT_LOGDB'])) {
                    self::$log_file = $GLOBALS['CAMPUSCONNECT_LOGDB'];
                } else {
                    self::$log_file = $GLOBALS['TMP_PATH']."/studip_cc_log.sqlite";
                }
            }
            self::$db = new PDO("sqlite:".self::$log_file);
            self::createDB();
        }
        return self::$db;
    }

    static protected function createDB()
    {
        $tables = self::$db->query(
            "SELECT * FROM sqlite_master WHERE type='table' " .
            "")->fetchAll(PDO::FETCH_ASSOC);
        $log_table_exists = false;
        foreach ($tables as $table) {
            if ($table['name'] === "cclogs") {
                $log_table_exists = true;
            }
        }
        if (!$log_table_exists) {
            self::$db->exec("
                CREATE TABLE cclogs (
                    log_id INTEGER PRIMARY KEY,
                    log_type TEXT NOT NULL,
                    log_text TEXT NULL,
                    log_json TEXT NULL,
                    user_id TEXT NOT NULL,
                    mkdate BIGINT NOT NULL
                )
            ");
        }
    }

}
