<?php
class AddLogfileConfig extends Migration
{
	function up(){
        Config::get()->create('CAMPUSCONNECT_LOGFILE', array(
            'value' => 'studip.log',
            'is_default' => 'studip.log',
            'type' => 'string',
            'range' => 'global',
            'section' => 'global',
            'description' => _('Pfad zur CampusConnect-Logdatei unterhalb des Temp-Ordners')
        ));
	}
}