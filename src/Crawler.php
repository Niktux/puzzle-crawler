<?php

namespace Puzzle\Crawling;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\BadResponseException;

class Crawler
{
    private
        $client,
        $startTime,
        $plugins,
        $parser,
        $urlMap,
        $pageLimit,
        $counter,
        $sleepBetweenCalls;
        
    public function __construct(Client $client, UrlMap $map, ResponseParser $parser = null)
    {
        $this->client = $client;
        $this->startTime = null;
        $this->plugins = array();
        $this->urlMap = $map;
        $this->pageLimit = false;
        $this->counter = 0;
        $this->sleepBetweenCalls = 0;
        
        $this->parser = $parser instanceof ResponseParser ? $parser : new ResponseParser();
    }
    
    public function addPlugin(Plugin $plugin)
    {
        $this->plugins[] = $plugin;

        return $this;
    }
    
    public function setPageLimit($limit)
    {
        if(is_int($limit) || $limit === false)
        {
            $this->pageLimit = $limit;
        }
        
        return $this;
    }
    
    public function sleepBetweenCalls($duration = 1)
    {
        if(is_int($duration))
        {
            $this->sleepBetweenCalls = $duration;
        }
        
        return $this;
    }

    public function run($domainName, $startUrl = '/')
    {
        $this->startTimer();
        $this->counter = 0;
        
        echo "CRAWLING $domainName\n";
        
        $this->client->setBaseUrl(
            $this->sanitizeDomainName($domainName)
        );

        $this->preCrawl();
        $this->crawl($startUrl);
        $this->postCrawl();
    }
    
    private function startTimer()
    {
        $this->startTime = microtime(true);
    }
    
    private function sanitizeDomainName($domainName)
    {
        return rtrim($domainName, '/') . '/';
    }
    
    private function preCrawl()
    {
        foreach($this->plugins as $plugin)
        {
            $plugin->preCrawl();
        }
    }
    
    private function postCrawl()
    {
        foreach($this->plugins as $plugin)
        {
            $plugin->postCrawl();
        }
    }
    
    private function crawl($startUrl)
    {
        $this->crawlOnePage($startUrl);

        $crawledPages = 1;
        while($this->pageLimit === false || $crawledPages < $this->pageLimit)
        {
            $url = $this->urlMap->next();
            if($url === false)
            {
                break;
            }
            
            sleep($this->sleepBetweenCalls);
            
            $this->crawlOnePage($url);
            $crawledPages++;
        }
    }
    
    private function crawlOnePage($relativeUrl)
    {
        $this->markAsVisited($relativeUrl);
        $nbUrls = 0;
        
        try 
        {
            $startTime = microtime(true);
            $response = $this->client->get($relativeUrl)->send();
            $receivedTime = microtime(true);
            
            $nbUrls = $this->urlMap->add($this->collectReferencedUrls($response));
            $this->processPlugins($response);
        }
        catch(BadResponseException $e)
        {
            $receivedTime = microtime(true);
            $response = $e->getResponse();
        }
        
        echo sprintf(
            "%4d: %s [%3d] %4d %4d ms   %s\n",
            $this->counter,
            date("H'i's"),
            $response->getStatusCode(),
            $nbUrls,
            ($receivedTime - $startTime) * 1000,
            $relativeUrl
        );
    }
    
    private function markAsVisited($url)
    {
        $this->urlMap->markAsVisited($url);
        $this->counter++;
    }
    
    private function collectReferencedUrls(Response $response)
    {
        $urls = $this->parser->collectUrls($response);

        return $urls;
    }
    
    private function processPlugins(Response $response)
    {
        foreach($this->plugins as $plugin)
        {
            $plugin->process($response);
        }
    }
}