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
 * Xcache Cache Handler
 */
class xcacheCacheHandler
{
	/**
	 * Unique identifier representing this copy of DaddyoBB
	 */
	var $unique_id;
	
	function xcacheCacheHandler()
	{
		if(!function_exists("xcache_get"))
		{
			die("Xcache needs to be configured with PHP to use the Xcache cache support");
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

		// Set a unique identifier for all queries in case other forums on this server also use this cache handler
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
		if(!xcache_isset($this->unique_id."_".$name))
		{
			return false;
		}
		return xcache_get($this->unique_id."_".$name);
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
		return xcache_set($this->unique_id."_".$name, $contents);
	}
	
	/**
	 * Delete a cache
	 *
	 * @param string The name of the cache
	 * @return boolean True on success, false on failure
	 */
	function delete($name)
	{
		return xcache_set($this->unique_id."_".$name, "", 1);
	}
	
	/**
	 * Disconnect from the cache
	 */
	function disconnect()
	{
		return true;
	}
	
	function size_of($name)
	{
		global $lang;
		
		return $lang->na;
	}
}
?>