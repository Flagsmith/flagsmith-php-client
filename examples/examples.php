<?php

require_once './vendor/autoload.php';

use Flagsmith\Exceptions\FlagsmithClientError;
use Flagsmith\Flagsmith;
use Flagsmith\Models\DefaultFlag;

const TOKEN = '--token--';
const HOST = 'https://api.flagsmith.com/api/v1/';


$flagsmith = new Flagsmith(TOKEN, HOST); // HOST is optional
$flags = $flagsmith->getEnvironmentFlags();

print 'Environment Flags: '.PHP_EOL;
// Get the flag named secret_button
var_dump($flags->getFlag('secret_button'));
// get all flags
var_dump($flags->getFlags());
// set the default handler, returns this object
$flagsmithDefaultHandler = $flagsmith->withDefaultFlagHandler(function () {
    return (new DefaultFlag())->withEnabled(true)->withValue('#333');
});

try {
    print 'Accessing flags without default flag handler throws a FlagsmithClientException for features that do not exist.'.PHP_EOL;
    // throws an exception
    var_dump($flags->getFlag('ABC123'));
} catch (FlagsmithClientError $e) {
    print 'Flagsmith Client error thrown '.PHP_EOL;
}

$flags = $flagsmithDefaultHandler->getEnvironmentFlags();
// returns the secret_button flag.
var_dump($flags->getFlag('secret_button'));
// returns the default flag.
var_dump($flags->getFlag('ABC123'));

print 'Identity Flags for hello_world.'.PHP_EOL;
var_dump($flagsmith->getIdentityFlags('hello_world'));
var_dump($flagsmith->getIdentityFlags('tester'));
var_dump($flagsmith->getIdentityFlags('hello'));
