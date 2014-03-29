<?php

require 'vendor/autoload.php';

use Puzzle\Crawling\Crawler;
use Guzzle\Http\Client;
use Puzzle\Crawling\UrlMap;
use Puzzle\Crawling\ResponseParser;

$client = new Client();
$map = new UrlMap();
$map->setInsertMode(UrlMap::TOP_BOTTOM);

$parser = new ResponseParser();
$parser
    ->addExcludePattern('~^connexion.php~')
    ->ignoreQueryString()
;

$domainName = $argv[1];

$crawler = new Crawler($client, $map, $parser);
$crawler
    ->setPageLimit(10)
    ->sleepBetweenCalls(0)
    ->run($domainName);

