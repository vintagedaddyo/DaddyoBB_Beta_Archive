<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 21:29 19.12.2008
  */

class templates
{
	/**
	 * The total number of templates.
	 *
	 * @var int
	 */
	var $total = 0;

	/**
	 * The template cache.
	 *
	 * @var array
	 */
	var $cache = array();

	/**
	 * Array of templates loaded that were not loaded via the cache
	 *
	 * @var array
	 */
	var $uncached_templates = array();

	/**
	 * Cache the templates.
	 *
	 * @param string A list of templates to cache.
	 */
	function cache($templates)
	{
		global $db, $theme;
		$sql = $sqladd = "";
		$names = explode(",", $templates);
		foreach($names as $key => $title)
		{
			$sql .= " ,'".trim($title)."'";
		}

		$query = $db->simple_select("templates", "title,template", "title IN (''$sql) AND sid IN ('-2','-1','".$theme['templateset']."')", array('order_by' => 'sid', 'order_dir' => 'asc'));
		while($template = $db->fetch_array($query))
		{
			$this->cache[$template['title']] = $template['template'];
		}
	}

	/**
	 * Gets templates.
	 *
	 * @param string The title of the template to get.
	 * @param boolean True if template contents must be escaped, false if not.
	 * @param boolean True to output HTML comments, false to not output.
	 * @return string The template HTML.
	 */
	function old_get($title, $eslashes=1, $htmlcomments=1)
	{
		global $db, $theme, $daddyobb;

		//
		// DEVELOPMENT MODE
		//
		if($daddyobb->dev_mode == 1)
		{
			$template = $this->dev_get($title);
			if($template !== false)
			{
				$this->cache[$title] = $template;
			}
		}
		
		if(!isset($this->cache[$title]))
		{
			$query = $db->simple_select("templates", "template", "title='".$db->escape_string($title)."' AND sid IN ('-2','-1','".$theme['templateset']."')", array('order_by' => 'sid', 'order_dir' => 'DESC', 'limit' => 1));

			$gettemplate = $db->fetch_array($query);
			if($daddyobb->debug_mode)
			{
				$this->uncached_templates[$title] = $title;
			}
			
			if(!$gettemplate)
			{
				$gettemplate['template'] = "";
			}

			$this->cache[$title] = $gettemplate['template'];
		}
		$template = $this->cache[$title];

		if($htmlcomments)
		{
			if($daddyobb->settings['tplhtmlcomments'] == 1)
			{
				$template = "<!-- start: ".htmlspecialchars_uni($title)." -->\n{$template}\n<!-- end: ".htmlspecialchars_uni($title)." -->";
			}
			else
			{
				$template = "\n{$template}\n";
			}
		}
		
		if($eslashes)
		{
			$template = str_replace("\\'", "'", addslashes($template));
		}
		return $template;
	}

	/**
	 * Fetch a template directly from the install/resources/daddyobb_theme.xml directory if it exists (DEVELOPMENT MODE)
	 */
	function dev_get($title)
	{
		static $template_xml;

		if(!$template_xml)
		{
			if(@file_exists(DADDYOBB_ROOT."install/resources/daddyobb_theme.xml"))
			{
				$template_xml = simplexml_load_file(DADDYOBB_ROOT."install/resources/daddyobb_theme.xml");
			}
			else
			{
				return false;
			}
		}
		$res = $template_xml->xpath("//template[@name='{$title}']");
		return $res[0];
	}

  /** If-Conditions in Templates by ZiNgA BuRgA (Yumi) **/

  function phptpl_templates(&$oldtpl)
	{
      global $daddyobb;
       
		$vars = get_object_vars($oldtpl);
		foreach($vars as $var => $val)
			$this->$var = $val;
		
		$this->def_htmlcomments = $daddyobb->settings['tplhtmlcomments'];
		$this->def_htmlcomments = (($this->def_htmlcomments == 'yes' || $this->def_htmlcomments == 1) ? 1:0);
	}
	
	function get($title, $eslashes=1, $htmlcomments=1)
	{
		if(isset($this->parsed_cache[$title]) && $eslashes && $this->def_htmlcomments == $htmlcomments)
			return $this->parsed_cache[$title];
		
		$this->parsed_cache[$title] = $this->old_get($title, $eslashes, $htmlcomments);
		// parse the template
		$this->phptpl_parsetpl($this->parsed_cache[$title]);
			
		return $this->parsed_cache[$title];
	}
  
  function phptpl_parsetpl(&$ourtpl)
  {
    $ourtpl = preg_replace(array(
      '#\<\?.*?(\?\>)#se', // '#\<\?.*?(\?\>|$)#se',
      '#\<if (.*?) then\>#sie',
      '#\<elseif (.*?) then\>#sie',
      '#\<else( /)?\>#i',
      '#\</if\>#i',
      '#\<func (htmlspecialchars|htmlspecialchars_uni|intval|floatval|urlencode|rawurlencode|addslashes|stripslashes|trim|crc32|ltrim|rtrim|md5|nl2br|strrev|strtoupper|strtolower|in_array)\>#i',
      '#\</func\>#i'
    ), array(
      '$this->phptpl_evalphp(\'$0\', \'$1\')',
      '\'".$templates->phptpl_iif(\'.$this->phptpl_unescape_string(\'$1\').\',"\'',
      '\'",\'.$this->phptpl_unescape_string(\'$1\').\',"\'',
      '","',
      '")."',
      '".$1("',
      '")."'
    ), $ourtpl);
  }


  // unescapes the slashes added by $templates->get(), plus addslashes() during preg_replace()
  function phptpl_unescape_string($str)
  {
    return strtr($str, array('\\\\"' => '"', '\\\\' => '\\'));
  }

  function phptpl_evalphp($str, $end)
  {
    return '".eval(\'ob_start(); ?>'
      .strtr($this->phptpl_unescape_string($str), array('\'' => '\\\\', '\\' => '\\\\'))
      .($end?'':'?>').'<?php return ob_get_clean();\')."';
  }

  function phptpl_iif($condition, $true)
  {
    $args = func_get_args();
    for($i=1, $c=count($args); $i<$c; $i+=2)
      if($args[$i-1]) return $args[$i];
    return (isset($args[$i-1]) ? $args[$i-1] : '');
  }
}
?>