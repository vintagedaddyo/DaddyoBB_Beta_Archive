<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:41 19.12.2008
 */

/**
 * Memcache Cache Handler
 */
class memcacheCacheHandler
{
	/**
	 * The memcache server resource
	 */
	var $memcache;

	/**
	 * Unique identifier representing this copy of DaddyoBB
	 */
	var $unique_id;
	
	function memcacheCacheHandler()
	{
		if(!function_exists("memcache_connect"))
		{
			die("Your server does not have memcache support enabled.");
		}
	}

	/**
	 * Connect and initialize this handler.
	 *
	 * @return boolean True if successful, false on failure
	 */
	function connect()
	{
		global $daddyobb;

		if(!$daddyobb->config['memcache_host'])
		{
			die("Plesse configure the memcache settings in inc/config.php before attempting to use this cache handler");
		}

		if(!$daddyobb->config['memcache_port'])
		{
			$daddyobb->config['memcache_port'] = "11211";
		}

		$this->memcache = @memcache_connect($daddyobb->config['memcache_host'], $daddyobb->config['memcache_port']);

		if(!$this->memcache)
		{
			die("Unable to connect to the memcache server on {$daddyobb->config['memcache_host']}:{$daddyobb->config['memcache_port']}. Are you sure it is running?");
		}

		// Set a unique identifier for all queries in case other forums are using the same memcache server
		$this->unique_id = md5($daddyobb->settings['bburl']);

		return true;
	}
	
	/**
	 * Retrieve an item from the cache.
	 *
	 * @param string The name of the cache
	 * @param boolean True if we should do a hard refresh
	 * @return mixed Cache data if successful, false if failure
	 */
	
	function fetch($name, $hard_refresh=false)
	{
		$data = memcache_get($this->memcache, $this->unique_id."_".$name);

		if($data === false)
		{
			return false;
		}
		else
		{
			return $data;
		}
	}
	
	/**
	 * Write an item to the cache.
	 *
	 * @param string The name of the cache
	 * @param mixed The data to write to the cache item
	 * @return boolean True on success, false on failure
	 */
	function put($name, $contents)
	{
		return memcache_set($this->memcache, $this->unique_id."_".$name, $contents, MEMCACHE_COMPRESSED);
	}
	
	/**
	 * Delete a cache
	 *
	 * @param string The name of the cache
	 * @return boolean True on success, false on failure
	 */
	function delete($name)
	{
		return memcache_delete($this->memcache, $this->unique_id."_".$name);
	}
	
	/**
	 * Disconnect from the cache
	 */
	function disconnect()
	{
		@memcache_close($this->memcache);
	}
	
	function size_of($name)
	{
		global $lang;
		
		return $lang->na;
	}
}