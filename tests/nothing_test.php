<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */



class NothingTestCase extends UnitTestCase {

    function test_actually_nothing() {
        $this->assertIsA(array("hell yeah!"), "array");
        $this->assertEqual("1", 1);
    }

}


