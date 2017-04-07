<?php 

require_once '../application/services/AMFTestCase.php';

$testcase = new AMFTestCase();
$services = $testcase->getServices();
print_r($services);
foreach ($services as $service) {
    print_r($testcase->describeService($service));
}


