<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

require_once dirname(__FILE__) . '/../vendor/simpletest/simpletest/autorun.php';

require_once dirname(__FILE__) . '/../src/CodeBuilder.php';

require_once dirname(__FILE__) . '/CodeBuilder/functions.php';
require_once dirname(__FILE__) . '/CodeBuilder/CBSettingsDontShowErrors.php';
require_once dirname(__FILE__) . '/CodeBuilder/CBTestNamingBlock.php';
require_once dirname(__FILE__) . '/CodeBuilder/CodeBuilderTestCase.php';

require_once dirname(__FILE__) . '/CodeBuilder/tests/CodeBuilderTest.php';
require_once dirname(__FILE__) . '/CodeBuilder/tests/CBContainerValueFactoryTest.php';
