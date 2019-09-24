<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:25 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'visitormessage.php');

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/functions_visitormessage.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

$templatelist = "visitormessage,visitormessage_edit,visitormessage_report";

// Load global language phrases
$lang->load("posting");

// Make navigation
switch($daddyobb->input['action'])
{
	case "message":
	case "do_message":
		add_breadcrumb($lang->nav_postnewvm);
		break;
  case "edit":
  case "do_edit":
    add_breadcrumb($lang->nav_editvm);
    break;
  case "report":
  case "do_report":
    add_breadcrumb($lang->nav_report);
    break;
}

### No Permission Pages ###
if($daddyobb->user['uid'] == 0 || $daddyobb->usergroup['canusevms'] == 0)
{
	error_no_permission();
}
if($daddyobb->settings['enablevmsystem'] == 0)
{
  error($lang->vmsystem_disabled);
}
if($daddyobb->input['uid'])
{
  //Is the incoming member valid?
  if(user_exists($daddyobb->input['uid']) == false)
  {
    error($lang->error_nomember);
  }
}
### END: No Permission Pages ###

### Write New Message ###
if($daddyobb->input['action'] == "message")
{
  $plugins->run_hooks("vmessage_new_start");
  
  //Check if we can write a VM to the given user
  verify_vm_user($daddyobb->input['uid']);
  
  $lang->logged_in_as_x = $lang->sprintf($lang->logged_in_as_x, get_profile_link($daddyobb->user['uid']), $daddyobb->user['username']);
  
  //Are we able to use MyCode?
  if($daddyobb->settings['bbcodeinserter'] != 0 && $daddyobb->settings['vmallowmycode'] != 0 && $daddyobb->user['showcodebuttons'] != 0)
  {
    $codebuttons = build_mycode_inserter();
    if($daddyobb->settings['vmallowsmilies'] != 0)
    {
      $smilieinserter = build_clickable_smilies();
    }
  }
  
  //Check was successful? Give us the template
  $plugins->run_hooks("vmessage_new_end");
  eval("\$newvisitormessage = \"".$templates->get("visitormessage")."\";");
  output_page($newvisitormessage);  
}
### END: Write New Message ###

### Insert New Message ###
elseif($daddyobb->input['action'] == "do_message" && $daddyobb->request_method == "post")
{
  $plugins->run_hooks("vmessage_do_start");

  if(empty($daddyobb->input['vmessage']))
  {
    $error = inline_error($lang->error_vmessageempty);
  }
    
  verify_post_check($daddyobb->input['my_post_key']);
      
  $messagelength = my_strlen($daddyobb->input['vmessage']);
  if($messagelength > $daddyobb->settings['maxcharsvm'])
  {
    $too_long = $messagelength - $daddyobb->settings['maxcharsvm'];
    $lang->vmessage_too_long = $lang->sprintf($lang->vmessage_too_long, $too_long);
    $error = inline_error($lang->vmessage_too_long);
  }
    
  //Mark this message as unread if we are not vming on our own profile
  if($daddyobb->user['uid'] == $daddyobb->input['uid'])
  {
    $unread = 0;
  }
  else
  {
    $unread = 1;
  }
   
  //Do we moderate EVERY visitor message?
  if($daddyobb->settings['vmmoderation'] == 1 && $daddyobb->usergroup['issupermod'] != 1)
  {
    $status = 0;
  }
  else
  {
    $status = 1;
  }
    
  //Insert the message
  $vm_insert_array = array(
    "touid" => intval($daddyobb->input['uid']),
    "fromuid" => intval($daddyobb->user['uid']),
    "dateline" => TIME_NOW,
    "status" => intval($status),
    "unread" => intval($unread),
    "message" => $db->escape_string($daddyobb->input['vmessage']),
    "ipaddress" => $db->escape_string($session->ipaddress)
   );
  $db->insert_query("visitormessage", $vm_insert_array);
    
  redirect("member.php?action=profile&uid=".$daddyobb->input['uid']."", $lang->redirect_vmessage_posted);
    
  $plugins->run_hooks("vmessage_do_end");
}
### END: Insert New Message ###

### Edit Visitor Message ###
elseif($daddyobb->input['action'] == "edit")
{
  $plugins->run_hooks("vmessage_edit_start");
  
  //Check if we're allowed to edit this VM
  verify_edit_permissions($daddyobb->input['vmid']);
  
  //Get the message to parse
  $query = $db->simple_select("visitormessage", "*", "vmid='".intval($daddyobb->input['vmid'])."'");
  $vmessage = $db->fetch_array($query);
  
  $lang->logged_in_as_x = $lang->sprintf($lang->logged_in_as_x, get_profile_link($daddyobb->user['uid']), $daddyobb->user['username']);
  
  //Are we able to use MyCode?
  if($daddyobb->settings['bbcodeinserter'] != 0 && $daddyobb->settings['vmallowmycode'] != 0 && $daddyobb->user['showcodebuttons'] != 0)
  {
    $codebuttons = build_mycode_inserter();
    if($daddyobb->settings['vmallowsmilies'] != 0)
    {
      $smilieinserter = build_clickable_smilies();
    }
  }
  
  //Check was successful? Give us the template
  $plugins->run_hooks("vmessage_edit_end");
  eval("\$editvisitormessage = \"".$templates->get("visitormessage_edit")."\";");
  output_page($editvisitormessage);  
}
### END: Edit Visitor Message ###
### Update Visitor Message ###
elseif($daddyobb->input['action'] == "do_edit")
{
  $plugins->run_hooks("vmessage_do_edit_start");

  if(empty($daddyobb->input['vmessage']))
  {
    $error = inline_error($lang->error_vmessageempty);
  }

  verify_post_check($daddyobb->input['my_post_key']);

  
  //Check Message Lenght
  $messagelength = my_strlen($daddyobb->input['vmessage']);
  
  if($messagelength > $daddyobb->settings['maxcharsvm'])
  {
    $too_long = $messagelength - $daddyobb->settings['maxcharsvm'];
    $lang->vmessage_too_long = $lang->sprintf($lang->vmessage_too_long, $too_long);
    $error = inline_error($lang->vmessage_too_long);
  }
  
  //Update Visitor Message
  $update_array = array(
  "message" => $db->escape_string($daddyobb->input['vmessage']),
  );
  $db->update_query("visitormessage", $update_array, "vmid='".intval($daddyobb->input['vmid'])."'");
  
  redirect("member.php?action=profile&uid=".$daddyobb->input['uid']."", $lang->redirect_vmessage_edited);
  
  $plugins->run_hooks("vmessage_do_edit_end");
}
### END: Update Visitor Message ###
### Report Visitor Message ###
elseif($daddyobb->input['action'] == "report")
{
  $plugins->run_hooks("vmessage_report_start");
  
  //Get the message to parse
  $query = $db->simple_select("visitormessage", "*", "vmid='".intval($daddyobb->input['vmid'])."'");
  $vmessage = $db->fetch_array($query);
  
  //Check stauts of this visitor message
  if($vmessage['status'] == 2)
  {
    error($lang->error_cannot_edit_reported_vm);
  }
  
  $lang->logged_in_as_x = $lang->sprintf($lang->logged_in_as_x, get_profile_link($daddyobb->user['uid']), $daddyobb->user['username']);
  
  //Check was successful? Give us the template
  $plugins->run_hooks("vmessage_report_end");
  eval("\$reportvisitormessage = \"".$templates->get("visitormessage_report")."\";");
  output_page($reportvisitormessage);  
}
### END: Edit Visitor Message ###
### Update Visitor Message ###
elseif($daddyobb->input['action'] == "do_report")
{
  // Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);
  
  $plugins->run_hooks("vmessage_do_report_start");

  //Update Visitor Message
  $update_array = array(
  "status" => 2,
  );
  $db->update_query("visitormessage", $update_array, "vmid='".intval($daddyobb->input['vmid'])."'");
  
  redirect("member.php?action=profile&uid=".$daddyobb->input['uid']."", $lang->redirect_vmessage_reported);
  
  $plugins->run_hooks("vmessage_do_report_end");
}
### END: Update Visitor Message ###

?>