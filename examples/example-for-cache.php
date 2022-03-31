<?php

require_once './vendor/autoload.php';

use Flagsmith\Flagsmith;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

const TOKEN = '--token--';


$flagsmith = (new Flagsmith(TOKEN))->withCache(new Psr16Cache(new FilesystemAdapter()));
// Cache the environment call to reduce network calls for each and every evaluation.
$flagsmith->updateEnvironment();

