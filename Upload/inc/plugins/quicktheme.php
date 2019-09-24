<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$plugins->add_hook("pre_output_page", "quicktheme_run");
$plugins->add_hook("index_start", "quicktheme_update");
$plugins->add_hook("global_start", "quicktheme_global");

function quicktheme_info()
{
	return array(
		"name"			=> "Quick Theme for DaddyoBB",
		"description"	=> "A plugin that allows users to quickly change their theme!",
		"website"		=> "http://mods.mybboard.net/view/quick-theme",
		"author"		=> "Tikitiki and Ported to DADDYOBB by Vintagedaddyo",
		"authorsite"	=> "http://thetikitiki.com",
		"version"		=> "1.0.0",
		"compatibility" => "10*",
		"guid" 			=> "a60f0c103e0e4d4468e10604a76fe570",
	);
}

function quicktheme_activate()
{
}

function quicktheme_deactivate()
{
}

function quicktheme_run($page)
{
	global $daddyobb, $db, $lang;
	
	$lang->load("userbase");
	
	$theme_select = build_theme_select("style", $daddyobb->user['style']);
		
	if(strpos($theme_select, '</select>') === false)
	{
		$theme_select .= '</select>';
	}
		
	$data = "<form method=\"post\" action=\"./index.php?action=quicktheme\">\n<span class=\"trow2\" style=\"float: right; border: 1px solid #000000; padding: 1px;\">\n";
	$data .= "<input type=\"hidden\" name=\"uid\" value=\"{$daddyobb->user['uid']}\" />";
	$data .= "<span class=\"smalltext\"><strong>&nbsp;Quick Theme:</strong> ".$theme_select;
	$data .= "</span>\n<input type=\"submit\" value=\"Change Theme\" />\n";
	$data .= "</span>\n</form>\n<br />";		
	
	preg_match('#'.preg_quote('<!-- start: footer -->').'#i', $page, $matches);
	if($matches[0])
	{
		$page = str_replace($matches[0], "<br />".$data."\n<!-- start: footer -->", $page);
	}
	
	return $page;
}

function quicktheme_update()
{
	global $daddyobb, $db, $lang;
	
	if($daddyobb->input['action'] == 'quicktheme')
	{
		if(isset($daddyobb->input['style']) && $daddyobb->request_method == "post")
		{
			$lang->load('userbase');
			
			if($daddyobb->user['uid'])
			{ 
				$db->update_query("users", array('style' => intval($daddyobb->input['style'])), "uid='{$daddyobb->user['uid']}'");
			}
			else
			{
				if(intval($daddyobb->input['style']) == 0)
				{
					my_unsetcookie('quicktheme');
				}
				else
				{
					my_setcookie('quicktheme', intval($daddyobb->input['style']));
				}
			}		
			
			redirect($_SERVER['HTTP_REFERER'], $lang->redirect_optionsupdated);
		}
	}
}

function quicktheme_global()
{
	global $daddyobb;
	
	if($daddyobb->user['uid'] == 0 && intval($daddyobb->cookies['quicktheme']) > 0)
	{
		$daddyobb->user['style'] = intval($daddyobb->cookies['quicktheme']);
	}
}

?>