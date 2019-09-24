<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2008 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:32 19.12.2008
  */
 
// Set to 1 if recieving a blank page (template failure).
define("MANUAL_WARNINGS", 0);
 
// Define Custom DaddyoBB error handler constants with a value not used by php's error handler.
define("DADDYOBB_SQL", 20);
define("DADDYOBB_TEMPLATE", 30);
define("DADDYOBB_GENERAL", 40);

if(!defined("E_STRICT"))
{
	// This constant has been defined since PHP 5.
	define("E_STRICT", 2048);
}

if(!defined("E_RECOVERABLE_ERROR"))
{
	// This constant has been defined since PHP 5.2.
	define("E_RECOVERABLE_ERROR", 4096);
}

class errorHandler {

	/**
	 * Array of all of the error types
	 *
	 * @var array
	 */
	var $error_types = array( 
		E_ERROR              => 'Error',
		E_WARNING            => 'Warning',
		E_PARSE              => 'Parsing Error',
		E_NOTICE             => 'Notice',
		E_CORE_ERROR         => 'Core Error',
		E_CORE_WARNING       => 'Core Warning',
		E_COMPILE_ERROR      => 'Compile Error',
		E_COMPILE_WARNING    => 'Compile Warning',
		E_USER_ERROR         => 'User Error',
		E_USER_WARNING       => 'User Warning',
		E_USER_NOTICE        => 'User Notice',
		E_STRICT             => 'Runtime Notice',
		E_RECOVERABLE_ERROR  => 'Catchable Fatal Error',
		DADDYOBB_SQL 			 => 'DaddyoBB SQL Error', 
		DADDYOBB_TEMPLATE		 => 'DaddyoBB Template Error',
		DADDYOBB_GENERAL		 => 'DaddyoBB Error',
	);
	
	/**
	 * Array of all of the error types to ignore
	 *
	 * @var array
	 */
	var $ignore_types = array(
		E_NOTICE,
		E_USER_NOTICE,
		E_STRICT
	);
	
	/**
	 * String of all the warnings collected
	 *
	 * @var string
	 */
	var $warnings = "";

	/**
	 * Is DaddyoBB in an errornous state? (Have we received an error?)
	 *
	 * @var boolean
	 */
	var $has_errors = false;
	
	/**
	 * Initializes the error handler
	 *
	 */
	function errorHandler()
	{
		// Lets set the error handler in here so we can just do $handler = new errorHandler() and be all set up.
		if(version_compare(PHP_VERSION, ">=", "5"))
		{
			set_error_handler(array(&$this, "error"), array_diff($this->error_types, $this->ignore_types));
		}
		else
		{
			set_error_handler(array(&$this, "error"));
		}
	}
 	
	/**
	 * Parses a error for processing.
	 *
	 * @param string The error type (i.e. E_ERROR, E_FATAL)
	 * @param string The error message
	 * @param string The error file
	 * @param integer The error line
	 * @return boolean True if parsing was a success, otherwise assume a error
	 */			
	function error($type, $message, $file=null, $line=0)
	{
		global $daddyobb;

		// Error reporting turned off (either globally or by @ before erroring statement)
		if(error_reporting() == 0)
		{
			return;
		}

		if(in_array($type, $this->ignore_types))
		{
			return;
		}

		$file = str_replace(DADDYOBB_ROOT, "", $file);

		$this->has_errors = true;
		
		// For some reason in the installer this setting is set to "<"
		$accepted_error_types = array('both', 'error', 'warning');
		if(!in_array($daddyobb->settings['errortypemedium'], $accepted_error_types))
		{
			$daddyobb->settings['errortypemedium'] = "both";
		}
		
		if(($daddyobb->settings['errortypemedium'] == "both" || !$daddyobb->settings['errortypemedium']) || my_strpos(my_strtolower($this->error_types[$type]), $daddyobb->settings['errortypemedium']) || defined("IN_INSTALL") || defined("IN_UPGRADE"))
		{
			if(defined("IN_TASK"))
			{
				global $task;
				
				require_once DADDYOBB_ROOT."inc/functions_task.php";
				
				if($file)
				{
					$filestr = " - Line: $line - File: $file";
				}
				
				add_task_log($task, "{$this->error_types[$type]} - [$type] ".var_export($message, true)."{$filestr}");
			}
			
			// Saving error to log file.
			if($daddyobb->settings['errorlogmedium'] == "log" || $daddyobb->settings['errorlogmedium'] == "both")
			{
				$this->log_error($type, $message, $file, $line);
			}

			// Are we emailing the Admin a copy?
			if($daddyobb->settings['errorlogmedium'] == "mail" || $daddyobb->settings['errorlogmedium'] == "both")
			{
				$this->email_error($type, $message, $file, $line);
			}
			
			if($type == DADDYOBB_SQL)
			{
				$this->output_error($type, $message, $file, $line);
			}
			else
			{
				if(my_strpos(my_strtolower($this->error_types[$type]), 'warning') === false)
				{
					$this->output_error($type, $message, $file, $line);
				}
				else
				{
					global $templates;
					$warning = "<strong>{$this->error_types[$type]}</strong> [$type] $message - Line: $line - File: $file PHP ".PHP_VERSION." (".PHP_OS.")<br />\n";
					if(is_object($templates) && method_exists($templates, "get") && !defined("IN_ADMINCP"))
					{
						$this->warnings .= $warning;
						$this->warnings .= $this->generate_backtrace();
					}
					else
					{
						echo "<div class=\"php_warning\">{$warning}".$this->generate_backtrace()."</div>";
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Returns all the warnings
	 *
	 * @return string The warnings
	 */
	function show_warnings()
	{
		global $lang, $templates;
		
		if(empty($this->warnings))
		{
			return false;
		}
		
		// Incase a template fails and we're recieving a blank page.
		if(MANUAL_WARNINGS)
		{
			echo $this->warnings."<br />";
		}

		if(!$lang->warnings)
		{
			$lang->warnings = "The following warnings occured:";
		}
	
		if(defined("IN_ADMINCP"))
		{
			$warning = makeacpphpwarning($this->warnings);
		}
		else
		{
			$template_exists = false;
			
			if(!is_object($templates) || !method_exists($templates, 'get'))
			{
				if(@file_exists(DADDYOBB_ROOT."inc/class_templates.php"))
				{
					@require_once DADDYOBB_ROOT."inc/class_templates.php";
					$templates = new templates;
					$template_exists = true;
				}
			}
			else
			{
				$template_exists = true;
			}
			
			if($template_exists == true)
			{
				eval("\$warning = \"".$templates->get("php_warnings")."\";");
			}
		}
	
		return $warning;
	}
	
	/**
	 * Triggers a user created error 
	 * Example: $error_handler->trigger("Some Warning", E_USER_ERROR);
	 *
	 * @param string Message
	 * @param string Type
	 */
	function trigger($message="", $type=E_USER_ERROR)
	{
		global $lang;

		if(!$message)
		{
			$message = $lang->unknown_user_trigger;
		}

		if($type == DADDYOBB_SQL || $type == DADDYOBB_TEMPLATE || $type == DADDYOBB_GENERAL)
		{
			$this->error($type, $message);
		}
		else
		{
			trigger_error($message, $type);		
		}
	}

	/**
	 * Logs the error in the specified error log file.
	 *
	 * @param string Warning type
	 * @param string Warning message
	 * @param string Warning file
	 * @param integer Warning line
	 */
	function log_error($type, $message, $file, $line)
	{
		global $daddyobb;

		if($type == DADDYOBB_SQL)
		{
			$message = "SQL Error: {$message['error_no']} - {$message['error']}\nQuery: {$message['query']}";
		}
		$error_data = "<error>\n";
		$error_data .= "\t<dateline>".TIME_NOW."</dateline>\n";
		$error_data .= "\t<script>".$file."</script>\n";
		$error_data .= "\t<line>".$line."</line>\n";
		$error_data .= "\t<type>".$type."</type>\n";
		$error_data .= "\t<friendly_type>".$this->error_types[$type]."</friendly_type>\n";
		$error_data .= "\t<message>".$message."</message>\n";
		$error_data .= "</error>\n\n";

		if(trim($daddyobb->settings['errorloglocation']) != "")
		{
			@error_log($error_data, 3, $daddyobb->settings['errorloglocation']);
		}
		else
		{
			@error_log($error_data, 0);
		}
	}

	/**
	 * Emails the error in the specified error log file.
	 *
	 * @param string Warning type
	 * @param string Warning message
	 * @param string Warning file
	 * @param integer Warning line
	 */
	function email_error($type, $message, $file, $line)
	{
		if(!$daddyobb->settings['adminemail'])
		{
			return false;
		}

		if($type == DADDYOBB_SQL) 
		{
			$message = "SQL Error: {$message['error_no']} - {$message['error']}\nQuery: {$message['query']}";
		}
		
		$message = "Your copy of DaddyoBB running on {$daddyobb->settings['bbname']} ({$daddyobb->settings['bburl']}) has experienced an error. Details of the error include:\n---\nType: $type\nFile: $file (Line no. $line)\nMessage\n$message";

		$error = @my_mail($daddyobb->settings['adminemail'], "DaddyoBB error on {$daddyobb->settings['bbname']}", $message, $daddyobb->settings['adminemail']);
		if($error)
		{
			$this->output_error(DADDYOBB_GENERAL, $error);
		}
	}

	function output_error($type, $message, $file, $line)
	{
		global $daddyobb, $parser;

		if(!$daddyobb->settings['bbname'])
		{
			$daddyobb->settings['bbname'] = "DaddyoBB";
		}

		if($type == DADDYOBB_SQL)
		{
			$title = "DaddyoBB SQL Error";
			$error_message = "<p>DaddyoBB has experienced an internal SQL error and cannot continue.</p>";
			$error_message .= "<dl>\n";
			$error_message .= "<dt>SQL Error:</dt>\n<dd>{$message['error_no']} - {$message['error']}</dd>\n";
			if($message['query'] != "")
			{
				$error_message .= "<dt>Query:</dt>\n<dd>{$message['query']}</dd>\n";
			}
			$error_message .= "</dl>\n";
		}
		else
		{
			$title = "DaddyoBB Internal Error";
			$error_message = "<p>DaddyoBB has experienced an internal error and cannot continue.</p>";
			$error_message .= "<dl>\n";
			$error_message .= "<dt>Error Type:</dt>\n<dd>{$this->error_types[$type]} ($type)</dd>\n";
			$error_message .= "<dt>Error Message:</dt>\n<dd>{$message}</dd>\n";
			if(!empty($file))
			{
				$error_message .= "<dt>Location:</dt><dd>File: {$file}<br />Line: {$line}</dd>\n";
				if(!@preg_match('#config\.php|settings\.php#', $file) && @file_exists($file))
				{
					$code_pre = @file($file);

					$code = "";

					if(isset($code_pre[$line-4]))
					{
						$code .= $line-3 . ". ".$code_pre[$line-4];
					}

					if(isset($code_pre[$line-3]))
					{
						$code .= $line-2 . ". ".$code_pre[$line-3];
					}

					if(isset($code_pre[$line-2]))
					{
						$code .= $line-1 . ". ".$code_pre[$line-2];
					}

					$code .= $line . ". ".$code_pre[$line-1]; // The actual line.

					if(isset($code_pre[$line]))
					{
						$code .= $line+1 . ". ".$code_pre[$line];
					}

					if(isset($code_pre[$line+1]))
					{
						$code .= $line+2 . ". ".$code_pre[$line+1];
					}

					if(isset($code_pre[$line+2]))
					{
						$code .= $line+3 . ". ".$code_pre[$line+2];
					}

					unset($code_pre);

					$parser_exists = false;

					if(!is_object($parser) || !method_exists($parser, 'mycode_parse_php'))
					{
						if(@file_exists(DADDYOBB_ROOT."inc/class_parser.php"))
						{
							@require_once DADDYOBB_ROOT."inc/class_parser.php";
							$parser = new postParser;
							$parser_exists = true;
						}
					}
					else
					{
						$parser_exists = true;
					}

					if($parser_exists)
					{
						$code = $parser->mycode_parse_php($code, true);
					}
					else
					{
						$code = @nl2br($code);
					}

					$error_message .= "<dt>Code:</dt><dd>{$code}</dd>\n";
				}
			}
			$backtrace = $this->generate_backtrace();
			if($backtrace && $type != DADDYOBB_GENERAL)
			{
				$error_message .= "<dt>Backtrace:</dt><dd>{$backtrace}</dd>\n";
			}
			$error_message .= "</dl>\n";

		}

		if(isset($lang->settings['charset']))
		{
			$charset = $lang->settings['charset'];
		}
		else
		{
			$charset = 'UTF-8';
		}

		if(!headers_sent())
		{
			@header("Content-type: text/html; charset={$charset}");
			$_SERVER['PHP_SELF'] = htmlspecialchars_uni($_SERVER['PHP_SELF']);

		echo <<<EOF
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{$daddyobb->settings['bbname']} - Internal Error</title>
	<style type="text/css">
		body { background: #efefef; color: #000; font-family: Verdana; font-size: 12px; text-align: center; line-height: 1.4; }
		a:link { color: #026CB1; text-decoration: none;	}
		a:visited {	color: #026CB1;	text-decoration: none; }
		a:hover, a:active {	color: #000; text-decoration: underline; }
		#container { width: 600px; padding: 20px; background: #fff;	border: 1px solid #e4e4e4; margin: 100px auto; text-align: left; }
		h1 { margin: 0; background: url({$_SERVER['PHP_SELF']}?action=DADDYOBB_logo) no-repeat;	height: 82px; width: 248px; }
		#content { border: 1px solid #B60101; background: #fff; }
		h2 { font-size: 12px; padding: 4px; background: #B60101; color: #fff; margin: 0; }
		.invisible { display: none; }
		#error { padding: 6px; }
		#footer { font-size: 11px; border-top: 1px solid #ccc; padding-top: 10px; }
		dt { font-weight: bold; }
	</style>
</head>
<body>
	<div id="container">
		<div id="logo">
			<h1><a href="http://daddyobb.com/" title="DaddyoBB"><span class="invisible">DaddyoBB</span></a></h1>
		</div>

		<div id="content">
			<h2>{$title}</h2>

			<div id="error">
				{$error_message}
				<p id="footer">Please contact the <a href="http://www.daddyobb.com">DaddyoBB Group</a> for support.</p>
			</div>
		</div>
	</div>
</body>
</html>
EOF;
		}
		else
		{
			echo <<<EOF
	<style type="text/css">
		#DADDYOBB_error_content { border: 1px solid #B60101; background: #fff; }
		#DADDYOBB_error_content h2 { font-size: 12px; padding: 4px; background: #B60101; color: #fff; margin: 0; }
		#DADDYOBB_error_error { padding: 6px; }
		#DADDYOBB_error_footer { font-size: 11px; border-top: 1px solid #ccc; padding-top: 10px; }
		#DADDYOBB_error_content dt { font-weight: bold; }
	</style>
	<div id="DADDYOBB_error_content">
		<h2>{$title}</h2>
		<div id="DADDYOBB_error_error">
		{$error_message}
			<p id="DADDYOBB_error_footer">Please contact the <a href="http://www.daddyobb.com">DaddyoBB Group</a> for support.</p>
		</div>
	</div>
EOF;
		}
		exit(1);
	}

	/**
	 * Generates a backtrace if the server supports it.
	 *
	 * @return string The generated backtrace
	 */
	function generate_backtrace()
	{
		if(function_exists("debug_backtrace"))
		{
			$trace = debug_backtrace();
			$backtrace = "<table style=\"width: 100%; margin: 10px 0; border: 1px solid #aaa; border-collapse: collapse; border-bottom: 0;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
			$backtrace .= "<thead><tr>\n";
			$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">File</th>\n";
			$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Line</th>\n";
			$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Function</th>\n";
			$backtrace .= "</tr></thead>\n<tbody>\n";

			// Strip off this function from trace
			array_shift($trace);

			foreach($trace as $call)
			{
				if(!$call['file']) $call['file'] = "[PHP]";
				if(!$call['line']) $call['line'] = "&nbsp;";
				if($call['class']) $call['function'] = $call['class'].$call['type'].$call['function'];
				$call['file'] = str_replace(DADDYOBB_ROOT, "/", $call['file']);
				$backtrace .= "<tr>\n";
				$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['file']}</td>\n";
				$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['line']}</td>\n";
				$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['function']}</td>\n";
				$backtrace .= "</tr>\n";
			}
			$backtrace .= "</tbody></table>\n";
		}
		return $backtrace;
	}
}
?>