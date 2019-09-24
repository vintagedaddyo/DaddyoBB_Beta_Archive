<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 22:23 20.12.200822:24 20.12.2008
 */

$tables[] = "CREATE TABLE daddyobb_adminlog (
  uid int unsigned NOT NULL default '0',
  ipaddress varchar(50) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  module varchar(50) NOT NULL default '',
  action varchar(50) NOT NULL default '',
  data text NOT NULL,
  KEY module (module, action)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_adminoptions (
  uid int(10) NOT NULL default '0',
  cpstyle varchar(50) NOT NULL default '',
  codepress int(1) NOT NULL default '1',
  notes text NOT NULL,
  permissions text NOT NULL,
  defaultviews text NOT NULL,
  PRIMARY KEY  (uid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_adminsessions (
  sid varchar(32) NOT NULL default '',
  uid int unsigned NOT NULL default '0',
  loginkey varchar(50) NOT NULL default '',
  ip varchar(40) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  lastactive bigint(30) NOT NULL default '0',
  data TEXT NOT NULL
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_adminviews (
	vid int unsigned NOT NULL auto_increment,
	uid int unsigned NOT NULL default '0',
	title varchar(100) NOT NULL default '',
	type varchar(6) NOT NULL default '',
	visibility int(1) NOT NULL default '0',
	`fields` text NOT NULL,
	conditions text NOT NULL,
	sortby varchar(20) NOT NULL default '',
	sortorder varchar(4) NOT NULL default '',
	perpage int(4) NOT NULL default '0',
	view_type varchar(6) NOT NULL default '',
	PRIMARY KEY(vid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_announcements (
  aid int unsigned NOT NULL auto_increment,
  fid int(10) NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  subject varchar(120) NOT NULL default '',
  message text NOT NULL,
  views int(100) NOT NULL default '0',
  startdate bigint(30) NOT NULL default '0',
  enddate bigint(30) NOT NULL default '0',
  allowhtml int(1) NOT NULL default '0',
  allowmycode int(1) NOT NULL default '0',
  allowsmilies int(1) NOT NULL default '0',
  KEY fid (fid),
  PRIMARY KEY  (aid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_attachments (
  aid int unsigned NOT NULL auto_increment,
  pid int(10) NOT NULL default '0',
  posthash varchar(50) NOT NULL default '',
  uid int unsigned NOT NULL default '0',
  filename varchar(120) NOT NULL default '',
  filetype varchar(120) NOT NULL default '',
  filesize int(10) NOT NULL default '0',
  attachname varchar(120) NOT NULL default '',
  downloads int unsigned NOT NULL default '0',
  dateuploaded bigint(30) NOT NULL default '0',
  visible int(1) NOT NULL default '0',
  thumbnail varchar(120) NOT NULL default '',
  KEY posthash (posthash),
  KEY pid (pid, visible),
  KEY uid (uid),
  PRIMARY KEY  (aid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_attachtypes (
  atid int unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  mimetype varchar(120) NOT NULL default '',
  extension varchar(10) NOT NULL default '',
  maxsize int(15) NOT NULL default '0',
  icon varchar(100) NOT NULL default '',
  PRIMARY KEY  (atid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_awaitingactivation (
  aid int unsigned NOT NULL auto_increment,
  uid int unsigned NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  code varchar(100) NOT NULL default '',
  type char(1) NOT NULL default '',
  oldgroup bigint(30) NOT NULL default '0',
  misc varchar(255) NOT NULL default '',
  PRIMARY KEY  (aid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_badwords (
  bid int unsigned NOT NULL auto_increment,
  badword varchar(100) NOT NULL default '',
  replacement varchar(100) NOT NULL default '',
  PRIMARY KEY  (bid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_banfilters (
  fid int unsigned NOT NULL auto_increment,
  filter varchar(200) NOT NULL default '',
  type int(1) NOT NULL default '0',
  lastuse bigint(30) NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  PRIMARY KEY  (fid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_banned (
  uid int unsigned NOT NULL default '0',
  gid int unsigned NOT NULL default '0',
  oldgroup int unsigned NOT NULL default '0',
  oldadditionalgroups text NOT NULL,
  olddisplaygroup int NOT NULL default '0',
  admin int unsigned NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  bantime varchar(50) NOT NULL default '',
  lifted bigint(30) NOT NULL default '0',
  reason varchar(255) NOT NULL default '',
  KEY uid (uid),
  KEY dateline (dateline)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_calendars (
  cid int unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  disporder int unsigned NOT NULL default '0',
  startofweek int(1) NOT NULL default '0',
  showbirthdays int(1) NOT NULL default '0',
  eventlimit int(3) NOT NULL default '0',
  moderation int(1) NOT NULL default '0',
  allowhtml int(1) NOT NULL default '0',
  allowmycode int(1) NOT NULL default '0',
  allowimgcode int(1) NOT NULL default '0',
  allowsmilies int(1) NOT NULL default '0',
  PRIMARY KEY(cid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_calendarpermissions (
  cid int unsigned NOT NULL default '0',
  gid int unsigned NOT NULL default '0',
  canviewcalendar int(1) NOT NULL default '0',
  canaddevents int(1) NOT NULL default '0',
  canbypasseventmod int(1) NOT NULL default '0',
  canmoderateevents int(1) NOT NULL default '0'
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_captcha (
  imagehash varchar(32) NOT NULL default '',
  imagestring varchar(8) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  KEY imagehash (imagehash),
  KEY dateline (dateline)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_datacache (
  title varchar(50) NOT NULL default '',
  cache mediumtext NOT NULL,
  PRIMARY KEY(title)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_events (
  eid int unsigned NOT NULL auto_increment,
  cid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  name varchar(120) NOT NULL default '',
  description text NOT NULL,
  visible int(1) NOT NULL default '0',
  private int(1) NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  starttime int(10) unsigned NOT NULL default '0',
  endtime int(10) unsigned NOT NULL default '0',
  timezone int(3) NOT NULL default '0',
  ignoretimezone int(1) NOT NULL default '0',
  usingtime int(1) NOT NULL default '0',
  repeats text NOT NULL,
  KEY daterange (starttime, endtime),
  KEY private (private),
  PRIMARY KEY  (eid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_forumpermissions (
  pid int unsigned NOT NULL auto_increment,
  fid int unsigned NOT NULL default '0',
  gid int unsigned NOT NULL default '0',
  canview int(1) NOT NULL default '0',
  canviewthreads int(1) NOT NULL default '0',
  candlattachments int(1) NOT NULL default '0',
  canpostthreads int(1) NOT NULL default '0',
  canpostreplys int(1) NOT NULL default '0',
  canpostattachments int(1) NOT NULL default '0',
  canratethreads int(1) NOT NULL default '0',
  caneditposts int(1) NOT NULL default '0',
  candeleteposts int(1) NOT NULL default '0',
  candeletethreads int(1) NOT NULL default '0',
  caneditattachments int(1) NOT NULL default '0',
  canpostpolls int(1) NOT NULL default '0',
  canvotepolls int(1) NOT NULL default '0',
  cansearch int(1) NOT NULL default '0',
  PRIMARY KEY  (pid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_forums (
  fid smallint unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  description text NOT NULL,
  linkto varchar(180) NOT NULL default '',
  type char(1) NOT NULL default '',
  pid smallint unsigned NOT NULL default '0',
  parentlist text NOT NULL,
  disporder smallint unsigned NOT NULL default '0',
  active int(1) NOT NULL default '0',
  open int(1) NOT NULL default '0',
  threads int unsigned NOT NULL default '0',
  posts int unsigned NOT NULL default '0',
  lastpost int(10) unsigned NOT NULL default '0',
  lastposter varchar(120) NOT NULL default '',
  lastposteruid int(10) unsigned NOT NULL default '0',
  lastposttid int(10) NOT NULL default '0',
  lastpostsubject varchar(120) NOT NULL default '',
  allowhtml int(1) NOT NULL default '0',
  allowmycode int(1) NOT NULL default '0',
  allowsmilies int(1) NOT NULL default '0',
  allowimgcode int(1) NOT NULL default '0',
  allowpicons int(1) NOT NULL default '0',
  allowtratings int(1) NOT NULL default '0',
  status int(4) NOT NULL default '1',
  usepostcounts int(1) NOT NULL default '0',
  password varchar(50) NOT NULL default '',
  showinjump int(1) NOT NULL default '0',
  modposts int(1) NOT NULL default '0',
  modthreads int(1) NOT NULL default '0',
  mod_edit_posts int(1) NOT NULL default '0',
  modattachments int(1) NOT NULL default '0',
  style smallint unsigned NOT NULL default '0',
  overridestyle int(1) NOT NULL default '0',
  rulestype smallint(1) NOT NULL default '0',
  rulestitle varchar(200) NOT NULL default '',
  rules text NOT NULL,
  unapprovedthreads int(10) unsigned NOT NULL default '0',
  unapprovedposts int(10) unsigned NOT NULL default '0',
  defaultdatecut smallint(4) unsigned NOT NULL default '0',
  defaultsortby varchar(10) NOT NULL default '',
  defaultsortorder varchar(4) NOT NULL default '',
  PRIMARY KEY (fid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_forumsread (
  fid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  dateline int(10) NOT NULL default '0',
  KEY dateline (dateline),
  UNIQUE KEY fid (fid,uid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_forumsubscriptions (
  fsid int unsigned NOT NULL auto_increment,
  fid smallint unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  PRIMARY KEY  (fsid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_groupleaders (
  lid smallint unsigned NOT NULL auto_increment,
  gid smallint unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  canmanagemembers int(1) NOT NULL default '0',
  canmanagerequests int(1) NOT NULL default '0',
  PRIMARY KEY  (lid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_helpdocs (
  hid smallint unsigned NOT NULL auto_increment,
  sid smallint unsigned NOT NULL default '0',
  name varchar(120) NOT NULL default '',
  description text NOT NULL,
  document text NOT NULL,
  usetranslation int(1) NOT NULL default '0',
  enabled int(1) NOT NULL default '0',
  disporder smallint unsigned NOT NULL default '0',
  PRIMARY KEY  (hid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_helpsections (
  sid smallint unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  description text NOT NULL,
  usetranslation int(1) NOT NULL default '0',
  enabled int(1) NOT NULL default '0',
  disporder smallint unsigned NOT NULL default '0',
  PRIMARY KEY (sid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_icons (
  iid smallint unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  path varchar(220) NOT NULL default '',
  PRIMARY KEY (iid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_joinrequests (
  rid int unsigned NOT NULL auto_increment,
  uid int unsigned NOT NULL default '0',
  gid smallint unsigned NOT NULL default '0',
  reason varchar(250) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  PRIMARY KEY (rid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_massemails (
	mid int unsigned NOT NULL auto_increment,
	uid int unsigned NOT NULL default '0',
	subject varchar(200) NOT NULL default '',
	message text NOT NULL,
	htmlmessage text NOT NULL,
	type tinyint(1) NOT NULL default '0',
	format tinyint(1) NOT NULL default '0',
	dateline bigint(30) NOT NULL default '0',
	senddate bigint(30) NOT NULL default '0',
	status tinyint(1) NOT NULL default '0',
	sentcount int unsigned NOT NULL default '0',
	totalcount int unsigned NOT NULL default '0',
	conditions text NOT NULL,
	perpage smallint(4) NOT NULL default '50',
	PRIMARY KEY(mid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_mailerrors (
  eid int unsigned NOT NULL auto_increment,
  subject varchar(200) NOT NULL default '',
  message text NOT NULL,
  toaddress varchar(150) NOT NULL default '',
  fromaddress varchar(150) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  error text NOT NULL,
  smtperror varchar(200) NOT NULL default '',
  smtpcode int(5) NOT NULL default '0',
  PRIMARY KEY (eid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_maillogs (
	mid int unsigned NOT NULL auto_increment,
	subject varchar(200) not null default '',
	message text NOT NULL,
	dateline bigint(30) NOT NULL default '0',
	fromuid int unsigned NOT NULL default '0',
	fromemail varchar(200) not null default '',
	touid bigint(30) NOT NULL default '0',
	toemail varchar(200) NOT NULL default '',
	tid int unsigned NOT NULL default '0',
	ipaddress varchar(20) NOT NULL default '',
	PRIMARY KEY (mid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_mailqueue (
	mid int unsigned NOT NULL auto_increment,
	mailto varchar(200) NOT NULL,
	mailfrom varchar(200) NOT NULL,
	subject varchar(200) NOT NULL,
	message text NOT NULL,
	headers text NOT NULL,
	PRIMARY KEY (mid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_moderatorlog (
  uid int unsigned NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  fid smallint unsigned NOT NULL default '0',
  tid int unsigned NOT NULL default '0',
  pid int unsigned NOT NULL default '0',
  action text NOT NULL,
  data text NOT NULL,
  ipaddress varchar(50) NOT NULL default '',
  KEY tid (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_moderators (
  mid smallint unsigned NOT NULL auto_increment,
  fid smallint unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  caneditposts int(1) NOT NULL default '0',
  candeleteposts int(1) NOT NULL default '0',
  canviewips int(1) NOT NULL default '0',
  canopenclosethreads int(1) NOT NULL default '0',
  canmanagethreads int(1) NOT NULL default '0',
  canmovetononmodforum int(1) NOT NULL default '0',
  KEY uid (uid, fid),
  PRIMARY KEY (mid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_modtools (
	tid smallint unsigned NOT NULL auto_increment,
	name varchar(200) NOT NULL,
	description text NOT NULL,
	forums text NOT NULL,
	type char(1) NOT NULL default '',
	postoptions text NOT NULL,
	threadoptions text NOT NULL,
	PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_mycode (
  cid int unsigned NOT NULL auto_increment,
  title varchar(100) NOT NULL default '',
  description text NOT NULL,
  regex text NOT NULL,
  replacement text NOT NULL,
  active int(1) NOT NULL default '0',
  parseorder smallint unsigned NOT NULL default '0',
  PRIMARY KEY(cid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_polls (
  pid int unsigned NOT NULL auto_increment,
  tid int unsigned NOT NULL default '0',
  question varchar(200) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  options text NOT NULL,
  votes text NOT NULL,
  numoptions smallint unsigned NOT NULL default '0',
  numvotes smallint unsigned NOT NULL default '0',
  timeout bigint(30) NOT NULL default '0',
  closed int(1) NOT NULL default '0',
  multiple int(1) NOT NULL default '0',
  public int(1) NOT NULL default '0',
  PRIMARY KEY (pid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_pollvotes (
  vid int unsigned NOT NULL auto_increment,
  pid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  voteoption smallint unsigned NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  KEY pid (pid, uid),
  PRIMARY KEY (vid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_posts (
  pid int unsigned NOT NULL auto_increment,
  tid int unsigned NOT NULL default '0',
  replyto int unsigned NOT NULL default '0',
  fid smallint unsigned NOT NULL default '0',
  subject varchar(120) NOT NULL default '',
  icon smallint unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  username varchar(80) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  message text NOT NULL,
  ipaddress varchar(30) NOT NULL default '',
  longipaddress int(11) NOT NULL default '0',
  includesig int(1) NOT NULL default '0',
  smilieoff int(1) NOT NULL default '0',
  edituid int unsigned NOT NULL default '0',
  edittime int(10) NOT NULL default '0',
  editreason varchar(200) NOT NULL default '',
  visible int(1) NOT NULL default '0',
  posthash varchar(32) NOT NULL default '',
  KEY tid (tid, uid),
  KEY uid (uid),
  KEY visible (visible),
  KEY dateline (dateline),
  KEY longipaddress (longipaddress),
  PRIMARY KEY (pid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_privatemessages (
  pmid int unsigned NOT NULL auto_increment,
  uid int unsigned NOT NULL default '0',
  toid int unsigned NOT NULL default '0',
  fromid int unsigned NOT NULL default '0',
  recipients text NOT NULL,
  folder smallint unsigned NOT NULL default '1',
  subject varchar(120) NOT NULL default '',
  icon smallint unsigned NOT NULL default '0',
  message text NOT NULL,
  dateline bigint(30) NOT NULL default '0',
  deletetime bigint(30) NOT NULL default '0',
  status int(1) NOT NULL default '0',
  statustime bigint(30) NOT NULL default '0',
  includesig int(1) NOT NULL default '0',
  smilieoff int(1) NOT NULL default '0',
  receipt int(1) NOT NULL default '0',
  readtime bigint(30) NOT NULL default '0',
  KEY uid (uid, folder),
  PRIMARY KEY (pmid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_profilefields (
  fid smallint unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  description text NOT NULL,
  disporder smallint unsigned NOT NULL default '0',
  type text NOT NULL,
  length smallint unsigned NOT NULL default '0',
  maxlength smallint unsigned NOT NULL default '0',
  required int(1) NOT NULL default '0',
  editable int(1) NOT NULL default '0',
  hidden int(1) NOT NULL default '0',
  PRIMARY KEY (fid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_promotions (
  pid int unsigned NOT NULL auto_increment,
  title varchar(120) NOT NULL default '',
  description text NOT NULL,
  enabled tinyint(1) NOT NULL default '1',
  logging tinyint(1) NOT NULL default '0',
  posts int NOT NULL default '0',
  posttype char(2) NOT NULL default '',
  registered int NOT NULL default '0',
  registeredtype varchar(20) NOT NULL default '',
  reputations int NOT NULL default '0',
  reputationtype char(2) NOT NULL default '',
  requirements varchar(200) NOT NULL default '',
  originalusergroup varchar(120) NOT NULL default '0',
  newusergroup smallint unsigned NOT NULL default '0',
  usergrouptype varchar(120) NOT NULL default '0',
  PRIMARY KEY (pid)
) TYPE=MyISAM;";
	
$tables[] = "CREATE TABLE daddyobb_promotionlogs (
  plid int unsigned NOT NULL auto_increment,
  pid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  oldusergroup varchar(200) NOT NULL default '0',
  newusergroup smallint NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  type varchar(9) NOT NULL default 'primary',
  PRIMARY KEY(plid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_reportedposts (
  rid int unsigned NOT NULL auto_increment,
  pid int unsigned NOT NULL default '0',
  tid int unsigned NOT NULL default '0',
  fid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  reportstatus int(1) NOT NULL default '0',
  reason varchar(250) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  KEY fid (fid),
  KEY dateline (dateline),
  PRIMARY KEY (rid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_reputation (
  rid int unsigned NOT NULL auto_increment,
  uid int unsigned NOT NULL default '0',
  adduid int unsigned NOT NULL default '0',
  reputation bigint(30) NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  comments text NOT NULL,
  KEY uid (uid),
  KEY dateline (dateline),
  PRIMARY KEY (rid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_searchlog (
  sid varchar(32) NOT NULL default '',
  uid int unsigned NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  ipaddress varchar(120) NOT NULL default '',
  threads text NOT NULL,
  posts text NOT NULL,
  resulttype varchar(10) NOT NULL default '',
  querycache text NOT NULL,
  keywords text NOT NULL,
  PRIMARY KEY (sid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_sessions (
  sid varchar(32) NOT NULL default '',
  uid int unsigned NOT NULL default '0',
  ip varchar(40) NOT NULL default '',
  time bigint(30) NOT NULL default '0',
  location varchar(150) NOT NULL default '',
  useragent varchar(100) NOT NULL default '',
  anonymous int(1) NOT NULL default '0',
  nopermission int(1) NOT NULL default '0',
  error int(1) NOT NULL default '0',
  location1 int(10) NOT NULL default '0',
  location2 int(10) NOT NULL default '0',
  PRIMARY KEY(sid),
  KEY location1 (location1),
  KEY location2 (location2),
  KEY time (time),
  KEY uid (uid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_settinggroups (
  gid smallint unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  title varchar(220) NOT NULL default '',
  description text NOT NULL,
  disporder smallint unsigned NOT NULL default '0',
  isdefault int(1) NOT NULL default '0',
  PRIMARY KEY (gid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_settings (
  sid smallint unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  title varchar(120) NOT NULL default '',
  description text NOT NULL,
  optionscode text NOT NULL,
  value text NOT NULL,
  disporder smallint unsigned NOT NULL default '0',
  gid smallint unsigned NOT NULL default '0',
  isdefault int(1) NOT NULL default '0',
  PRIMARY KEY (sid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_smilies (
  sid smallint unsigned NOT NULL auto_increment,
  name varchar(120) NOT NULL default '',
  find varchar(120) NOT NULL default '',
  image varchar(220) NOT NULL default '',
  disporder smallint unsigned NOT NULL default '0',
  showclickable int(1) NOT NULL default '0',
  PRIMARY KEY  (sid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_spiders (
	sid int unsigned NOT NULL auto_increment,
	name varchar(100) NOT NULL default '',
	theme int unsigned NOT NULL default '0',
	language varchar(20) NOT NULL default '',
	usergroup int unsigned NOT NULL default '0',
	useragent varchar(200) NOT NULL default '',
	lastvisit bigint(30) NOT NULL default '0',
	PRIMARY KEY (sid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_stats (
	dateline bigint(30) NOT NULL default '0',
	numusers int unsigned NOT NULL default '0',
	numthreads int unsigned NOT NULL default '0',
	numposts int unsigned NOT NULL default '0',
	PRIMARY KEY(dateline)
) TYPE=MyISAM;";
	
$tables[] = "CREATE TABLE daddyobb_tasks (
	tid int unsigned NOT NULL auto_increment,
	title varchar(120) NOT NULL default '',
	description text NOT NULL,
	file varchar(30) NOT NULL default '',
	minute varchar(200) NOT NULL default '',
	hour varchar(200) NOT NULL default '',
	day varchar(100) NOT NULL default '',
	month varchar(30) NOT NULL default '',
	weekday varchar(15) NOT NULL default '',
	nextrun bigint(30) NOT NULL default '0',
	lastrun bigint(30) NOT NULL default '0',
	enabled int(1) NOT NULL default '1',
	logging int(1) NOT NULL default '0',
	locked bigint(30) NOT NULL default '0',
	PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_tasklog (
	lid int unsigned NOT NULL auto_increment,
	tid int unsigned NOT NULL default '0',
	dateline bigint(30) NOT NULL default '0',
	data text NOT NULL,
	PRIMARY KEY (lid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_templategroups (
  gid int unsigned NOT NULL auto_increment,
  prefix varchar(50) NOT NULL default '',
  title varchar(100) NOT NULL default '',
  PRIMARY KEY (gid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_templates (
  tid int unsigned NOT NULL auto_increment,
  title varchar(120) NOT NULL default '',
  template text NOT NULL,
  sid int(10) NOT NULL default '0',
  version varchar(20) NOT NULL default '0',
  status varchar(10) NOT NULL default '',
  dateline int(10) NOT NULL default '0',
  PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_templatesets (
  sid smallint unsigned NOT NULL auto_increment,
  title varchar(120) NOT NULL default '',
  PRIMARY KEY  (sid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_themes (
  tid smallint unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  pid smallint unsigned NOT NULL default '0',
  def smallint(1) NOT NULL default '0',
  properties text NOT NULL,
  stylesheets text NOT NULL,
  allowedgroups text NOT NULL,
  PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_themestylesheets(
	sid int unsigned NOT NULL auto_increment,
	name varchar(30) NOT NULL default '',
	tid int unsigned NOT NULL default '0',
	attachedto text NOT NULL,
	stylesheet text NOT NULL,
	cachefile varchar(100) NOT NULL default '',
	lastmodified bigint(30) NOT NULL default '0',
	PRIMARY KEY(sid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_threadratings (
  rid int unsigned NOT NULL auto_increment,
  tid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  rating smallint unsigned NOT NULL default '0',
  ipaddress varchar(30) NOT NULL default '',
  KEY tid (tid, uid),
  PRIMARY KEY (rid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_threadviews (
	tid int unsigned NOT NULL default '0',
	KEY (tid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_threads (
  tid int unsigned NOT NULL auto_increment,
  fid smallint unsigned NOT NULL default '0',
  subject varchar(120) NOT NULL default '',
  icon smallint unsigned NOT NULL default '0',
  poll int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  username varchar(80) NOT NULL default '',
  dateline bigint(30) NOT NULL default '0',
  firstpost int unsigned NOT NULL default '0',
  lastpost bigint(30) NOT NULL default '0',
  lastposter varchar(120) NOT NULL default '',
  lastposteruid int unsigned NOT NULL default '0',
  views int(100) NOT NULL default '0',
  replies int(100) NOT NULL default '0',
  closed varchar(30) NOT NULL default '',
  sticky int(1) NOT NULL default '0',
  numratings smallint unsigned NOT NULL default '0',
  totalratings smallint unsigned NOT NULL default '0',
  notes text NOT NULL,
  visible int(1) NOT NULL default '0',
  unapprovedposts int(10) unsigned NOT NULL default '0',
  attachmentcount int(10) unsigned NOT NULL default '0',
  deletetime int(10) unsigned NOT NULL default '0',
  KEY fid (fid, visible, sticky),
  KEY dateline (dateline),
  KEY lastpost (lastpost, fid),
  KEY firstpost (firstpost),
  KEY uid (uid),
  PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_threadsread (
  tid int unsigned NOT NULL default '0',
  uid int unsigned NOT NULL default '0',
  dateline int(10) NOT NULL default '0',
  KEY dateline (dateline),
  UNIQUE KEY tid (tid,uid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_threadsubscriptions (
  sid int unsigned NOT NULL auto_increment,
  uid int unsigned NOT NULL default '0',
  tid int unsigned NOT NULL default '0',
  notification int(1) NOT NULL default '0',
  dateline bigint(30) NOT NULL default '0',
  subscriptionkey varchar(32) NOT NULL default '',
  KEY uid (uid),
  KEY tid (tid,notification),
  PRIMARY KEY (sid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_userfields (
  ufid int unsigned NOT NULL default '0',
  fid1 text NOT NULL,
  fid2 text NOT NULL,
  fid3 text NOT NULL,
  PRIMARY KEY (ufid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_usergroups (
  gid smallint unsigned NOT NULL auto_increment,
  type smallint(2) NOT NULL default '2',
  title varchar(120) NOT NULL default '',
  description text NOT NULL,
  namestyle varchar(200) NOT NULL default '{username}',
  usertitle varchar(120) NOT NULL default '',
  stars smallint(4) NOT NULL default '0',
  starimage varchar(120) NOT NULL default '',
  image varchar(120) NOT NULL default '',
  disporder smallint(6) unsigned NOT NULL,
  isbannedgroup int(1) NOT NULL default '0',
  canview int(1) NOT NULL default '0',
  canviewthreads int(1) NOT NULL default '0',
  canviewprofiles int(1) NOT NULL default '0',
  candlattachments int(1) NOT NULL default '0',
  canpostthreads int(1) NOT NULL default '0',
  canpostreplys int(1) NOT NULL default '0',
  canpostattachments int(1) NOT NULL default '0',
  canratethreads int(1) NOT NULL default '0',
  caneditposts int(1) NOT NULL default '0',
  candeleteposts int(1) NOT NULL default '0',
  candeletethreads int(1) NOT NULL default '0',
  caneditattachments int(1) NOT NULL default '0',
  avatarmaxsize varchar(50) NOT NULL default '40',
  avatarmaxdimensions varchar(50) NOT NULL default '100x100',
  profilepicmaxsize varchar(50) NOT NULL default '65',
  profilepicmaxdimensions varchar(50) NOT NULL default '130x130',
  sigmaxchars varchar(10) NOT NULL default '1000',
  signumimages varchar(10) NOT NULL default '4',
  sigallowmycode int(1) NOT NULL default '1',
  sigallowimgcode int(1) NOT NULL default '1',
  sigallowsmilies int(1) NOT NULL default '1',
  sigallowhtml int(1) NOT NULL default '0',
  canpostpolls int(1) NOT NULL default '0',
  canvotepolls int(1) NOT NULL default '0',
  canusevms int(1) NOT NULL default '1',
  caneditownvms int(1) NOT NULL default '1',
  candeleteownvms int(1) NOT NULL default '1',
  canmanagevms int(1) NOT NULL default '1',
  canusepms int(1) NOT NULL default '0',
  cansendpms int(1) NOT NULL default '0',
  cantrackpms int(1) NOT NULL default '0',
  candenypmreceipts int(1) NOT NULL default '0',
  pmquota int(3) NOT NULL default '0',
  maxpmrecipients int(4) NOT NULL default '5',
  cansendemail int(1) NOT NULL default '0',
  maxemails int(3) NOT NULL default '5',
  canviewmemberlist int(1) NOT NULL default '0',
  canviewcalendar int(1) NOT NULL default '0',
  canaddevents int(1) NOT NULL default '0',
  canbypasseventmod int(1) NOT NULL default '0',
  canmoderateevents int(1) NOT NULL default '0',
  canviewonline int(1) NOT NULL default '0',
  canviewwolinvis int(1) NOT NULL default '0',
  canviewonlineips int(1) NOT NULL default '0',
  cancp int(1) NOT NULL default '0',
  issupermod int(1) NOT NULL default '0',
  cansearch int(1) NOT NULL default '0',
  canusercp int(1) NOT NULL default '0',
  canuploadavatars int(1) NOT NULL default '1',
  canuploadprofilepics int(1) NOT NULL default '1',
  canratemembers int(1) NOT NULL default '0',
  canchangename int(1) NOT NULL default '0',
  showforumteam int(1) NOT NULL default '0',
  usereputationsystem int(1) NOT NULL default '0',
  cangivereputations int(1) NOT NULL default '0',
  reputationpower bigint(30) NOT NULL default '0',
  maxreputationsday bigint(30) NOT NULL default '0',
  candisplaygroup int(1) NOT NULL default '0',
  attachquota bigint(30) NOT NULL default '0',
  cancustomtitle int(1) NOT NULL default '0',
  canhtmlintitle int(1) NOT NULL default '0',
  canwarnusers int(1) NOT NULL default '0',
  canreceivewarnings int(1) NOT NULL default '0',
  maxwarningsday int(3) NOT NULL default '3',
  canmodcp int(1) NOT NULL default '0',
  modcanannouncements int(1) NOT NULL default '0',
  modcanviewmodlogs int(1) NOT NULL default '0',
  modcanmanagevms int(1) NOT NULL default '0',
  modcanmanageprofiles int(1) NOT NULL default '0',
  modcanbanusers int(1) NOT NULL default '0',
  PRIMARY KEY (gid)
) TYPE=MyISAM;";


$tables[] = "CREATE TABLE daddyobb_users (
  uid int unsigned NOT NULL auto_increment,
  username varchar(120) NOT NULL default '',
  password varchar(120) NOT NULL default '',
  salt varchar(10) NOT NULL default '',
  loginkey varchar(50) NOT NULL default '',
  email varchar(220) NOT NULL default '',
  postnum int(10) NOT NULL default '0',
  profilepic varchar(200) NOT NULL default '',
  profilepicdimensions varchar(10) NOT NULL default '',
  avatar varchar(200) NOT NULL default '',
  avatardimensions varchar(10) NOT NULL default '',
  avatartype varchar(10) NOT NULL default '0',
  usergroup smallint unsigned NOT NULL default '0',
  additionalgroups varchar(200) NOT NULL default '',
  displaygroup smallint unsigned NOT NULL default '0',
  usertitle varchar(250) NOT NULL default '',
  regdate bigint(30) NOT NULL default '0',
  lastactive bigint(30) NOT NULL default '0',
  lastvisit bigint(30) NOT NULL default '0',
  lastpost bigint(30) NOT NULL default '0',
  website varchar(200) NOT NULL default '',
  icq varchar(10) NOT NULL default '',
  aim varchar(50) NOT NULL default '',
  yahoo varchar(50) NOT NULL default '',
  msn varchar(75) NOT NULL default '',
  birthday varchar(15) NOT NULL default '',
  birthdayprivacy varchar(4) NOT NULL default 'all',
  vcard int(1) NOT NULL default '0',
  signature text NOT NULL,
  allownotices int(1) NOT NULL default '0',
  hideemail int(1) NOT NULL default '0',
  subscriptionmethod int(1) NOT NULL default '0',
  invisible int(1) NOT NULL default '0',
  receivepms int(1) NOT NULL default '0',
  pmnotice int(1) NOT NULL default '0',
  pmnotify int(1) NOT NULL default '0',
  remember int(1) NOT NULL default '0',
  threadmode varchar(8) NOT NULL default '',
  showsigs int(1) NOT NULL default '0',
  showavatars int(1) NOT NULL default '0',
  showquickreply int(1) NOT NULL default '0',
  showredirect int(1) NOT NULL default '0',
  ppp smallint(6) NOT NULL default '0',
  tpp smallint(6) NOT NULL default '0',
  daysprune smallint(6) NOT NULL default '0',
  dateformat varchar(4) NOT NULL default '',
  timeformat varchar(4) NOT NULL default '',
  timezone varchar(4) NOT NULL default '',
  dst int(1) NOT NULL default '0',
  dstcorrection int(1) NOT NULL default '0',
  buddylist text NOT NULL,
  ignorelist text NOT NULL,
  style smallint unsigned NOT NULL default '0',
  pmfolders text NOT NULL,
  notepad text NOT NULL,
  referrer int unsigned NOT NULL default '0',
  reputation bigint(30) NOT NULL default '0',
  regip varchar(50) NOT NULL default '',
  lastip varchar(50) NOT NULL default '',
  longregip int(11) NOT NULL default '0',
  longlastip int(11) NOT NULL default '0',
  language varchar(50) NOT NULL default '',
  showcodebuttons int(1) NOT NULL default '1',
  totalpms int(10) NOT NULL default '0',
  unreadpms int(10) NOT NULL default '0',
  enablevms int(1) NOT NULL default '1',
  limitvms int(1) NOT NULL default '0',
  warningpoints int(3) NOT NULL default '0',
  moderateposts int(1) NOT NULL default '0',
  moderationtime bigint(30) NOT NULL default '0',
  suspendposting int(1) NOT NULL default '0',
  suspensiontime bigint(30) NOT NULL default '0',
  coppauser int(1) NOT NULL default '0',
  classicpostbit int(1) NOT NULL default '0',
  loginattempts tinyint(2) NOT NULL default '1',
  failedlogin bigint(30) NOT NULL default '0',
  UNIQUE KEY username (username),
  KEY usergroup (usergroup),
  KEY birthday (birthday),
  KEY longregip (longregip),
  KEY longlastip (longlastip),
  PRIMARY KEY (uid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_usertitles (
  utid smallint unsigned NOT NULL auto_increment,
  posts int unsigned NOT NULL default '0',
  title varchar(250) NOT NULL default '',
  stars smallint(4) NOT NULL default '0',
  starimage varchar(120) NOT NULL default '',
  PRIMARY KEY (utid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_visitormessage (
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

$tables[] = "CREATE TABLE daddyobb_warninglevels (
	lid int unsigned NOT NULL auto_increment,
	percentage int(3) NOT NULL default '0',
	action text NOT NULL,
	PRIMARY KEY (lid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_warningtypes (
	tid int unsigned NOT NULL auto_increment,
	title varchar(120) NOT NULL default '',
	points int unsigned NOT NULL default '0',
	expirationtime bigint(30) NOT NULL default '0',
	PRIMARY KEY (tid)
) TYPE=MyISAM;";

$tables[] = "CREATE TABLE daddyobb_warnings (
	wid int unsigned NOT NULL auto_increment,
	uid int unsigned NOT NULL default '0',
	tid int unsigned NOT NULL default '0',
	pid int unsigned NOT NULL default '0',
	title varchar(120) NOT NULL default '',
	points int unsigned NOT NULL default '0',
	dateline bigint(30) NOT NULL default '0',
	issuedby int unsigned NOT NULL default '0',
	expires bigint(30) NOT NULL default '0',
	expired int(1) NOT NULL default '0',
	daterevoked bigint(30) NOT NULL default '0',
	revokedby int unsigned NOT NULL default '0',
	revokereason text NOT NULL,
	notes text NOT NULL,
	PRIMARY KEY (wid)
) TYPE=MyISAM;";

?>