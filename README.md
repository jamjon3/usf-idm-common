usf-idm-common
==========

Composer package of common libraries for USF Identity Management services.  This package provides these classes:

* `UsfEncryption` provides AES-256 encryption and decryption routines compatible with Java and C# implentations used at USF.
* `UsfConfig` wraps the [Configula](https://github.com/caseyamcl/Configula) PHP configuration library.
* `UsfLogger` wraps the [Monolog](https://github.com/Seldaek/monolog) PHP logging library.
  * Provides handlers for writing logs to:
    * Files
    * Syslog
    * The [FirePHP](http://www.firephp.org) plugin for Firebug
    * Email, using the [Swift-Mailer](http://swiftmailer.org) library
    * SMS, using [Twilio](http://twilio.com) API
* `UsfLogRegistry` provides a single object holding multiple `UsfLogger` instances.


Installation
----
To install usf-idm-common with composer, add this to your composer.json:

```
{
  "require": {
    "usf-it/usf-idm-common": "0.1.0"
  }
}
```
and run `composer update`.

UsfEncryption
----

PHP must have access to the MCRYPT library for this encryption library to function.  Here is an example of encrypting and decrypting a string:

```php
<?php

require_once ('vendor/autoload.php');

use USF\IdM\UsfEncryption;

//AES-256 requires a 32-character key
$key = "12345678901234561234567890123456";

$crypt = UsfEncryption::encrypt($key, "this is a test");

echo "Encrypted string: ".$crypt."\n";

echo "Decrypted string: ".UsfEncryption::decrypt($key, $crypt);

?>
```

UsfConfig
----
This is a simple wrapper around [Configula](https://github.com/caseyamcl/Configula).

* Scans a given directory for configuration files and merges the data into one configuration object.
* Works with _.php_, _.ini_, _.json_, and _.yml_ configuration file types
* Supports "local" configuration files that override the default configuration files. 
* Simple usage:

```
//Access configuration values from default location (/usr/local/etc/idm_config)
$config = new UsfConfig();
$some_value = $config->some_key;
```

* Array and iterator access to your config settings:

```
//Access conifguration values
$config = new UsfConfig('/path/to/config/files');
foreach ($config as $item => $value) {
  echo "<li>{$item} is {$value}</li>";
}
```

####Notes

* The UsfConfig object, once instantiated, is immutable, meaning that it is read-only.  You can not alter the config values.  You can, however, create as many UsfConfig objects as you would like. 
* If any configuration file contains invalid code (invalid PHP or malformed JSON, for example), the Configula class will not throw an error.  Instead, it will simply skip reading that file.
* When working with PHP files, UsfConfig will look for an array called $config in this file.

####Local Configuration Files

In some cases, you may want to have local configuration files that override the default configuration files. To override any configuration file, create another configuration file, and append .local.EXT to the end.

For example, a configuration file named database.yml is overridden by database.local.yml, if the latter file exists.

This is very useful if you want certain settings included in version control, and certain settings ignored (just add /path/to/config/*.local.EXT to your .gitignore)


UsfLogRegistry
----

This class provides a [singleton](https://en.wikipedia.org/wiki/Singleton_pattern) that contains an array of UsfLogger objects for logging various portions of your application.  Currently, we support logging to files, syslog, [FirePHP](http://www.firephp.org), Email, and SMS.  

In addition, these logging methods can be activated basd on severity - sending generic infomation to log files, while important messages are emailed and critical alerts are sent via SMS.

#####Basic Usage

```php
<?php

require_once ('vendor/autoload.php');

use USF\IdM\UsfLogRegistry;

// Create an instance of UsfLogRegistry
$logger = UsfLogRegistry::getInstance();

// Calling addLogger with no options creates a loghandler named 'log' that 
// writes messages to /var/log/usf-logger.log
$logger->addLogger();

// log an error message with extra array data
$logger->log->warn('This is a test message.', ['foo' => 'bar']);

?>

```

#####Log to different files

```php
<?php

require_once ('vendor/autoload.php');

use USF\IdM\UsfLogRegistry;

// Create an instance of UsfLogRegistry
$logger = UsfLogRegistry::getInstance();
$logger->addLogger();

// Log audit messages to a different file
$logger->addLogger('audit','file',['log_location' => '/var/log/audit_log', 'log_level' => 'info']);

// This message will only go to /var/log/usf-logger.log
$logger->log->warn('This is a test message.');

// This message will only go to /var/log/audit_log
$logger->audit->info('User login',['username'=>$user, 'timestamp' => date(DATE_RFC2822)]);

?>

```

####Logging to multiple handlers based on severity

```php
<?php

require_once ('vendor/autoload.php');

use USF\IdM\UsfLogRegistry;

$logger = UsfLogRegistry::getInstance();
$logger->addLogger();

//Configure email handler
$mailConfig = [
    'log_level' => 'error',
    'host' => 'smtp.gmail.com',
    'port' => 465,
    'username' => 'alert@example.edu',
    'password' => 'secret',
    'subject' => 'Log Message',
    // Who the message will be from
    'from' => ['root@localhost' => 'Admin'],
    // one or more email addresses the logs will go to
    'to' => ['my_address@example.edu']
];

// Add an additional handler that emails critical messages
$logger->log->addLogHandler('mail', $mailConfig);

// Add a log processor that logs the method, class, file and line number of the call
$logger->log-addLogProcessor('introspection');

//This message will be logged to file AND emailed
$logger->log->critical('Critical Problem!!', ['var1' => 'true', 'var2' => 'false']);

?>
```

####Notes

* When used in web applications, the URL, server & remote IPs, HTTP method and referrer are logged automatically.