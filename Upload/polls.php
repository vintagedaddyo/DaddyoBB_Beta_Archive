<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:07 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'polls.php');

$templatelist = "poll_newpoll,redirect_pollposted,redirect_pollupdated,redirect_votethanks";
require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("polls");

if($daddyobb->user['uid'] != 0)
{
	eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
}
else
{
	eval("\$loginbox = \"".$templates->get("loginbox")."\";");
}

if($daddyobb->input['preview'] || $daddyobb->input['updateoptions'])
{
	if($daddyobb->input['action'] == "do_editpoll")
	{
		$daddyobb->input['action'] = "editpoll";
	}
	else
	{
		$daddyobb->input['action'] = "newpoll";
	}
}
if($daddyobb->input['action'] == "newpoll")
{
	// Form for new poll
	$tid = intval($daddyobb->input['tid']);

	$plugins->run_hooks("polls_newpoll_start");

	$query = $db->simple_select("threads", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$thread = $db->fetch_array($query);
	$fid = $thread['fid'];
	$forumpermissions = forum_permissions($fid);

	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}
	// Make navigation
	build_forum_breadcrumb($fid);
	add_breadcrumb(htmlspecialchars_uni($thread['subject']), get_thread_link($thread['tid']));
	add_breadcrumb($lang->nav_postpoll);

	// No permission if: Not thread author; not moderator; no forum perms to view, post threads, post polls
	if(($thread['uid'] != $daddyobb->user['uid'] && !is_moderator($fid)) || ($forumpermissions['canview'] == 0 || $forumpermissions['canpostthreads'] == 0 || $forumpermissions['canpostpolls'] == 0))
	{
		error_no_permission();
	}

	if($thread['poll'])
	{
		error($lang->error_pollalready);
	}

	// Sanitize number of poll options
	if($daddyobb->input['numpolloptions'] > 0)
	{
		$daddyobb->input['polloptions'] = $daddyobb->input['numpolloptions'];
	}
	if($daddyobb->settings['maxpolloptions'] && $daddyobb->input['polloptions'] > $daddyobb->settings['maxpolloptions'])
	{	// Too big
		$polloptions = $daddyobb->settings['maxpolloptions'];
	}
	elseif($daddyobb->input['polloptions'] < 2)
	{	// Too small
		$polloptions = 2;
	}
	else
	{	// Just right
		$polloptions = intval($daddyobb->input['polloptions']);
	}

	$question = htmlspecialchars_uni($daddyobb->input['question']);

	$postoptions = $daddyobb->input['postoptions'];
	if($postoptions['multiple'] == 1)
	{
		$postoptionschecked['multiple'] = 'checked="checked"';
	}
	if($postoptions['public'] == 1)
	{
		$postoptionschecked['public'] = 'checked="checked"';
	}

	$options = $daddyobb->input['options'];
	$optionbits = '';
	for($i = 1; $i <= $polloptions; ++$i)
	{
		$option = $options[$i];
		$option = htmlspecialchars_uni($option);
		eval("\$optionbits .= \"".$templates->get("polls_newpoll_option")."\";");
		$option = "";
	}

	if($daddyobb->input['timeout'] > 0)
	{
		$timeout = intval($daddyobb->input['timeout']);
	}
	else
	{
		$timeout = 0;
	}

	$plugins->run_hooks("polls_newpoll_end");

	eval("\$newpoll = \"".$templates->get("polls_newpoll")."\";");
	output_page($newpoll);
}
if($daddyobb->input['action'] == "do_newpoll" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("polls_do_newpoll_start");

	$query = $db->simple_select("threads", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$thread = $db->fetch_array($query);
	$fid = $thread['fid'];
	$forumpermissions = forum_permissions($fid);

	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}

	// No permission if: Not thread author; not moderator; no forum perms to view, post threads, post polls
	if(($thread['uid'] != $daddyobb->user['uid'] && !is_moderator($fid)) || ($forumpermissions['canview'] == 0 || $forumpermissions['canpostthreads'] == 0 || $forumpermissions['canpostpolls'] == 0))
	{
		error_no_permission();
	}

	if($thread['poll'])
	{
		error($lang->error_pollalready);
	}

	$polloptions = $daddyobb->input['polloptions'];
	if($daddyobb->settings['maxpolloptions'] && $polloptions > $daddyobb->settings['maxpolloptions'])
	{
		$polloptions = $daddyobb->settings['maxpolloptions'];
	}

	$postoptions = $daddyobb->input['postoptions'];
	if($postoptions['multiple'] != '1')
	{
		$postoptions['multiple'] = 0;
	}

	if($postoptions['public'] != '1')
	{
		$postoptions['public'] = 0;
	}
	
	if($polloptions < 2)
	{
		$polloptions = "2";
	}
	$optioncount = "0";
	$options = $daddyobb->input['options'];
	
	for($i = 1; $i <= $polloptions; ++$i)
	{
		if(trim($options[$i]) != "")
		{
			$optioncount++;
		}
		
		if(my_strlen($options[$i]) > $daddyobb->settings['polloptionlimit'] && $daddyobb->settings['polloptionlimit'] != 0)
		{
			$lengtherror = 1;
			break;
		}
	}
	
	if($lengtherror)
	{
		error($lang->error_polloptiontoolong);
	}
	
	if(empty($daddyobb->input['question']) || $optioncount < 2)
	{
		error($lang->error_noquestionoptions);
	}
	
	$optionslist = '';
	$voteslist = '';
	for($i = 1; $i <= $optioncount; ++$i)
	{
		if(trim($options[$i]) != '')
		{
			if($i > 1)
			{
				$optionslist .= '||~|~||';
				$voteslist .= '||~|~||';
			}
			$optionslist .= $options[$i];
			$voteslist .= '0';
		}
	}
	
	if($daddyobb->input['timeout'] > 0)
	{
		$timeout = intval($daddyobb->input['timeout']);
	}
	else
	{
		$timeout = 0;
	}
	
	$newpoll = array(
		"tid" => $thread['tid'],
		"question" => $db->escape_string($daddyobb->input['question']),
		"dateline" => TIME_NOW,
		"options" => $db->escape_string($optionslist),
		"votes" => $db->escape_string($voteslist),
		"numoptions" => intval($optioncount),
		"numvotes" => 0,
		"timeout" => $timeout,
		"closed" => 0,
		"multiple" => $postoptions['multiple'],
		"public" => $postoptions['public']
	);

	$plugins->run_hooks("polls_do_newpoll_process");

	$pid = $db->insert_query("polls", $newpoll);

	$db->update_query("threads", array('poll' => $pid, 'icon' => '21'), "tid='".$thread['tid']."'");

	$plugins->run_hooks("polls_do_newpoll_end");

	if($thread['visible'] == 1)
	{
		redirect(get_thread_link($thread['tid']), $lang->redirect_pollposted);
	}
	else
	{
		redirect(get_forum_link($forum['fid']), $lang->redirect_pollpostedmoderated);
	}
}

if($daddyobb->input['action'] == "editpoll")
{
	$pid = intval($daddyobb->input['pid']);

	$plugins->run_hooks("polls_editpoll_start");

	$query = $db->simple_select("polls", "*", "pid='$pid'");
	$poll = $db->fetch_array($query);

	$query = $db->simple_select("threads", "*", "poll='$pid'");
	$thread = $db->fetch_array($query);
	$tid = $thread['tid'];
	$fid = $thread['fid'];

	// Make navigation
	build_forum_breadcrumb($fid);
	add_breadcrumb(htmlspecialchars_uni($thread['subject']), get_thread_link($thread['tid']));
	add_breadcrumb($lang->nav_editpoll);


	$forumpermissions = forum_permissions($fid);

	$query = $db->simple_select("forums", "*", "fid='$fid'");
	$forum = $db->fetch_array($query);


	if($thread['visible'] == "0" || !$tid)
	{
		error($lang->error_invalidthread);
	}
	
	if(!is_moderator($fid, "caneditposts"))
	{
		error_no_permission();
	}
	
	$polldate = my_date($daddyobb->settings['dateformat'], $poll['dateline']);
	if(!$daddyobb->input['preview'] && !$daddyobb->input['updateoptions'])
	{
		if($poll['closed'] == 1)
		{
			$postoptionschecked['closed'] = 'checked="checked"';
		}
		
		if($poll['multiple'] == 1)
		{
			$postoptionschecked['multiple'] = 'checked="checked"';
		}
		
		if($poll['public'] == 1)
		{
			$postoptionschecked['public'] = 'checked="checked"';
		}

		$optionsarray = explode("||~|~||", $poll['options']);
		$votesarray = explode("||~|~||", $poll['votes']);


		for($i = 1; $i <= $poll['numoptions']; ++$i)
		{
			$poll['totvotes'] = $poll['totvotes'] + $votesarray[$i-1];
		}
		
		$question = htmlspecialchars_uni($poll['question']);
		$numoptions = $poll['numoptions'];
		$optionbits = "";
		for($i = 0; $i < $numoptions; ++$i)
		{
			$counter = $i + 1;
			$option = $optionsarray[$i];
			$option = htmlspecialchars_uni($option);
			$optionvotes = intval($votesarray[$i]);
			
			if(!$optionvotes)
			{
				$optionvotes = 0;
			}
			
			eval("\$optionbits .= \"".$templates->get("polls_editpoll_option")."\";");
			$option = "";
			$optionvotes = "";
		}
		
		if(!$poll['timeout'])
		{
			$timeout = 0;
		}
		else
		{
			$timeout = $poll['timeout'];
		}
	}
	else
	{
		if($daddyobb->settings['maxpolloptions'] && $daddyobb->input['numoptions'] > $daddyobb->settings['maxpolloptions'])
		{
			$numoptions = $daddyobb->settings['maxpolloptions'];
		}
		elseif($daddyobb->input['numoptions'] < 2)
		{
			$numoptions = "2";
		}
		else
		{
			$numoptions = $daddyobb->input['numoptions'];
		}
		$question = htmlspecialchars_uni($daddyobb->input['question']);

		$postoptions = $daddyobb->input['postoptions'];
		if($postoptions['multiple'] == 1)
		{
			$postoptionschecked['multiple'] = 'checked="checked"';
		}
		
		if($postoptions['public'] == 1)
		{
			$postoptionschecked['public'] = 'checked="checked"';
		}
		
		if($postoptions['closed'] == 1)
		{
			$postoptionschecked['closed'] = 'checked="checked"';
		}

		$options = $daddyobb->input['options'];
		$votes = $daddyobb->input['votes'];
		$optionbits = '';
		for($i = 1; $i <= $numoptions; ++$i)
		{
			$counter = $i;
			$option = $options[$i];
			$option = htmlspecialchars_uni($option);
			$optionvotes = $votes[$i];
			
			if(!$optionvotes)
			{
				$optionvotes = 0;
			}
			
			eval("\$optionbits .= \"".$templates->get("polls_editpoll_option")."\";");
			$option = "";
		}

		if($daddyobb->input['timeout'] > 0)
		{
			$timeout = $daddyobb->input['timeout'];
		}
		else
		{
			$timeout = 0;
		}
	}

	$plugins->run_hooks("polls_editpoll_end");

	eval("\$editpoll = \"".$templates->get("polls_editpoll")."\";");
	output_page($editpoll);
}

if($daddyobb->input['action'] == "do_editpoll" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("polls_do_editpoll_start");

	$query = $db->simple_select("polls", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$poll = $db->fetch_array($query);

	$query = $db->simple_select("threads", "*", "poll='".intval($daddyobb->input['pid'])."'");
	$thread = $db->fetch_array($query);

	$forumpermissions = forum_permissions($thread['fid']);

	$query = $db->simple_select("forums", "*", "fid='".$thread['fid']."'");
	$forum = $db->fetch_array($query);

	if($thread['visible'] == 0 || !$thread['tid'])
	{
		error($lang->error_invalidthread);
	}
	
	if(!is_moderator($thread['fid'], "caneditposts"))
	{
		error_no_permission();
	}

	if($daddyobb->settings['maxpolloptions'] && $daddyobb->input['numoptions'] > $daddyobb->settings['maxpolloptions'])
	{
		$numoptions = $daddyobb->settings['maxpolloptions'];
	}
	elseif(!$daddyobb->input['numoptions'])
	{
		$numoptions = 2;
	}
	else
	{
		$numoptions = $daddyobb->input['numoptions'];
	}

	$postoptions = $daddyobb->input['postoptions'];
	if($postoptions['multiple'] != '1')
	{
		$postoptions['multiple'] = 0;
	}
	
	if($postoptions['public'] != '1')
	{
		$postoptions['public'] = 0;
	}
	
	if($postoptions['closed'] != '1')
	{
		$postoptions['closed'] = 0;
	}
	$optioncount = "0";
	$options = $daddyobb->input['options'];

	for($i = 1; $i <= $numoptions; ++$i)
	{
		if(trim($options[$i]) != '')
		{
			$optioncount++;
		}
		
		if(my_strlen($options[$i]) > $daddyobb->settings['polloptionlimit'] && $daddyobb->settings['polloptionlimit'] != 0)
		{
			$lengtherror = 1;
			break;
		}
	}
	
	if($lengtherror)
	{
		error($lang->error_polloptiontoolong);
	}

	if(trim($daddyobb->input['question']) == '' || $optioncount < 2)
	{
		error($lang->error_noquestionoptions);
	}
	
	$optionslist = '';
	$voteslist = '';
	$numvotes = '';
	$votes = $daddyobb->input['votes'];
	for($i = 1; $i <= $optioncount; ++$i)
	{
		if(trim($options[$i]) != '')
		{
			if($i > 1)
			{
				$optionslist .= "||~|~||";
				$voteslist .= "||~|~||";
			}
			
			$optionslist .= $options[$i];
			if(intval($votes[$i]) <= 0)
			{
				$votes[$i] = "0";
			}
			$voteslist .= $votes[$i];
			$numvotes = $numvotes + $votes[$i];
		}
	}
	
	if($daddyobb->input['timeout'] > 0)
	{
		$timeout = intval($daddyobb->input['timeout']);
	}
	else
	{
		$timeout = 0;
	}
	
	$updatedpoll = array(
		"question" => $db->escape_string($daddyobb->input['question']),
		"options" => $db->escape_string($optionslist),
		"votes" => $db->escape_string($voteslist),
		"numoptions" => intval($numoptions),
		"numvotes" => $numvotes,
		"timeout" => $timeout,
		"closed" => $postoptions['closed'],
		"multiple" => $postoptions['multiple'],
		"public" => $postoptions['public']
	);

	$plugins->run_hooks("polls_do_editpoll_process");

	$db->update_query("polls", $updatedpoll, "pid='".intval($daddyobb->input['pid'])."'");

	$plugins->run_hooks("polls_do_editpoll_end");
	
	$modlogdata['fid'] = $thread['fid'];
	$modlogdata['tid'] = $thread['tid'];
	log_moderator_action($modlogdata, $lang->poll_edited);

	redirect(get_thread_link($thread['tid']), $lang->redirect_pollupdated);
}

if($daddyobb->input['action'] == "showresults")
{
	$query = $db->simple_select("polls", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$poll = $db->fetch_array($query);
	$tid = $poll['tid'];
	$query = $db->simple_select("threads", "*", "tid='$tid'");
	$thread = $db->fetch_array($query);
	$fid = $thread['fid'];

	// Get forum info
	$forum = get_forum($fid);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}

	$forumpermissions = forum_permissions($forum['fid']);

	$plugins->run_hooks("polls_showresults_start");

	if($forumpermissions['canviewthreads'] == 0 || $forumpermissions['canview'] == 0)
	{
		error($lang->error_pollpermissions);
	}
	
	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}
	
	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}

	// Make navigation
	build_forum_breadcrumb($fid);
	add_breadcrumb(htmlspecialchars_uni($thread['subject']), get_thread_link($thread['tid']));
	add_breadcrumb($lang->nav_pollresults);

	$voters = array();

	// Calculate votes
	$query = $db->query("
		SELECT v.*, u.username 
		FROM ".TABLE_PREFIX."pollvotes v 
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=v.uid) 
		WHERE v.pid='{$poll['pid']}' 
		ORDER BY u.username
	");
	while($voter = $db->fetch_array($query))
	{
		// Mark for current user's vote
		if($daddyobb->user['uid'] == $voter['uid'] && $daddyobb->user['uid'])
		{
			$votedfor[$voter['voteoption']] = 1;
		}

		// Count number of guests and users without a username (assumes they've been deleted)
		if($voter['uid'] == 0 || $voter['username'] == '')
		{
			// Add one to the number of voters for guests
			++$guest_voters[$voter['voteoption']];
		}
		else
		{
			$voters[$voter['voteoption']][$voter['uid']] = $voter['username'];
		}
	}
	
	$optionsarray = explode("||~|~||", $poll['options']);
	$votesarray = explode("||~|~||", $poll['votes']);
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$poll['totvotes'] = $poll['totvotes'] + $votesarray[$i-1];
	}
	
	$polloptions = '';
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$parser_options = array(
			"allow_html" => $forum['allowhtml'],
			"allow_mycode" => $forum['allowmycode'],
			"allow_smilies" => $forum['allowsmilies'],
			"allow_imgcode" => $forum['allowimgcode'],
			"filter_badwords" => 1
		);
		$option = $parser->parse_message($optionsarray[$i-1], $parser_options);

		$votes = $votesarray[$i-1];
		$number = $i;
		// Make the mark for current user's voted option
		if($votedfor[$number])
		{
			$optionbg = 'trow2';
			$votestar = '*';
		}
		else
		{
			$optionbg = 'trow1';
			$votestar = '';
		}
		
		if($votes == '0')
		{
			$percent = '0';
		}
		else
		{
			$percent = number_format($votes / $poll['totvotes'] * 100, 2);
		}
		
		$imagewidth = round($percent/3) * 5;
		$comma = '';
		$guest_comma = '';
		$userlist = '';
		$guest_count = 0;
		if($poll['public'] == 1 || is_moderator($fid))
		{
			if(is_array($voters[$number]))
			{
				foreach($voters[$number] as $uid => $username)
				{
					$userlist .= $comma.build_profile_link($username, $uid);
					$comma = $guest_comma = ', ';
				}
			}

			if($guest_voters[$number] > 0)
			{
				if($guest_voters[$number] == 1)
				{
					$userlist .= $guest_comma.$lang->guest_count;
				}
				else
				{
					$userlist .= $guest_comma.$lang->sprintf($lang->guest_count_multiple, $guest_voters[$number]);
				}
			}
		}
		eval("\$polloptions .= \"".$templates->get("polls_showresults_resultbit")."\";");
	}
	
	if($poll['totvotes'])
	{
		$totpercent = '100%';
	}
	else
	{
		$totpercent = '0%';
	}

	$plugins->run_hooks("polls_showresults_end");

	$poll['question'] = htmlspecialchars_uni($poll['question']);
	eval("\$showresults = \"".$templates->get("polls_showresults")."\";");
	output_page($showresults);
}
if($daddyobb->input['action'] == "vote")
{
	$query = $db->simple_select("polls", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$poll = $db->fetch_array($query);
	$poll['timeout'] = $poll['timeout']*60*60*24;

	$plugins->run_hooks("polls_vote_start");

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	$query = $db->simple_select("threads", "*", "poll='".$poll['pid']."'");
	$thread = $db->fetch_array($query);

	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}
	
	$fid = $thread['fid'];
	$forumpermissions = forum_permissions($fid);
	if($forumpermissions['canvotepolls'] == 0)
	{
		error_no_permission();
	}

	$expiretime = $poll['dateline'] + $poll['timeout'];
	$now = TIME_NOW;
	if($poll['closed'] == 1 || $thread['closed'] == 1 || ($expiretime < $now && $poll['timeout']))
	{
		error($lang->error_pollclosed);
	}
	
	if(!isset($daddyobb->input['option']))
	{
		error($lang->error_nopolloptions);
	}
	
	// Check if the user has voted before...
	if($daddyobb->user['uid'])
	{
		$query = $db->simple_select("pollvotes", "*", "uid='".$daddyobb->user['uid']."' AND pid='".$poll['pid']."'");
		$votecheck = $db->fetch_array($query);
	}
	
	if($votecheck['vid'] || $daddyobb->cookies['pollvotes'][$poll['pid']])
	{
		error($lang->error_alreadyvoted);
	}
	elseif(!$daddyobb->user['uid'])
	{
		// Give a cookie to guests to inhibit revotes
		my_setcookie("pollvotes[{$poll['pid']}]", '1');
	}
	
	$votesql = '';
	$now = TIME_NOW;
	$votesarray = explode("||~|~||", $poll['votes']);
	$option = $daddyobb->input['option'];
	$numvotes = $poll['numvotes'];
	if($poll['multiple'] == 1)
	{
		foreach($option as $voteoption => $vote)
		{
			if($vote == 1 && isset($votesarray[$voteoption-1]))
			{
				if($votesql)
				{
					$votesql .= ",";
				}
				$votesql .= "('".$poll['pid']."','".$daddyobb->user['uid']."','".$db->escape_string($voteoption)."','$now')";
				$votesarray[$voteoption-1]++;
				$numvotes = $numvotes+1;
			}
		}
	}
	else
	{
		if(!isset($votesarray[$option-1]))
		{
			error($lang->error_nopolloptions);
		}
		$votesql = "('".$poll['pid']."','".$daddyobb->user['uid']."','".$db->escape_string($option)."','$now')";
		$votesarray[$option-1]++;
		$numvotes = $numvotes+1;
	}

	$db->write_query("
		INSERT INTO 
		".TABLE_PREFIX."pollvotes (pid,uid,voteoption,dateline) 
		VALUES $votesql
	");
	$voteslist = '';
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		if($i > 1)
		{
			$voteslist .= "||~|~||";
		}
		$voteslist .= $votesarray[$i-1];
	}
	$updatedpoll = array(
		"votes" => $db->escape_string($voteslist),
		"numvotes" => intval($numvotes),
	);

	$plugins->run_hooks("polls_vote_process");

	$db->update_query("polls", $updatedpoll, "pid='".$poll['pid']."'");

	$plugins->run_hooks("polls_vote_end");

	redirect(get_thread_link($poll['tid']), $lang->redirect_votethanks);
}

?>