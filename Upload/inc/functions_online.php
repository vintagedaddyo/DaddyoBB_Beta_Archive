<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 12:42 24.12.2008
  */

$uid_list = $aid_list = $pid_list = $tid_list = $fid_list = $eid_list = array();

/**
 * Fetch a users activity and any corresponding details from their location.
 *
 * @param string The location (URL) of the user.
 * @return array Array of location and activity information
 */
function fetch_wol_activity($location)
{
        global $uid_list, $aid_list, $pid_list, $tid_list, $fid_list, $eid_list, $plugins, $user, $parameters;

        $user_activity = array();

        $split_loc = explode(".php", $location);
        if($split_loc[0] == $user['location'])
        {
                $filename = '';
        }
        else
        {
                $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
        }
        if($split_loc[1])
        {
                $temp = explode("&amp;", my_substr($split_loc[1], 1));
                foreach($temp as $param)
                {
                        $temp2 = explode("=", $param, 2);
                        $parameters[$temp2[0]] = $temp2[1];
                }
        }

        switch($filename)
        {
                case "announcements":
                        if(is_numeric($parameters['fid']))
                        {
                                $fid_list[] = $parameters['fid'];
                        }
                        $user_activity['activity'] = "announcements";
                        $user_activity['fid'] = $parameters['fid'];
                        break;
                case "attachment":
                        if(is_numeric($parameters['aid']))
                        {
                                $aid_list[] = $parameters['aid'];
                        }
                        $user_activity['activity'] = "attachment";
                        $user_activity['aid'] = $parameters['aid'];
                        break;
                case "calendar":
                        if($parameters['action'] == "event")
                        {
                                if(is_numeric($parameters['eid']))
                                {
                                        $eid_list[] = $parameters['eid'];
                                }
                                $user_activity['activity'] = "calendar_event";
                                $user_activity['eid'] = $parameters['eid'];
                        }
                        elseif($parameters['action'] == "addevent" || $parameters['action'] == "do_addevent")
                        {
                                $user_activity['activity'] = "calendar_addevent";
                        }
                        elseif($parameters['action'] == "editevent" || $parameters['action'] == "do_editevent")
                        {
                                $user_activity['activity'] = "calendar_editevent";
                        }
                        else
                        {
                                $user_activity['activity'] = "calendar";
                        }
                        break;
                case "editpost":
                        if(is_numeric($parameters['pid']))
                        {
                                $pid_list[] = $parameters['pid'];
                        }
                        $user_activity['activity'] = "editpost";
                        $user_activity['pid'] = $parameters['pid'];
                        break;
                case "forumdisplay":
                        if(is_numeric($parameters['fid']))
                        {
                                $fid_list[] = $parameters['fid'];
                        }
                        $user_activity['activity'] = "forumdisplay";
                        $user_activity['fid'] = $parameters['fid'];
                        break;
                case "index":
                case '':
                        $user_activity['activity'] = "index";
                        break;
                case "managegroup":
                        $user_activity['activity'] = "managegroup";
                        break;
                case "member":
                        if($parameters['action'] == "activate")
                        {
                                $user_activity['activity'] = "member_activate";
                        }
                        elseif($parameters['action'] == "register" || $parameters['action'] == "do_register")
                        {
                                $user_activity['activity'] = "member_register";
                        }
                        elseif($parameters['action'] == "login" || $parameters['action'] == "do_login")
                        {
                                $user_activity['activity'] = "member_login";
                        }
                        elseif($parameters['action'] == "logout")
                        {
                                $user_activity['activity'] = "member_logout";
                        }
                        elseif($parameters['action'] == "profile")
                        {
                                $user_activity['activity'] = "member_profile";
                                if(is_numeric($parameters['uid']))
                                {
                                        $uid_list[] = $parameters['uid'];
                                }
                                $user_activity['uid'] = $parameters['uid'];
                        }
                        elseif($parameters['action'] == "emailuser" || $parameters['action'] == "do_emailuser")
                        {
                                $user_activity['activity'] = "member_emailuser";
                        }
                        elseif($parameters['action'] == "rate" || $parameters['action'] == "do_rate")
                        {
                                $user_activity['activity'] = "member_rate";
                        }
                        elseif($parameters['action'] == "resendactivation" || $parameters['action'] == "do_resendactivation")
                        {
                                $user_activity['activity'] = "member_resendactivation";
                        }
                        elseif($parameters['action'] == "lostpw" || $parameters['action'] == "do_lostpw" || $parameters['action'] == "resetpassword")
                        {
                                $user_activity['activity'] = "member_lostpw";
                        }
                        else
                        {
                                $user_activity['activity'] = "member";
                        }
                        break;
                case "memberlist":
                        if($parameters['action'] == "search")
                        {
                                $user_activity['activity'] = "memberlist_search";
                        }
                        else
                        {
                                $user_activity['activity'] = "memberlist";
                        }
                        break;
                case "misc":
                        $accepted_parameters = array("markread", "help", "buddypopup", "smilies", "syndication", "imcenter", "dstswitch");
                        if($parameters['action'] == "whoposted")
                        {
                                if(is_numeric($parameters['tid']))
                                {
                                        $tid_list[] = $parameters['tid'];
                                }
                                $user_activity['activity'] = "misc_whoposted";
                                $user_activity['tid'] = $parameters['tid'];
                        }
                        elseif(in_array($parameters['action'], $accepted_parameters))
                        {
                                $user_activity['activity'] = "misc_".$parameters['action'];
                        }
                        else
                        {
                                $user_activity['activity'] = "misc";
                        }
                        break;
                case "modcp":
                        $accepted_parameters = array("modlogs", "announcements", "finduser", "warninglogs", "ipsearch");

                        foreach($accepted_parameters as $action)
                        {
                                if($parameters['action'] == $action)
                                {
                                        $user_activity['activity'] = "modcp_".$action;
                                        break;
                                }
                        }

                        $accepted_parameters = array();
                        $accepted_parameters['report'] = array("do_reports", "reports", "allreports");
                        $accepted_parameters['new_annoucement'] = array("do_new_announcement", "new_announcement");
                        $accepted_parameters['delete_announcement'] = array("do_delete_announcement", "delete_announcement");
                        $accepted_parameters['edit_announcement'] = array("do_edit_announcement", "edit_announcement");
                        $accepted_parameters['mod_queue'] = array("do_modqueue", "modqueue");
                        $accepted_parameters['editprofile'] = array("do_editprofile", "editprofile");
                        $accepted_parameters['banning'] = array("do_banuser", "banning", "liftban", "banuser");

                        foreach($accepted_parameters as $name => $actions)
                        {
                                if(in_array($parameters['action'], $actions))
                                {
                                        $user_activity['activity'] = "modcp_".$name;
                                        break;
                                }
                        }

                        if(!$user_activity['activity'])
                        {
                                $user_activity['activity'] = "modcp";
                        }
                        break;
                case "moderation":
                        if($parameters['action'] == "move" && $parameters['modtype'] == "thread")
                        {
                          if(is_numeric($parameters['tid']))
                          {
                            $tid_list[] = $parameters['tid'];
                            $user_activity['tid'] = $parameters['tid'];
                          }
                          $user_activity['activity'] = "moderation_movethread";
                        }
                        elseif($parameters['action'] == "openclosethread" && $parameters['modtype'] == "thread")
                        {
                          if(is_numeric($parameters['tid']))
                          {
                            $tid_list[] = $parameters['tid'];
                            $user_activity['tid'] = $parameters['tid'];
                          }
                          $user_activity['activity'] = "moderation_openclosethread";
                        }
                        elseif($parameters['action'] == "stick" && $parameters['modtype'] == "thread")
                        {
                          if(is_numeric($parameters['tid']))
                          {
                            $tid_list[] = $parameters['tid'];
                            $user_activity['tid'] = $parameters['tid'];
                          }
                          $user_activity['activity'] = "moderation_stickunstick";
                        }
                        elseif($parameters['action'] == "threadnotes" && $parameters['modtype'] == "thread")
                        {
                          if(is_numeric($parameters['tid']))
                          {
                            $tid_list[] = $parameters['tid'];
                            $user_activity['tid'] = $parameters['tid'];
                          }
                          $user_activity['activity'] = "moderation_threadnotes";
                        }
                        elseif($parameters['action'] == "merge" && $parameters['modtype'] == "thread")
                        {
                          $user_activity['activity'] = "moderation_merge";
                        }
                        else
                        { 
                          $user_activity['activity'] = "moderation";
                        }
                        break;
                case "newreply":
                        if(is_numeric($parameters['pid']))
                        {
                                $pid_list[] = $parameters['pid'];
                                $user_activity['activity'] = "newreply";
                                $user_activity['pid'] = $parameters['pid'];
                        }
                        else
                        {
                                if(is_numeric($parameters['tid']))
                                {
                                        $tid_list[] = $parameters['tid'];
                                }
                                $user_activity['activity'] = "newreply";
                                $user_activity['tid'] = $parameters['tid'];
                        }
                        break;
                case "newthread":
                        if(is_numeric($parameters['fid']))
                        {
                                $fid_list[] = $parameters['fid'];
                        }
                        $user_activity['activity'] = "newthread";
                        $user_activity['fid'] = $parameters['fid'];
                        break;
                case "online":
                        if($parameters['action'] == "today")
                        {
                                $user_activity['activity'] = "woltoday";
                        }
                        else
                        {
                                $user_activity['activity'] = "wol";
                        }
                        break;
                case "polls":
                        // Make the "do" parts the same as the other one.
                        if($parameters['action'] == "do_newpoll")
                        {
                                $user_activity['activity'] = "newpoll";
                        }
                        elseif($parameters['action'] == "do_editpoll")
                        {
                                $user_activity['activity'] = "editpoll";
                        }
                        else
                        {
                                $accepted_parameters = array("do_editpoll", "editpoll", "newpoll", "do_newpoll", "showresults", "vote");

                                foreach($accepted_parameters as $action)
                                {
                                        if($parameters['action'] == $action)
                                        {
                                                $user_activity['activity'] = $action;
                                                break;
                                        }
                                }

                                if(!$user_activity['activity'])
                                {
                                        $user_activity['activity'] = "showresults";
                                }
                        }
                        break;
                case "printthread":
                        if(is_numeric($parameters['tid']))
                        {
                                $tid_list[] = $parameters['tid'];
                        }
                        $user_activity['activity'] = "printthread";
                        $user_activity['tid'] = $parameters['tid'];
                        break;
                case "private":
                        if($parameters['action'] == "send" || $parameters['action'] == "do_send")
                        {
                                $user_activity['activity'] = "private_send";
                                $user_activity['pmid'] = $parameters['pmid'];
                                if(is_numeric($parameters['uid']))
                                {
                                        $uid_list[] = $parameters['uid'];
                                }
                                $user_activity['uid'] = $parameters['uid'];
                        }
                        elseif($parameters['action'] == "read")
                        {
                                $user_activity['activity'] = "private_read";
                                if(is_numeric($parameters['pmid']))
                                {
                                        $pmid_list[] = $parameters['pmid'];
                                }
                                $user_activity['pmid'] = $parameters['pmid'];
                        }
                        elseif($parameters['action'] == "folders" || $parameters['action'] == "do_folders")
                        {
                                $user_activity['activity'] = "private_folders";
                        }
                        elseif($parameters['action'] == "export" || $parameters['action'] == "do_export")
                        {
                                $user_activity['activity'] = "private_download";
                        }
                        else
                        {
                                $user_activity['activity'] = "private";
                        }
                        break;
                case "ratethread":
                        $user_activity['activity'] = "ratethread";
                        break;
                case "report":
                        $user_activity['activity'] = "report";
                        break;
                case "reputation":
                        $user_activity['activity'] = "reputation";
                        break;
                case "search":
                        $user_activity['activity'] = "search";
                        break;
                case "sendthread":
                        if(is_numeric($parameters['tid']))
                        {
                                $tid_list[] = $parameters['tid'];
                        }
                        $user_activity['activity'] = "sendthread";
                        $user_activity['tid'] = $parameters['tid'];
                break;
                case "showteam":
                        $user_activity['activity'] = "showteam";
                        break;
                case "showthread":
                        if(is_numeric($parameters['pid']) && $parameters['action'] == "showpost")
                        {
                                $pid_list[] = $parameters['pid'];
                                $user_activity['activity'] = "showpost";
                                $user_activity['pid'] = $parameters['pid'];
                        }
                        else
                        {
                                if($parameters['page'])
                                {
                                        $user_activity['page'] = $parameters['page'];
                                }
                                if(is_numeric($parameters['tid']))
                                {
                                        $tid_list[] = $parameters['tid'];
                                }
                                $user_activity['activity'] = "showthread";
                                $user_activity['tid'] = $parameters['tid'];
                        }
                        break;
                case "stats":
                        $user_activity['activity'] = "stats";
                        break;
                case "usercp":
                        if($parameters['action'] == "avatar" || $parameters['action'] == "do_avatar")
                        {
                                $user_activity['activity'] = "usercp_avatar";
                        }
                        elseif($parameters['action'] == "attachments" || $parameters['action'] == "do_attachments")
                        {
                                $user_activity['activity'] = "usercp_attachments";
                        }
                        elseif($parameters['action'] == "changename" || $parameters['action'] == "do_changename")
                        {
                                $user_activity['activity'] = "usercp_changename";
                        }
                        elseif($parameters['action'] == "drafts" || $parameters['action'] == "do_drafts")
                        {
                                $user_activity['activity'] = "usercp_drafts";
                        }
                        elseif($parameters['action'] == "editlists" || $parameters['action'] == "do_editlists")
                        {
                                $user_activity['activity'] = "usercp_editlists";
                        }
                        elseif($parameters['action'] == "editsig" || $parameters['action'] == "do_editsig")
                        {
                                $user_activity['activity'] = "usercp_editsig";
                        }
                        elseif($parameters['action'] == "email" || $parameters['action'] == "do_email")
                        {
                                $user_activity['activity'] = "usercp_email";
                        }
                        elseif($parameters['action'] == "forumsubscriptions")
                        {
                                $user_activity['activity'] = "usercp_forumsubscriptions";
                        }
                        elseif($parameters['action'] == "notepad" || $parameters['action'] == "do_notepad")
                        {
                                $user_activity['activity'] = "usercp_notepad";
                        }
                        elseif($parameters['action'] == "options" || $parameters['action'] == "do_options")
                        {
                                $user_activity['activity'] = "usercp_options";
                        }
                        elseif($parameters['action'] == "password" || $parameters['action'] == "do_password")
                        {
                                $user_activity['activity'] = "usercp_password";
                        } 
                        elseif($parameters['action'] == "profile" || $parameters['action'] == "do_profile")
                        {
                                $user_activity['activity'] = "usercp_profile";
                        }
                        elseif($parameters['action'] == "profilepic" || $parameters['action'] == "do_profielpic")
                        {
                                $user_activity['activity'] = "usercp_profilepic";
                        }
                        elseif($parameters['action'] == "subscriptions")
                        {
                                $user_activity['activity'] = "usercp_subscriptions";
                        }
                        elseif($parameters['action'] == "usergroups")
                        {
                                $user_activity['activity'] = "usercp_usergroups";
                        }
                        else
                        {
                                $user_activity['activity'] = "usercp";
                        }
                        break;
                case "usercp2":
                        if($parameters['action'] == "addfavorite" || $parameters['action'] == "removefavorite" || $parameters['action'] == "removefavorites")
                        {
                                $user_activity['activity'] = "usercp2_favorites";
                        }
                        elseif($parameters['action'] == "addsubscription" || $parameters['action'] == "removesubscription" || $parameters['action'] == "removesubscription")
                        {
                                $user_activity['activity'] = "usercp2_subscriptions";
                        }
                        break;
                case "portal":
                        $user_activity['activity'] = "portal";
                        break;
                case "visitormessage":
                        if($parameters['action'] == "delete")
                        {
                                $user_activity['activity'] = "visitormessage_delete";
                                if(is_numeric($parameters['uid']))
                                {
                                        $uid_list[] = $parameters['uid'];
                                }
                                $user_activity['uid'] = $parameters['uid'];
                        }
                        elseif($parameters['action'] == "edit")
                        {
                                $user_activity['activity'] = "visitormessage_edit";
                                if(is_numeric($parameters['uid']))
                                {
                                        $uid_list[] = $parameters['uid'];
                                }
                                $user_activity['uid'] = $parameters['uid'];
                        }
                        elseif($parameters['action'] == "message" || $parameters['action'] == "do_message")
                        {
                                $user_activity['activity'] = "visitormessage_message";
                                if(is_numeric($parameters['uid']))
                                {
                                        $uid_list[] = $parameters['uid'];
                                }
                                $user_activity['uid'] = $parameters['uid'];
                        }
                        elseif($parameters['action'] == "approve"  || $parameters['action'] == "unapprove" || $parameters['action'] == "report" || $parameters['action'] == "dimissreport")
                        {
                                $user_activity['activity'] = "visitormessage_moderate";
                        }
                  break;
                case "warnings":
                        if($parameters['action'] == "warn" || $parameters['action'] == "do_warn")
                        {
                                $user_activity['activity'] = "warnings_warn";
                        }
                        elseif($parameters['action'] == "do_revoke")
                        {
                                $user_activity['activity'] = "warnings_revoke";
                        }
                        elseif($parameters['action'] == "view")
                        {
                                $user_activity['activity'] == "warnings_view";
                        }
                        else
                        {
                                $user_activity['activity'] = "warnings";
                        }
                        break;
                case "nopermission":
                        $user_activity['activity'] = "nopermission";
                        break;
                default:
                        $user_activity['activity'] = "unknown";
                        break;
        }

        $user_activity['location'] = htmlspecialchars_uni($location);

        $plugins->run_hooks_by_ref("fetch_wol_activity_end", $user_activity);

        return $user_activity;
}

/**
 * Builds a friendly named Who's Online location from an "activity" and array of user data. Assumes fetch_wol_activity has already been called.
 *
 * @param array Array containing activity and essential IDs.
 * @return string Location name for the activity being performed.
 */
function build_friendly_wol_location($user_activity)
{
        global $db, $lang, $uid_list, $aid_list, $pid_list, $tid_list, $pmid_list, $fid_list, $eid_list, $plugins, $parser, $daddyobb, $session;
        global $pms, $threads, $forums, $forums_linkto, $posts, $events, $usernames, $attachments, $br;


        //Trying something out...
        $session_qry = $db->simple_select("sessions", "*", "uid = '".$daddyobb->user['uid']."'");
        $session_user = $db->fetch_array($session_qry);
        $online_loci = explode("/", $session_user['location']);
        if(in_array("online.php", $online_loci) || in_array("online.php?", $online_loci))//$session_user['location'] == "/online.php" || $session_user['location'] == "/online.php?")
        {
                $br = "<br>";
        }
        else
        {
                $br = "";
        }

        // Fetch forum permissions for this user
        $unviewableforums = get_unviewable_forums();
        if($unviewableforums)
        {
                $fidnot = " AND fid NOT IN ($unviewableforums)";
        }

        // Fetch any users
        if(!is_array($usernames) && count($uid_list) > 0)
        {
                $uid_sql = implode(",", $uid_list);
                $query = $db->simple_select("users", "uid,username", "uid IN ($uid_sql)");
                while($user = $db->fetch_array($query))
                {
                        $usernames[$user['uid']] = $user['username'];
                }
        }

        // Fetch any attachments
        if(!is_array($attachments) && count($aid_list) > 0)
        {
                $aid_sql = implode(",", $aid_list);
                $query = $db->simple_select("attachments", "aid,pid", "aid IN ($aid_sql)");
                while($attachment = $db->fetch_array($query))
                {
                        $attachments[$attachment['aid']] = $attachment['pid'];
                        $pid_list[] = $attachment['pid'];
                }
        }

        // Fetch any posts
        if(!is_array($posts) && count($pid_list) > 0)
        {
                $pid_sql = implode(",", $pid_list);
                $query = $db->simple_select("posts", "pid,tid", "pid IN ($pid_sql) $fidnot");
                while($post = $db->fetch_array($query))
                {
                        $posts[$post['pid']] = $post['tid'];
                        $tid_list[] = $post['tid'];
                }
        }

        //Fetch pms by pmid
        if(!is_array($pms) && count($pmid_list) > 0)
        {
                $pmid_sql = implode(",", $pmid_list);
                $query = $db->simple_select("privatemessages", "*", "pmid IN ($pmid_sql)");
                while($pm = $db->fecth_array($query))
                {
                        $pms[$pm['pmid']] = $pm['fromid'];
                        $pm['fromid'] = $usernames[$pm['fromid']];
                        $uid_list[] = $pm['fromid'];
                        $pmid_list[] = $pm['pmid'];
                }
         }

        // Fetch any threads
        if(!is_array($threads) && count($tid_list) > 0)
        {
                $tid_sql = implode(",", $tid_list);
                $query = $db->simple_select("threads", "fid,tid,subject,visible", "tid IN($tid_sql) $fidnot $visible");
                while($thread = $db->fetch_array($query))
                {
                        if(is_moderator($thread['fid']) || $thread['visible'] != '0')
                        {
                                $threads[$thread['tid']] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));
                                $fid_list[] = $thread['fid'];
                        }
                }
        }

        // Fetch any forums
        if(!is_array($forums) && count($fid_list) > 0)
        {
                $fid_sql = implode(",", $fid_list);
                $query = $db->simple_select("forums", "fid,name,linkto", "fid IN ($fid_sql) $fidnot");
                while($forum = $db->fetch_array($query))
                {
                        $forums[$forum['fid']] = $forum['name'];
                        $forums_linkto[$forum['fid']] = $forum['linkto'];
                }
        }

        // And finaly any events
        if(!is_array($events) && count($eid_list) > 0)
        {
                $eid_sql = implode(",", $eid_list);
                $query = $db->simple_select("events", "eid,name", "eid IN ($eid_sql)");
                while($event = $db->fetch_array($query))
                {
                        $events[$event['eid']] = htmlspecialchars_uni($parser->parse_badwords($event['name']));
                }
        }

        // Now we've got everything we need we can put a name to the location
        switch($user_activity['activity'])
        {
                // announcement.php functions
                case "announcements":
                        if($forums[$user_activity['fid']])
                        {
                                $location_name = $lang->sprintf($lang->viewing_announcement_2, get_forum_link($user_activity['fid']), $forums[$user_activity['fid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->viewing_announcement;
                        }
                        break;
                // attachment.php actions
                case "attachment":
                        $pid = $attachments[$user_activity['aid']];
                        $tid = $posts[$pid];
                        if($threads[$tid])
                        {
                                $location_name = $lang->sprintf($lang->viewing_attachment_2, $user_activity['aid'], $threads[$tid], get_thread_link($tid), $br);
                        }
                        else
                        {
                                $location_name = $lang->viewing_attachment;
                        }
                        break;
                // calendar.php functions
                case "calendar":
                        $location_name = $lang->viewing_calendar;
                        break;
                case "calendar_event":
                        if($events[$user_activity['eid']])
                        {
                                $location_name = $lang->sprintf($lang->viewing_event_2, get_event_link($user_activity['eid']), $events[$user_activity['eid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->viewing_event;
                        }
                        break;
                case "calendar_addevent":
                        $location_name = $lang->creating_event;
                        break;
                case "calendar_editevent":
                        $location_name = $lang->modifying_event;
                        break;
                // editpost.php functions
                case "editpost":
                        if($posts[$user_activity['pid']])
                        {
                                $location_name = $lang->sprintf($lang->modifying_post_2, get_thread_link($posts[$user_activity['pid']]), $threads[$posts[$user_activity['pid']]], $br);
                        }
                        else
                        {
                                $location_name = $lang->modifying_post;
                        }
                        break;
                // forumdisplay.php functions
                case "forumdisplay":
                        if($forums[$user_activity['fid']])
                        {
                                if($forums_linkto[$user_activity['fid']])
                                {
                                        $location_name = $lang->sprintf($lang->being_redirected_to, get_forum_link($user_activity['fid']), $forums[$user_activity['fid']], $br);
                                }
                                else
                                {
                                        $location_name = $lang->sprintf($lang->viewing_forum_2, get_forum_link($user_activity['fid']), $forums[$user_activity['fid']], $br);
                                }
                        }
                        else
                        {
                                $location_name = $lang->viewing_forum;
                        }
                        break;
                // index.php functions
                case "index":
                        $location_name = $lang->sprintf($lang->viewing_index, $daddyobb->settings['bbname'], $br);
                        break;
                // managegroup.php functions
                case "managegroup":
                        $location_name = $lang->managing_group;
                        break;
                // member.php functions
                case "member_activate":
                        $location_name = $lang->activating_registration;
                        break;
                case "member_profile":
                        if($usernames[$user_activity['uid']])
                        {
                                $user = get_user($user_activity['uid']);
                                $usernames[$user_activity['uid']] = format_name($user['username'], $user['usergroup'], $usernames['displaygroup']);
                                $location_name = $lang->sprintf($lang->viewing_user_profile_2, get_profile_link($user_activity['uid']), $usernames[$user_activity['uid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->viewing_user_profile;
                        }
                        break;
                case "member_register":
                        $location_name = $lang->registering;
                        break;
                case "member":
                case "member_login":
                        // Guest or member?
                        if($daddyobb->user['uid'] == 0)
                        {
                                $location_name = $lang->logging_in;
                        }
                        else
                        {
                                $location_name = $lang->logging_in_plain;
                        }
                        break;
                case "member_logout":
                        $location_name = $lang->logging_out;
                        break;
                case "member_emailuser":
                        $location_name = $lang->emailing;
                        break;
                case "member_rate":
                        $location_name = $lang->giving_reputation;
                        break;
                case "member_resendactivation":
                        $location_name = $lang->request_activation_code;
                        break;
                case "member_lostpw":
                        $location_name = $lang->retrieving_lost_password;
                        break;
                // memberlist.php functions
                case "memberlist":
                        $location_name = $lang->viewing_member_list;
                        break;
                case "memberlist_search":
                        $location_name = $lang->searching_member_list;
                        break;
                // misc.php functions
                case "misc_dstswitch":
                        $location_name = $lang->modifying_dst;
                        break;
                case "misc_whoposted":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->viewing_whoposted2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']]);
                        }
                        else
                        {
                                $location_name = $lang->viewing_whoposted;
                        }
                        break;
                case "misc_markread":
                        $location_name = $lang->marking_read;
                        break;
                case "misc_help":
                        $location_name = $lang->viewing_faq;
                        break;
                case "misc_buddypopup":
                        $location_name = $lang->viewing_buddylist;
                        break;
                case "misc_smilies":
                        $location_name = $lang->viewing_smilies;
                        break;
                case "misc_syndication":
                        $location_name = $lang->viewing_syndication;
                        break;
                case "misc_imcenter":
                        $location_name = $lang->viewing_im_center;
                        break;
                // modcp.php functions
                case "modcp_modlogs":
                        $location_name = $lang->viewing_moderator_logs;
                        break;
                case "modcp_announcements":
                        $location_name = $lang->managing_announcements;
                        break;
                case "modcp_finduser":
                        $location_name = $lang->searching_users;
                        break;
                case "modcp_warninglogs":
                        $location_name = $lang->managing_warninglogs;
                        break;
                case "modcp_ipsearch":
                        $location_name = $lang->searching_ips;
                        break;
                case "modcp_report":
                        $location_name = $lang->viewing_reported_posts;
                        break;
                case "modcp_new_announcement":
                        $location_name = $lang->creating_announcement;
                        break;
                case "modcp_delete_announcement":
                        $location_name = $lang->deleting_announcement;
                        break;
                case "modcp_edit_announcement":
                        $location_name = $lang->modifying_announcement;
                        break;
                case "modcp_mod_queue":
                        $location_name = $lang->managing_modqueue;
                        break;
                case "modcp_editprofile":
                        $location_name = $lang->modifying_user_profiles;
                        break;
                case "modcp_banning":
                        $location_name = $lang->managing_bans;
                        break;
                case "modcp":
                        $location_name = $lang->viewing_moderator_control_panel;
                        break;
                // moderation.php functions
                case "moderation":
                        $location_name = $lang->moderating;
                        break;
                case "moderation_movethread":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->moving_thread_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->moving_thread;
                        }
                        break;
                case "moderation_openclosethread":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->open_close_thread_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->open_close_thread;
                        }
                        break;
                case "moderation_stickunstick":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->stick_unstick_thread_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->stick_unstick_thread;
                        }
                        break;
                case "moderation_merge":
                        $location_name = $lang->merging_threads;
                        break;
                case "moderation_threadnotes":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->modifying_thread_notes_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->modifying_thread_notes;
                        }
                        break;
                // newreply.php functions
                case "newreply":
                        if($user_activity['pid'])
                        {
                                $user_activity['tid'] = $posts[$user_activity['pid']];
                        }
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->replying_to_thread_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->replying_to_thread;
                        }
                        break;
                // newthread.php functions
                case "newthread":
                        if($forums[$user_activity['fid']])
                        {
                                $location_name = $lang->sprintf($lang->creating_thread_2, get_forum_link($user_activity['fid']), $forums[$user_activity['fid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->creating_thread;
                        }
                        break;
                // online.php functions
                case "wol":
                        $location_name = $lang->viewing_whos_online;
                        break;
                case "woltoday":
                        $location_name = $lang->viewing_who_was_online_today;
                        break;
                // polls.php functions
                case "newpoll":
                        $location_name = $lang->creating_poll;
                        break;
                case "editpoll":
                        $location_name = $lang->modifying_poll;
                        break;
                case "showresults":
                        $location_name = $lang->viewing_poll_results;
                        break;
                case "vote":
                        $location_name = $lang->voting_on_poll;
                        break;
                // printthread.php functions
                case "printthread":
                        if($threads[$user_activity['tid']])
                        {
                                $location_name = $lang->sprintf($lang->printing_thread2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->printing_thread;
                        }
                        break;
                // private.php functions
                case "private_send":
                                if($usernames[$user_activity['uid']])
                                {
                                        $user = get_user($user_activity['uid']);
                                        $usernames[$user_activity['uid']] = format_name($user['username'], $user['usergroup'], $usernames['displaygroup']);                                
                                        $location_name = $lang->sprintf($lang->creating_private_message_2, get_profile_link($user_activity['uid']), $usernames[$user_activity['uid']], $br);
                                }
                                else
                                {
                                        $location_name = $lang->creating_private_message;
                                }
                        break;
                case "private_read":
                        if($user_activity['pmid'])
                        {
                                $location_name = $lang->sprintf($lang->viewing_private_message);
                        }
                        else
                        {
                                $location_name = $lang->viewing_private_message;
                        }
                        break;
                case "private_download":
                        $location_name = $lang->downloading_private_messages;
                        break;
                case "private_folders":
                        $location_name = $lang->modifying_pmfolders;
                        break;
                case "private":
                        $location_name = $lang->private_messaging;
                        break;
                /* Ratethread functions */
                case "ratethread":
                        $location_name = $lang->rating_thread;
                        break;
                // report.php functions
                case "report":
                        $location_name = $lang->reporting_post;
                        break;
                // reputation.php functions
                case "reputation":
                        $location_name = $lang->giving_reputation;
                        break;
                // search.php functions
                case "search":
                        $location_name = $lang->searching_forums;
                        break;
                // showthread.php functions
                case "showthread":
                        if($threads[$user_activity['tid']])
                        {
                                $pagenote = '';
                                $location_name = $lang->sprintf($lang->viewing_thread_2, get_thread_link($user_activity['tid']), $threads[$user_activity['tid']], $pagenote, $br);
                        }
                        else
                        {
                                $location_name = $lang->viewing_thread;
                        }
                        break;
                // showteam.php functions
                case "showteam":
                        $location_name = $lang->viewing_forum_leaders;
                        break;
                 // sendthread.php functions
                 case "sendthread":
                          $location_name = $lang->sending_thread_to_friend;
                          break;
                // stats.php functions
                case "stats":
                        $location_name = $lang->viewing_stats;
                        break;
                // usercp.php functions
                case "usercp":
                        $location_name = $lang->viewing_user_control_panel;
                        break;
                case "usercp_avatar":
                        $location_name = $lang->modifying_avatar;
                        break;
                case "usercp_attachments":
                        $location_name = $lang->managing_attachments;
                        break;
                case "usercp_changename":
                        $location_name = $lang->modifying_username;
                        break;
                case "usercp_drafts":
                        $location_name = $lang->viewing_drafts;
                        break;
                case "usercp_editlists":
                        $location_name = $lang->modifying_contact_ignore_list;
                        break;
                case "usercp_editsig":
                        $location_name = $lang->modifying_signature;
                        break;
                case "usercp_email":
                        $location_name = $lang->modifying_email;
                        break;
                case "usercp_forumsubscriptions":
                        $location_name = $lang->viewing_subscribed_forums;
                        break;
                case "usercp_notepad":
                        $location_name = $lang->modifying_personal_pad;
                        break;
                case "usercp_options":
                        $location_name = $lang->modifying_options;
                        break;
                case "usercp_password":
                        $location_name = $lang->modifying_password;
                        break;
                case "usercp_profile":
                        $location_name = $lang->modifying_profile;
                        break;
                case "usercp_profilepic":
                        $location_name = $lang->modifying_profilepic;
                        break;
                case "usercp_subscriptions":
                        $location_name = $lang->viewing_subscribed_threads;
                        break;
                case "usercp_usergroups":
                        $location_name = $lang->modifying_usergroups;
                        break;
                case "usercp2_favorites":
                        $location_name = $lang->managing_favorites;
                        break;
                case "usercp2_subscriptions":
                        $location_name = $lang->managing_subscriptions;
                        break;
                case "portal":
                        $location_name = $lang->viewing_portal;
                        break;
                // sendthread.php functions
                case "sendthread":
                        $location_name = $lang->sending_thread;
                        break;
                //visitormessage.php functions
                case "visitormessage_delete":
                        if($user_activity['uid'])
                        {       
                                $user = get_user($user_activity['uid']);
                                $usernames[$user_activity['uid']] = format_name($user['username'], $user['usergroup'], $usernames['displaygroup']);
                                $location_name = $lang->sprintf($lang->deleting_visitor_message_2, get_profile_link($user_activity['uid']), $usernames[$user_activity['uid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->deleting_visitor_message;
                        }
                        break;
                case "visitormessage_edit":
                        if($user_activity['uid'])
                        {       
                                $user = get_user($user_activity['uid']);
                                $usernames[$user_activity['uid']] = format_name($user['username'], $user['usergroup'], $usernames['displaygroup']);
                                $location_name = $lang->sprintf($lang->modifying_visitor_message_2, get_profile_link($user_activity['uid']), $usernames[$user_activity['uid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->modifying_visitor_message;
                        }
                        break;
                case "visitormessage_message":
                        if($user_activity['uid'])
                        {       
                                $user = get_user($user_activity['uid']);
                                $usernames[$user_activity['uid']] = format_name($user['username'], $user['usergroup'], $usernames['displaygroup']);
                                $location_name = $lang->sprintf($lang->posting_visitor_message_2, get_profile_link($user_activity['uid']), $usernames[$user_activity['uid']], $br);
                        }
                        else
                        {
                                $location_name = $lang->posting_visitor_message;
                        }
                        break;
                case "visitormessage_moderate":
                        $location_name = $lang->moderating_vms;
                        break;
                // warnings.php functions
                case "warnings_revoke":
                        $location_name = $lang->revoking_warning;
                        break;
                case "warnings_warn":
                        $location_name = $lang->warning_user;
                        break;
                case "warnings_view":
                        $location_name = $lang->viewing_warning;
                        break;
                case "warnings":
                        $location_name = $lang->managing_warnings;
                        break;
        }

        $plugin_array = array('user_activity' => &$user_activity, 'location_name' => &$location_name);
        $plugins->run_hooks_by_ref("build_friendly_wol_location_end", $plugin_array);

        if($user_activity['nopermission'] == 1)
        {
          $location_name = $lang->viewing_no_permission_message;
        }
        
        if($user_activity['error'] == 1)
        {
          $location_name = $lang->viewing_error_message;
        }

        if(!$location_name)
        {
          $location_name = $lang->sprintf($lang->unknown_location, $user_activity['location']);
        }

        return $location_name;
}

/**
 * Build a Who's Online row for a specific user
 *
 * @param array Array of user information including activity information
 * @return string Formatted online row
 */
function build_wol_row($user)
{
  global $daddyobb, $lang, $templates, $themes, $session, $iphead, $buddymark;

  // We have a registered user
  if($user['uid'] > 0)
  {
    if($daddyobb->user['uid'] > 0)
    {
      $daddyobb_buddies = explode(",", $daddyobb->user['buddylist']);
      if(in_array($user['uid'], $daddyobb_buddies))
      {
        $buddymark = 1;
      }
      else
      {
        $buddymark = 0;
      }          
    }        
    // Only those with "canviewwolinvis" permissions can view invisible users
    if($user['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $daddyobb->user['uid'])
    {
      // Append an invisible mark if the user is invisible
      if($user['invisible'] == 1)
      {
        $invisiblemark = 1;
      }
      else
      {
        $invisiblemark = 0;
      }

      $user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
      $online_name = build_profile_link($user['username'], $user['uid']);
    }
  }
  // We have a bot
  elseif($user['bot'])
  {
    $online_name = format_name($user['bot'], $user['usergroup']);
  }
  // Otherwise we've got a plain old guest
  else
  {
    $online_name = format_name($lang->guest, 1);
  }

  $online_time = my_date($daddyobb->settings['timeformat'], $user['time']);

  // Fetch the location name for this users activity
  $location = build_friendly_wol_location($user['activity']);

  // Can view IPs, then fetch the IP template
  if($daddyobb->usergroup['canviewonlineips'] == 1)
  {
    eval("\$iphead = \"".$templates->get("online_ip")."\";");
    $adminlocation = "<span style=\"float: right;\"><img src=\"".$daddyobb->settings['bburl']."/images/icons/question.gif\" alt=\"".$user['location']."\" title=\"".$user['location']."\"></span>";
    eval("\$user_ip = \"".$templates->get("online_row_ip")."\";");
  }
  else
  {
    $user['ip'] = '';
  }
  
  // And finally if we have permission to view this user, return the completed online row
  if($user['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $daddyobb->user['uid'])
  {
    eval("\$online_row = \"".$templates->get("online_row")."\";");
  }
  
  return $online_row;
}
?>