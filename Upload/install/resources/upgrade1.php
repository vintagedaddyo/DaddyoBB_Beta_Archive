<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:13 20.12.2008
 */
 
/**
  * Upgrade Script: DaddyoBB 1.4.4
  */
  
$upgrade_detail = array(
  "revert_all_templates" => 1,
  "revert_all_themes" => 1,
  "revert_all_settings" => 1,
  "requires_deactivated_plugins" => 1,
);

@set_time_limit(0);

function upgrade1_dbchanges()
{
  global $db, $output, $daddyobb;
  
  $output->print_header("Altering Exisiting Tables");
  
  echo "<p>DaddyoBB containts a few new table structure which has to be added to the existing tables.</p>";
  flush();
  
  // Add the new structure
  $altering_array = array(
  "announcements" => array(
    "views" => "int(100) NOT NULL default '0' AFTER message"
    ),
  "sessions" => array(
    "error" => "int(1) NOT NULL default '0' AFTER nopermission;"
    ),
  "usergroups" => array(
    "sigmaxchars" => "varchar(10) NOT NULL default '1000' AFTER caneditattachments;", 
    "signumimages" => "varchar(10) NOT NULL default '4' AFTER sigmaxchars;", 
    "sigallowmycode" => "int(1) NOT NULL default '1' AFTER signumimages;", 
    "sigallowimgcode" => "int(1) NOT NULL default '1' AFTER sigallowmycode;", 
    "sigallowsmilies" => "int(1) NOT NULL default '1' AFTER sigallowimgcode;",
    "sigallowhtml" => "int(1) NOT NULL default '0' AFTER sigallowsmilies;", 
    "caneditownvms" => "int(1) NOT NULL default '1' AFTER canusevms;", 
    "candeleteownvms" => "int(1) NOT NULL default '1' AFTER caneditownvms;", 
    "canmanagevms" => "int(1) NOT NULL default '1' AFTER candeleteownvms;", 
    "canhtmlintitle" => "int(1) NOT NULL default '0' AFTER cancustomtitle;", 
    "modcanannouncements" => "int(1) NOT NULL default '0' AFTER canmodcp;", 
    "modcanviewmodlogs" => "int(1) NOT NULL default '0' AFTER modcanannouncements;",
    "modcanmanagevms" => "int(1) NOT NULL default '0' AFTER modcanviewmodlogs;", 
    "modcanmanageprofiles" => "int(1) NOT NULL default '0' AFTER modcanmanagevms;", 
    "modcanbanusers" => "int(1) NOT NULL default '0' AFTER modcanmanageprofiles;",
    "avatarmaxsize" => "varchar(50) NOT NULL default '10' AFTER caneditattachments;", 
    "avatarmaxdimensions" => "varchar(50) NOT NULL default '100x100' AFTER avatarmaxsize;", 
    "profilepicmaxsize" => "varchar(50) NOT NULL default '15' AFTER avatarmaxdimensions;", 
    "profilepicmaxdimensions" => "varchar(50) NOT NULL default '130x130' AFTER profilepicmaxsize;",
    "canuploadprofilepics" => "int(1) NOT NULL default '1' AFTER canuploadavatars;"
    ),
  "users" => array(
    "profilepic" => "varchar(200) NOT NULL default '' AFTER postnum;",
    "profilepicdimensions" => "varchar(10) NOT NULL default '' AFTER profilepic;",
    "vcard" => "int(1) NOT NULL default '0' AFTER birthdayprivacy;",
    "enablevms" => "int(1) NOT NULL default '1' AFTER unreadpms;",
    "limitvms" => "int(1) NOT NULL default '0' AFTER enablevms;"
    )
  );
  
  foreach($altering_array as $row => $data)
  {
    echo "<span class=\"spoiler\"><a href=\"#\" onclick=\"spoiler(this.parentNode);\" style=\"float: right;\" />Show Details</a>Altering Table: ".TABLE_PREFIX."$row...";
    echo "<div><p>";
    foreach($data as $structure => $type)
    {
      if($db->field_exists($structure, $row) == false)
      {
        $db->write_query("ALTER TABLE ".TABLE_PREFIX."{$row} ADD {$structure} {$type}");
      }
      echo "<dl style=\"margin-left: -30px; font-size: 9px;\"><dd><span style=\"float: right; color: #6af384;\"><strong>Completed!</strong></span>ADD {$structure} {$type}</dd></dl>";
    }
    echo "</p></div>";
    echo "</span>";
    echo "<br />";
  }
    
  echo "<p><strong>All tables has been restructered successfully.</strong></p>";
  flush();
  echo "<p>Click next to continue with the upgrade process.</p>";

  $output->print_footer("1_dbchanges0");
}
function upgrade1_dbchanges0()
{
  global $db, $output;
  
  $output->print_header("Table Inserts");
  
  //Insert new template group (visitormessage)
  $select = $db->simple_select("templategroups", "*", "prefix='visitormessage'");
  if($db->num_rows($select) <= 0)
  {
    $insert_array = array(
      "prefix" => "visitormessage",
      "title" => "<lang:group_visitormessage>"
    );
    $db->insert_query("templategroups", $insert_array);
  }
  
  echo "<span class=\"spoiler\"><a href=\"#\" onclick=\"spoiler(this.parentNode);\" style=\"float: right;\" />Show Details</a>Inserting Data: ".TABLE_PREFIX."templategroups...";
  echo "<div><p>";
  echo "<dl style=\"margin-left: -30px; font-size: 9px;\"><dd><span style=\"float: right; color: #6af384;\"><strong>Completed!</strong></span>INSERT INTO ".TABLE_PREFIX."templategroups (`prefix`, `title`) VALUES (\"visitormessage\", \"<lang:group_visitormessage>\")</dd></dl>";
  echo "</p></div>";
  echo "</span>";
  echo "<br />";
  
  echo "<p><strong>Template Group inserted successfully.</strong></p>";
  flush();
  echo "<p>Click next to continue with the upgrade process.</p>";

  $output->print_footer("1_dbchanges1");
  
}
function upgrade1_dbchanges1()
{
  global $db, $output, $daddyobb;

  $output->print_header("Removing Table Structure");
  
  echo "<p>That is just a little process. It deletes the unnecessary things of the user table.</p>";
  flush();
   
  //Get rid of old structure...
  $drop_array = array(
  "users" => array("away", "awaydate", "returndate", "awayreason", "timeonline")
  );
  
  foreach($drop_array as $table => $column)
  {
    echo "<span class=\"spoiler\"><a href=\"#\" onclick=\"spoiler(this.parentNode);\" style=\"float: right;\" />Show Details</a>Restructuring Table: ".TABLE_PREFIX."$table...";
    echo "<div><p>";
    foreach($column as $drop)
    {
      if($db->field_exists($drop, $table))
      {
        $db->write_query("ALTER TABLE ".TABLE_PREFIX."{$table} DROP COLUMN {$drop}");
      }
      echo "<dl style=\"margin-left: -30px; font-size: 9px;\"><dd><span style=\"float: right; color: #6af384;\"><strong>Completed!</strong></span>DROP COLUMN {$drop}</dd></dl>";
    }
    echo "</p></div>";
    echo "</span>";
    echo "<br />";
  }
  
  echo "<p><strong>All tables has been restructered successfully.</strong></p>";
  flush();
  echo "<p>Click next to continue with the upgrade process.</p>";

  $output->print_footer("1_dbchanges2");
}
function upgrade1_dbchanges2()
{
  global $db, $output, $daddyobb;

  $output->print_header("Creating Visitor Message Table");
  
  echo "<p>DaddyoBB does only conatin one new table. This will be added now.</p>";
  flush();
  
  //Create new table
  $vm_table = "CREATE TABLE ".TABLE_PREFIX."visitormessage (
    vmid int unsigned NOT NULL auto_increment,
    touid int unsigned NOT NULL default '0',
    fromuid int unsigned NOT NULL default '0',
    dateline bigint(30) NOT NULL default '0',
    status int(1) NOT NULL default '1',
    unread int(1) NOT NULL default '0',
    message mediumtext NOT NULL,
    ipaddress varchar(50) NOT NULL default '0',
    PRIMARY KEY (vmid)
  ) TYPE=MyISAM;";

  echo "<span class=\"spoiler\"><a href=\"#\" onclick=\"spoiler(this.parentNode);\" style=\"float: right;\" />Show Details</a>Creating Table: ".TABLE_PREFIX."visitormessage...";
  echo "<div><p>";
  if(!$db->table_exists("visitormessage"))
  {
    $db->write_query($vm_table);
  }
  
  echo "<dl style=\"margin-left: -30px; font-size: 12px;\"><dd><span style=\"float: right; color: #6af384;\"><strong>Completed!</strong></span><pre>CREATE TABLE daddyobb_visitormessage (
    vmid int unsigned NOT NULL auto_increment,
    touid int unsigned NOT NULL default '0',
    fromuid int unsigned NOT NULL default '0',
    dateline bigint(30) NOT NULL default '0',
    status int(1) NOT NULL default '1',
    unread int(1) NOT NULL default '0',
    message mediumtext NOT NULL,
    ipaddress varchar(50) NOT NULL default '0',
    PRIMARY KEY (vmid)
  ) TYPE=MyISAM;</pre></dd></dl>";

 echo "</p></div>";
 echo "</span>";
 echo "<br />";
  
  echo "<p><strong>Done.</strong></p>";
  flush();
  echo "<p>Click next to continue with the upgrade process.</p>";

  $output->print_footer("1_done");
}