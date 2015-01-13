<?php

require_once('vendor/autoload.php');

use USF\IdM\UsfLogger;
use USF\IdM\UsfConfig;

$config = new UsfConfig('config');

// Calling UsfLogger with no options creates a loghandler that writes messages to /var/log/usf-logger.log
$logger = new UsfLogger();

// log an error message with extra array data
$logger->log->warn('This is a test message.', ['foo' => 'bar']);

// Create a LogHandler that emails audit reports
$logger->addLogger('audit', 'mail', $config->mailConfig);

//Send an email
$logger->audit->alert('Audit Test', ['foo' => 'bar', 'black' => 'white', 'yes' => 'no']);

?>