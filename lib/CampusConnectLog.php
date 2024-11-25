<?php
/**
 * CampusConnectLog.php
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class CampusConnectLog
{

    const URGENT = 0; // It's an emergency
    const ALERT = 1; // Immediate action required
    const CRITICAL = 2; // Critical conditions
    const ERROR = 3; // An error occurred
    const WARNING = 4; // Something unexpected happening
    const NOTICE = 5; // Something worth noting
    const INFO = 6; // Information, not an error
    const DEBUG = 7; // Debugging messages


    private $log_handler = null;

    private $log_level = 6;
    private $log_level_names = array();

    private $file = null;

    private static $instances = array();

    public static function get($name = '')
    {
        $name = strlen($name) ? $name : 0;
        if ($name === 0 && !isset(self::$instances[$name])) {
            self::set();
        }
        if (!isset(self::$instances[$name])) {
            throw new InvalidArgumentException('Unknown logger: ' . $name);
        }
        return self::$instances[$name];
    }

    public static function set($name = '', $log_handler = null)
    {
        $name = strlen($name) ? $name : 0;
        $old = null;
        if (isset(self::$instances[$name])) {
            $old = self::$instances[$name];
        }
        self::$instances[$name] = new CampusConnectLog($log_handler);
        return $old;
    }

    public static function __callStatic($name, $arguments)
    {
        if ($name[0] === '_') {
            $log_name = substr($name, 1);
            $message = $arguments[0];
            $level = isset($arguments[1]) ? $arguments[1] : self::ERROR;
            return self::get($log_name)->log($message, $level);
        }
    }

    function __construct($log_handler = null)
    {
        $this->setHandler($log_handler);
        $r = new ReflectionClass($this);
        $this->log_level_names = array_flip($r->getConstants());
    }

    public function setLogLevel($level)
    {
        $old = $this->log_level;
        $this->log_level = $level;
        return $old;
    }

    public function getLogLevel()
    {
        return $this->log_level;
    }

    public function setHandler($log_handler)
    {
        $old = $this->log_handler;
        $this->log_handler = $log_handler;
        if (is_resource($this->file)) {
            fclose($this->file);
        }
        return $old;
    }

    public function getHandler()
    {
        return $this->log_handler;
    }

    public function log($message, $level = 3)
    {
        if ($level <= $this->log_level) {
            $log_level_name = $this->log_level_names[$level];
            if (is_callable($this->log_handler)) {
                $log_handler = $this->log_handler;
                return $log_handler($message, $log_level_name , date('c'));
            } else {
                $logfile = $this->log_handler ? $this->log_handler : $GLOBALS['TMP_PATH'] . '/studip.log';
                $this->file = is_resource($this->file) ? $this->file : @fopen($logfile, 'ab');
                if ($this->file && flock($this->file , LOCK_EX)) {
                    $ret = fwrite($this->file, date('c') . ' ['.$this->log_level_names[$level].'] ' . $message . "\n");
                    flock($this->file, LOCK_UN);
                    return $ret;
                } else {
                    trigger_error(sprintf('Logfile %s could not be opened.', $logfile), E_USER_WARNING);
                }
            }
        }
    }
}

