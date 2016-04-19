<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CampusConnectHelper.php";

class CampusConnectHelperTestCase extends UnitTestCase {


    function setUp()
    {

    }


    function tearDown()
    {
    }


    function test_utf8_array_encode_decode()
    {
        $problem_string1 = "überhaupt kein Problem";
        $problem_string2 = "äÜÖ€%6hg#'";
        $data = array('test' => $problem_string1, 'tjä' => array($problem_string2));

        //zuerst testen, ob diese Datei den richtigen Zeichensatz != utf8 hat
        //$wrong_encoded = (array) json_decode(json_encode($data));
        //$this->assertEqual($wrong_encoded['test'], null);
        //geht nicht, wirft drei Exceptions

        $still_wrong_encoded = (array) json_decode(json_encode(
            CampusConnectHelper::rec_utf8_encode($data))
        );
        //hier sollte $still_wrong_encoded noch in utf8 codiert sein
        $this->assertNotEqual($still_wrong_encoded['test'], $problem_string1);
        //aber trotzdem nicht null, also ein String
        $this->assertIsA($still_wrong_encoded['test'], "string");

        //und nun wird es korrekt zurück codiert und sollte ein hübsches Array
        //in windows-1252 sein
        $correctly_encoded = CampusConnectHelper::rec_utf8_decode(
            (array) json_decode(json_encode(
                CampusConnectHelper::rec_utf8_encode($data))
            )
        );
        $this->assertEqual($correctly_encoded['test'], $problem_string1);
        //und auch die Indizes sind codiert worden
        $this->assertEqual($correctly_encoded['tjä'][0], $problem_string2);

    }

    function test_rec_array_merge()
    {
        $array1 = array(
            'attr1' => "wow",
            'attr2' => "yeah",
            'attr3' => array('yeah' => 1, 'check' => "safe")
        );
        $array2 = array(
            'attr1' => "woohoo",
            'attr4' => "groovy",
            'attr3' => array('yeah' => 2)
        );
        $merged = CampusConnectHelper::rec_array_merge($array1, $array2);
        $this->assertIsA($merged, "array");
        $this->assertEqual($merged['attr1'], "woohoo");
        $this->assertEqual($merged['attr2'], "yeah");
        $this->assertEqual($merged['attr4'], "groovy");
        $this->assertIsA($merged['attr3'], "array");
        $this->assertEqual($merged['attr3']['check'], "safe");
        $this->assertEqual($merged['attr3']['yeah'], 2);

        /*
        $array1 = array(
            "boom", "bam", "baaaa"
        );
        $array2 = array(
            "bam", "booo"
        );
        $merged = CampusConnectHelper::rec_array_merge($array1, $array2);
        //nur vier Elemente, da "bam" ja doppelt auftauchte und nicht zweimal
        //gespeichert werden sollte.
        $this->assertEqual(count($merged), 4);
        */
    }

}


