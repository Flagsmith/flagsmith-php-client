<?php
require_once './vendor/autoload.php';

use \Flagsmith\Flagsmith;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

const TOKEN = 'M7NT6JDYqhcVgSfJgbnWVY';
const HOST = 'https://api.flagsmith.com/api/v1/';


$flagsmith = new Flagsmith(TOKEN, HOST); // HOST is optional
$flags = $flagsmith->getFlags();

var_dump($flags);

// Nyholm PSR7 implementation
$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
// Symfony PSR18 implementation
$httpClient = Psr18ClientDiscovery::find();

$request = $requestFactory
  ->createRequest('GET', rtrim(HOST, '/') . '/flags/')
  ->withHeader('Accept', 'application/json')
  ->withHeader('Content-Type', 'application/json')
  ->withHeader('X-Environment-Key', TOKEN);

$response = $httpClient->sendRequest($request);

var_dump($response->getBody()->getContents());

// TODO - write more API examples once they are implemented.





