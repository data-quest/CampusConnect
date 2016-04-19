<?php

# Copyright (c)  2007-2012 - Marcus Lunzenauer <mlunzena@uos.de>, Rasmus Fuhse <fuhse@data-quest.de>
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.


# set error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

# set include path
$inc_path = ini_get('include_path');
$inc_path .= PATH_SEPARATOR . dirname(__FILE__) . '/../../../../..';
$inc_path .= PATH_SEPARATOR . dirname(__FILE__) . '/../../../../../config';
ini_set('include_path', $inc_path);

# load required files
require_once dirname(__file__).'/unit_tester.php';
require_once dirname(__file__).'/reporter.php';
require_once dirname(__file__).'/collector.php';
require_once dirname(__file__).'/mock_objects.php';

# load varstream for easier filesystem testing
require_once dirname(__file__).'/varstream.php';

# load DB Mock
include dirname(__file__)."/loadDBMock.php";
$GLOBALS['testing'] = true;

# collect all tests
$all = new TestSuite('CampusConnect-Tests');
$collector = new SimplePatternCollector('/test[A-Z|a-z]{0,2}\.php$/');
$folder = dirname(__file__)."/../";
if (file_exists($folder . '/tests')) {
    $all->collect($folder . '/tests', $collector);
}

$all->run(new TextReporter());

//Abräumen der mock_db_ Tabellen:
DBManager::get()->dropMockTables();