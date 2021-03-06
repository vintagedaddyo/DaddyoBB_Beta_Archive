<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright � 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:29 19.12.2008
  */

class MyLanguage
{

	/**
	 * The path to the languages folder.
	 *
	 * @var string
	 */
	var $path;

	/**
	 * The language we are using.
	 *
	 * @var string
	 */
	var $language;

	/**
	 * Information about the current language.
	 *
	 * @var array
	 */
	var $settings;

	/**
	 * Set the path for the language folder.
	 *
	 * @param string The path to the language folder.
	 */
	function set_path($path)
	{
		$this->path = $path;
	}

	/**
	 * Check if a specific language exists.
	 *
	 * @param string The language to check for.
	 * @return boolean True when exists, false when does not exist.
	 */
	function language_exists($language)
	{
		$language = preg_replace("#[^a-z0-9\-_]#i", "", $language);
		if(file_exists($this->path."/".$language.".php"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Set the language for an area.
	 *
	 * @param string The language to use.
	 * @param string The area to set the language for.
	 */
	function set_language($language="english", $area="user")
	{
		global $daddyobb;
		
		$language = preg_replace("#[^a-z0-9\-_]#i", "", $language);

		// Default language is English.
		if($language == "")
		{
			$language = "english";
		}
		
		// Check if the language exists.
		if(!$this->language_exists($language))
		{
			die("Language $language ($this->path/$language) is not installed");
		}
		
		$this->language = $language;
		require $this->path."/".$language.".php";
		$this->settings = $langinfo;

		// Load the admin language files as well, if needed.
		if($area == "admin")
		{
			if(!is_dir($this->path."/".$language."/{$area}"))
			{
				if(!is_dir($this->path."/".$daddyobb->settings['cplanguage']."/{$area}"))
				{
					if(!is_dir($this->path."/english/{$area}"))
					{
						die("Your forum does not contain an Administration set. Please reupload the english language administration pack.");
					}
					else
					{
						$language = "english";
					}
				}
				else
				{
					$language = $daddyobb->settings['cplanguage'];
				}
			}
			$this->language = $language."/{$area}";
		}
	}

	/**
	 * Load the language variables for a section.
	 *
	 * @param string The section name.
	 * @param boolean Is this a datahandler?
	 * @param boolean supress the error if the file doesn't exist?
	 */
	function load($section, $isdatahandler=false, $supress_error=false)
	{
		// Assign language variables.
		// Datahandlers are never in admin lang directory.
		if($isdatahandler === true)
		{
			$this->language = str_replace('/admin', '', $this->language);
		}
		$lfile = $this->path."/".$this->language."/".$section.".lang.php";
		
		if(file_exists($lfile))
		{
			require_once $lfile;
		}
		elseif(file_exists($this->path."/english/".$section.".lang.php"))
		{
			require_once $this->path."/english/".$section.".lang.php";
		}
		else
		{
			if($supress_error != true)
			{
				die("$lfile does not exist");
			}
		}
		
		if(is_array($l))
		{
			foreach($l as $key => $val)
			{
				if(empty($this->$key) || $this->$key != $val)
				{
					$this->$key = $val;
				}
			}
		}
	}
	
	function sprintf($string)
	{
		$arg_list = func_get_args();
		$num_args = count($arg_list);
		
		for($i = 1; $i < $num_args; $i++)
		{
			$string = str_replace('{'.$i.'}', $arg_list[$i], $string);
		}
		
		return $string;
	}

	/**
	 * Get the language variables for a section.
	 *
	 * @param boolean Admin variables when true, user when false.
	 * @return array The language variables.
	 */
	function get_languages($admin=0)
	{
		$dir = @opendir($this->path);
		while($lang = readdir($dir))
		{
			$ext = my_strtolower(get_extension($lang));
			if($lang != "." && $lang != ".." && $ext == "php")
			{
				$lname = str_replace(".".$ext, "", $lang);
				require $this->path."/".$lang;
				if(!$admin || ($admin && $langinfo['admin']))
				{
					$languages[$lname] = $langinfo['name'];
				}
			}
		}
		@ksort($languages);
		return $languages;
	}

	/**
	 * Parse contents for language variables.
	 *
	 * @param string The contents to parse.
	 * @return string The parsed contents.
	 */
	function parse($contents)
	{
		$contents = preg_replace("#<lang:([a-zA-Z0-9_]+)>#e", "\$this->$1", $contents);
		return $contents;
	}
}
?>