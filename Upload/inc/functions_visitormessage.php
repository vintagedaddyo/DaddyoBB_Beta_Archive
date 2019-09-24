<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:10 19.12.2008
  */

function verify_vm_user($uid=0)
{
  global $daddyobb, $db, $lang;
  
  //Do we even have a UID?
  if(!$uid)
  {
    error($lang->error_nomember);
  }
  
  //Get user details
  $user = get_user($uid);
  $userpermissions = user_permissions($uid);

  //Is the input member able to have visitor messages?  
  if($userpermissions['canusevms'] == 0)
  {
    $lang->error_notallowedtohavevms = $lang->sprintf($lang->error_notallowedtohavevms, $user['username']);
    error($lang->error_notallowedtohavevms);
  }
  
  //Does this user disabled to become VMs?
  if($user['enablevms'] == 0)
  {
    $lang->error_vmsdisabled = $lang->sprintf($lang->error_vmsdisabled, $user['username']);
    error($lang->error_vmsdisabled);
  }
  
  //VMs aren't disabled but the user only wants VMs of moderators and contacts (But check mod rights doubled!)
  $user_buddies = explode(",", $user['buddylist']);
  if($user['limitvms'] == 1)
  {
    if((!in_array($daddyobb->user['uid'], $user_buddies) && $userpermissions['canmodcp'] == 0) || (!in_array($daddyobb->user['uid'], $user_buddies) && $daddyobb->usergroup['modecanmanagevms'] == 0))
    {
      $lang->error_vmslimited = $lang->sprintf($lang->error_vmslimited, $user['username']);
      error($lang->error_vmslimited);
    }
  }
}

function verify_vm_by_id($vmid=0)
{
  global $db, $daddyobb, $lang;
  
  $query = $db->simple_select("visitormessage", "COUNT(*) as vm", "vmid='".intval($vmid)."'", array('limit' => 1));
  if($db->fetch_field($query, 'vm') == 1)
  {
    return true;
  }
  else
  {
    error($lang->vm_is_not_valid);
  }
}

function verify_edit_permissions($vmid=0)
{
  global $daddyobb, $db;
  
  //Verify incoming VM
  verify_vm_by_id($vmid);
  
  //First of all lets query the VM
  $query = $db->simple_select("visitormessage", "*", "vmid='".intval($vmid)."'");
  $vm = $db->fetch_array($query);
  
  $uid = $daddyobb->user['uid'];
  
  //Okay now that we've queried that, lets check permissions
  if($daddyobb->usergroup['modcanmanagevms'] != 1) //If we are no mod, we are very limited in editing things...
  {
    if($vm['status'] == 2 || $vm['status'] == 0 || $vm['fromuid'] != $uid || ($vm['fromuid'] == $uid && $daddyobb->usergroup['caneditownvms'] != 1))
    {
      error_no_permission();
    }
  }
}

?>