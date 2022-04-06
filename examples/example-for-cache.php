<?php

require_once './vendor/autoload.php';

use Flagsmith\Flagsmith;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

const TOKEN = 'ser.server-key';
const HOST = 'https://api.flagsmith.com/api/v1/';

$cache = new Psr16Cache(new FilesystemAdapter());

$flagsmith = (new Flagsmith(TOKEN, HOST))->withCache($cache)->withEnvironmentTtl(100);
// Cache the environment call to reduce network calls for each and every evaluation.
$flagsmith->updateEnvironment();

var_dump($flagsmith->getEnvironment());
var_dump($cache->get('flagsmith.Environment'));
