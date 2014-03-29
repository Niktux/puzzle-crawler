<?php

namespace Puzzle\Crawling;

use Guzzle\Http\Message\Response;

class ResponseParser
{
    private
        $startPatterns,
        $endPatterns,
        $excludePatterns,
        $ignoreQueryString;
    
    public function __construct()
    {
        $this->excludePatterns = array();
        $this->ignoreQueryString = false;
        
        // patterns to exclude urls (externals, resources, mispelled, ...)
        $this->startPatterns = array( 'http://', 'https', '/visual', 'javascript:', '#', 'mailto:', 'www.');
        $this->endPatterns   = array('.css', '.ico', '.tpl', '.png', '.gif', '.jpg', '.jpeg', '.js');
    }
    
    /**
     * @param string $pattern regex
     * @return \Puzzle\Crawling\ResponseParser
     */
    public function addExcludePattern($regexPattern)
    {
        $this->excludePatterns[] = $regexPattern;
        
        return $this;
    }
    
    public function ignoreQueryString($ignore = true)
    {
        $this->ignoreQueryString = (bool) $ignore;
        
        return $this;
    }
    
    public function addStartPattern($stringPattern)
    {
        if(is_string($stringPattern))
        {
            $this->startPatterns[] = $stringPattern;
        }
        
        return $this;
    }
    
    public function addEndPattern($stringPattern)
    {
        if(is_string($stringPattern))
        {
            $this->endPatterns[] = $stringPattern;
        }
        
        return $this;
    }
    
    public function collectUrls(Response $response)
    {
        $html = $response->getBody(true);
        $filteredUrls  = array();
        
        if(! preg_match_all("~href=['\"](.*)['\"]~Usi", $html, $matches))
        {
            return $filteredUrls;
        }
        
        $collectedUrls = $matches[1];
        
    //    echo sprintf("Found %d urls\n", count($collectedUrls));
        
        // remove not crawl targeted urls
        foreach($collectedUrls as $url)
        {
            $url = $this->filterUrl($url);
        
            // empty url => skip
            if(empty($url) )
            {
              //  echo "Empty url detected\n";
                continue;
            }
        
            // matches a start pattern => skip
            foreach($this->startPatterns as $pattern)
            {
                $l = strlen($pattern);
                if(substr($url, 0, $l) === $pattern)
                {
                    continue 2;
                }
            }
        
            // matches an end pattern => skip
            foreach($this->endPatterns as $pattern)
            {
                $l = strlen($pattern) * -1;
                if(substr($url, $l) === $pattern)
                {
                    continue 2;
                }
            }
        
            // matches an user exclude pattern => skip
            foreach($this->excludePatterns as $pattern)
            {
                if(preg_match($pattern, $url))
                {
                    continue 2;
                }
            }
        
            $filteredUrls[] = $url;
        }
        
        // optimize result urls list
        $filteredUrls = array_merge(array_unique($filteredUrls));
        
        return $filteredUrls;
    }
    
    private function filterUrl($url)
    {
        $url = trim($url);
        
        $url = $this->removeAnchors($url);
        if($this->ignoreQueryString === true)
        {
            $url = $this->removeQueryString($url);
        }
        
        return $url;
    }
    
    private function removeQueryString($url)
    {
        $pos = stripos($url, '?');
        
        if($pos !== false)
        {
            $url = substr($url, 0, $pos);
        } 
        
        return $url;
    }
    
    private function removeAnchors($url)
    {
        $pos = stripos($url, '#');
        
        if($pos !== false)
        {
            $url = substr($url, 0, $pos);
        }
        
        return $url;
    }
}