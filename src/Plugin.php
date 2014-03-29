<?php

namespace Puzzle\Crawling;

use Guzzle\Http\Message\Response;

interface Plugin
{
    public function preCrawl();
    
    public function process(Response $response);
    
    public function postCrawl();
}