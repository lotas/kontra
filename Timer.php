<?php

class Timer
{
	private $startTime;
	
	function __construct() {
		$this->startTime = $this->getMicroTime();
	}
	
	function split() {		
		return (float) ($this->getMicroTime() - $this->startTime);
	}
	
	private function getMicroTime() {
		list($usec, $sec) = explode(" ", microtime()); 
		return ((float) $usec + (float) $sec);		
	}
}

?>