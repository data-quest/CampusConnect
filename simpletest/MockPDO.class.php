<?php

/**
 * Ein PDO-Wrapper, um alle Requests auf die virtuelle Test-Datenbank umzuleiten
 */

require_once 'lib/classes/TextFormat.php';

class MockPDO extends StudipPDO {

    protected static $table_prefix = "mock_db_";
    protected $lastQuery = "";

    public function query($sql, $fetch_mode = NULL, ...$fetch_args) {
        $sql = $this->alterSQL($sql);
        return parent::query($sql);
    }

    public function exec($sql) {
        $sql = $this->alterSQL($sql);
        return parent::exec($sql);
    }

    public function prepare($sql, $driver_options = array()) {
        $sql = $this->alterSQL($sql);
        $statement = parent::prepare($sql, $driver_options);
        return $statement;
    }

    protected function alterSQL($sql) {
        //Alle Tabellennamen im SQL-statement werden ersetzt durch mock_db_<tabellennamen>
        $forbidden_tables = array("IF", "FROM");
        $table_names = array();
        preg_match_all("/(?:TABLE\sIF\sNOT\sEXISTS|TABLE\sIF\sEXISTS|TABLE|FROM|JOIN|INTO|UPDATE)\s+([^\s\.\-`]+)/", $sql, $matches1);
        preg_match_all("/(?:TABLE\sIF\sNOT\sEXISTS|TABLE\sIF\sEXISTS|TABLE|FROM|JOIN|INTO|UPDATE)\s*`([^\s`]+)`/", $sql, $matches2);
        foreach ($matches1[1] as $table_name) {
            if (!in_array($table_name, $table_names) && !in_array(strtoupper($table_name), $forbidden_tables)) {
                $table_names[] = $table_name;
            }
        }
        foreach ($matches2[1] as $table_name) {
            if (!in_array($table_name, $table_names) && !in_array(strtoupper($table_name), $forbidden_tables)) {
                $table_names[] = $table_name;
            }
        }

        $sql_parser = new TextFormat();
        $sql_parser->addMarkup("string_quote1", '"', '"', function ($markup, $matches, $contents) {
            return '"'.$contents.'"';
        });
        $sql_parser->addMarkup("string_quote2", "'", "'", function ($markup, $matches, $contents) {
            return "'".$contents."'";
        });

        foreach ($table_names as $table_name) {
            $sql_parser->addMarkup(
                "rename_".$table_name,
                "([^\w_\-])(".$table_name.")([^\w_\-]|$)",
                "",
                function ($markup, $matches) {
                    return $matches[1].MockPDO::getTablePrefix().$matches[2].$matches[3];
                }
            );
        }
        $sql = $sql_parser->format($sql);

        $this->lastQuery = $sql;
        return $sql;
    }

    public function getLastQuery() {
        return $this->lastQuery;
    }

    public function dropMockTables() {
        $mock_tables = $this->query("SHOW TABLES LIKE '".MockPDO::getTablePrefix()."%'")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($mock_tables as $table) {
            parent::exec("DROP TABLE `".addslashes($table)."` ");
        }
    }

    static function getTablePrefix() {
        return self::$table_prefix;
    }

}
