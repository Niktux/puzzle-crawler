<?php

namespace Puzzle\Crawling;

class UrlMap
{
    const
        SIBLING = 0,
        TOP_BOTTOM = 1,
        RANDOM = 2;
    
    private
        $list,
        $insertMode;
    
    public function __construct(array $urls = array())
    {
        $this->list = array_fill_keys($urls, false);
        
        $this->insertMode = self::SIBLING;
    }
    
    public function setInsertMode($insertMode)
    {
        static $allowedModes = array(self::TOP_BOTTOM, self::SIBLING, self::RANDOM);
        
        if(in_array($insertMode, $allowedModes))
        {
            $this->insertMode = $insertMode;
        }
        
        return $this;
    }
    
    public function add(array $urls)
    {
        $formerCount = count($this->list);
        
        $urls = array_fill_keys($urls, false);
        
        if($this->insertMode === self::SIBLING)
        {
            $this->list += $urls;
        }
        else
        {
            $this->list = array_merge($urls, $this->list);
        }
        
        return count($this->list) - $formerCount; 
    }
    
    public function markAsVisited($url)
    {
        $this->list[$url] = true;
        
        return $this;
    }
    
    public function getUnvisitedUrls()
    {
        $urls = array_keys(array_filter($this->list, function ($alreadyVisited) {
        	return $alreadyVisited === false;
        }));
        
        if($this->insertMode === self::RANDOM)
        {
            shuffle($urls);
        }
        
        return $urls;
    }
    
    public function next()
    {
        return reset($this->getUnvisitedUrls());
    }
}