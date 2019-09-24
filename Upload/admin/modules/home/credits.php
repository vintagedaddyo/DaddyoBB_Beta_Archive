<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->DADDYOBB_MYBB_credits, "index.php?module=home/credits");

$plugins->run_hooks("admin_home_credits_begin");

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_home_credits_start");
	
	$page->output_header($lang->DADDYOBB_credits);
	
	$sub_tabs['credits'] = array(
		'title' => $lang->DADDYOBB_MYBB_credits,
		'link' => "index.php?module=home/credits",
		'description' => $lang->DADDYOBB_credits_description
	);
	
		$sub_tabs['credits_about_daddyobb'] = array(
		'title' => $lang->about_the_daddyobb_team,
		'link' => "http://daddyobb.com/index.php?p=team",
		'link_target' => "_blank",
	);
	
	$sub_tabs['credits_about_lb'] = array(
		'title' => $lang->about_the_lb_team,
		'link' => "http://litesoft.info/index.php?s=team",
		'link_target' => "_blank",
	);
	
	$sub_tabs['credits_about_mybb'] = array(
		'title' => $lang->about_the_mybb_team,
		'link' => "http://mybboard.net/about/team",
		'link_target' => "_blank",
	);

	$page->output_nav_tabs($sub_tabs, 'credits');
	
	$table = new Table;
	$table->construct_header($lang->product_managers, array('width' => '33%'));
	$table->construct_header($lang->developers, array('width' => '33%'));
	$table->construct_header($lang->graphics_and_style, array('width' => '33%'));
	
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=1\" target=\"_blank\">vintagedaddyo</a>");
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=1\" target=\"_blank\">vintagedaddyo</a>");	
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=1\" target=\"_blank\">vintagedaddyo</a>");
	$table->construct_row();
	
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=3\" target=\"_blank\">p@trick</a>");
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=3\" target=\"_blank\">p@trick</a>");	
	$table->construct_cell("&nbsp;");
	$table->construct_row();
	
		$table->construct_cell("&nbsp;");
	$table->construct_cell("<a href=\"http://community.daddyobb.com/member.php?action=profile&uid=2\" target=\"_blank\">Machoo</a>");	
	$table->construct_cell("&nbsp;");
	$table->construct_row();
	
	
	$table->output($lang->DADDYOBB_credits);
	
	$table = new Table;
	$table->construct_header($lang->product_managers, array('width' => '20%'));
	$table->construct_header($lang->developers, array('width' => '20%'));
	$table->construct_header($lang->graphics_and_style, array('width' => '20%'));
	$table->construct_header($lang->translators, array('width' => '20%'));
	$table->construct_header($lang->plugins, array('width' => '20%'));
	
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=1\" target=\"_blank\">Manuel Feller</a>");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=1\" target=\"_blank\">Manuel Feller</a>");	
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=1\" target=\"_blank\">Manuel Feller</a>");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=1\" target=\"_blank\">Manuel Feller</a> <small>[German]</small>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-7473.html\" target=\"_blank\">Yumi</a> <small>[Conditions in Templates]</small>");
	$table->construct_row();
	
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=2\" target=\"_blank\">Pascal Hölscher</a>");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=2\" target=\"_blank\">Pascal Hölscher</a>");
	$table->construct_cell("&nbsp;");
  $table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=2\" target=\"_blank\">Pascal Hölscher</a> <small>[Dutch]</small>");
	$table->construct_cell("<a href=\"http://www.mybbcentral.com/user-208.html\" target=\"_blank\">LeX-</a> <small>[Profile Pictures]</small>");
	$table->construct_row();
  $table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=73\" target=\"_blank\">Michele</a> <small>[Italian]</small>");
	$table->construct_cell("<a href=\"http://mods.mybboard.net/profile/13144\" target=\"_blank\">RenegadeFan</a> <small>[Editreason]</small>");
	$table->construct_row();		
	$table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=80\" target=\"_blank\">Uğur Kılıç</a> <small>[Turkish]</small>");
	$table->construct_cell("&nbsp;");
	$table->construct_row();
	$table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("&nbsp;");
	$table->construct_cell("<a href=\"http://community.litesoft.info/member.php?action=profile&uid=74\" target=\"_blank\">Lokki</a> <small>[Romanian]</small>");
	$table->construct_cell("&nbsp;");
	$table->construct_row();		
	
	$table->output($lang->LITEBULLETIN_credits);
	
	$table = new Table;
	$table->construct_header($lang->product_managers, array('width' => '33%'));
	$table->construct_header($lang->developers, array('width' => '33%'));
	$table->construct_header($lang->graphics_and_style, array('width' => '33%'));
	
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-1.html\" target=\"_blank\">Chris Boulton</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-1.html\" target=\"_blank\">Chris Boulton</a>");	
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-1.html\" target=\"_blank\">Chris Boulton</a>");
	$table->construct_row();
	
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-14.html\" target=\"_blank\">Musicalmidget</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-81.html\" target=\"_blank\">DennisTT</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-5.html\" target=\"_blank\">Scott Hough</a>");
	$table->construct_row();	
	
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-27.html\" target=\"_blank\">Tochjo</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-2165.html\" target=\"_blank\">Tikitiki</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-1830.html\" target=\"_blank\">Justin S.</a>");
	$table->construct_row();
	
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-81.html\" target=\"_blank\">DennisTT</a>");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-1653.html\" target=\"_blank\">DrPoodle</a>");
	$table->construct_cell("&nbsp;");
	$table->construct_row();
	
	$table->construct_cell("&nbsp;");
	$table->construct_cell("<a href=\"http://community.mybboard.net/user-7473.html\" target=\"_blank\">ZiNgA BuRgA</a>");
	$table->construct_cell("&nbsp;");
	$table->construct_row();
	
	$table->output($lang->MYBB_credits);
	
	$page->output_footer();
}

?>