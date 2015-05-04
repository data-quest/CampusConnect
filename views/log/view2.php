<?php

/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

/*
if (file_exists($logfile)) {
    $log = file_get_contents($logfile);
    if (substr_count($log, "\n\n") > 20) {
        $pos = strrpos($log, "\n\n");
        $log = substr($log, $pos + 1);
    }
    preg_match_all("/\n+(?P<timestamp>\d{4}\-\d{2}-\d{2}T\d{2}\:\d{2}\:\d{2}\+\d{2}\:\d{2})\s+\[(?P<type>\w+)\](?P<message>.*)/", $log, $logentries);
    foreach ($logentries as $entry) {
        //echo nl2br(htmlReady($entry));
        var_dump($entry);
        echo "<hr>";
    }
} else {
    echo _("Konnte keine Logdatei finden. Entweder sie existiert nicht oder es wurde ein spezieller Log-Handler angegeben.");
}*/


?>
<h1><?= htmlReady($logfile) ?></h1>
<? if (file_exists($logfile)) : ?>
<?= nl2br(htmlReady(file_get_contents($logfile))) ?>
<? else : ?>
<?= _("Konnte keine Logdatei finden.") ?>
<? endif ?>