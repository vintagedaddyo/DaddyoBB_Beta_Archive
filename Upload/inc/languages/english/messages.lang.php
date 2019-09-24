<?php
/**
 * DaddyoBB 1.0 Beta - English Language Pack
 * Copyright © 2008  DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:44 19.12.2008
 */

$l['emailsubject_lostpw'] = "Password Reset at {1}";
$l['emailsubject_passwordreset'] = "New password at {1}";
$l['emailsubject_subscription'] = "New Reply to {1}";
$l['emailsubject_randompassword'] = "Your Password for {1}";
$l['emailsubject_activateaccount'] = "Account Activation at {1}";
$l['emailsubject_forumsubscription'] = "New Thread in {1}";
$l['emailsubject_reportpost'] = "Reported post at {1}";
$l['emailsubject_reachedpmquota'] = "Private Messaging Quota Reached at {1}";
$l['emailsubject_changeemail'] = "Change of Email at {1}";
$l['emailsubject_newpm'] = "New Private Message at {1}";
$l['emailsubject_sendtofriend'] = "Interesting Web Page at {1}";
$l['emailbit_viewthread'] = "... (visit the thread to read more..)";

$l['email_lostpw'] = "{1},

To complete the phase of resetting your account password at {2}, you will need to go to the URL below in your web browser.

{3}/member.php?action=resetpassword&uid={4}&code={5}

If the above link does not work correctly, go to

{3}/member.php?action=resetpassword

You will need to enter the following:
Username: {1}
Activation Code: {5}

Thank you,
{2} Staff";


$l['email_reportpost'] = "{1} from {2} has reported this post:

{3}
{4}/{5}

The reason this user gave for reporting this post:
{7}

This message has been sent to all moderators of this forum, or all administrators and super moderators if there are no moderators.

Please check this post out as soon as possible.";

$l['email_passwordreset'] = "{1},

Your password at {2} has been reset.

Your new password is: {3}

You will need this password to login to the forums, once you login you should change it by going to your User Control Panel.

Thank you,
{2} Staff";

$l['email_randompassword'] = "{1},

Thank you for registering on {2}. Below is your username and the randomly generated password. To login to {2}, you will need these details.

Username: {3}
Password: {4}

It is recommended you change your password immediately after you login. You can do this by going to your User CP then clicking Change Password on the left menu.

Thank you,
{2} Staff";

$l['email_sendtofriend'] = "Hello,

{1} from {2} thought you may be interested in reading the following web page:

{3}

{1} included the following message:
------------------------------------------
{4}
------------------------------------------

Thank you,
{2} Staff
";

$l['email_forumsubscription'] = "{1},

{2} has just started a new thread in {3}. This is a forum you have subscribed to at {4}.

The thread is titled {5}

Here is an excerpt of the message:
--
{6}
--

To view the thread, you can go to the following URL:
{7}/{8}

There may also be other new threads and replies but you will not receive anymore notifications until you visit the board again.

Thank you,
{4} Staff

------------------------------------------
Unsubscription Information:

If you would not like to receive any more notifications of new threads in this forum, visit the following URL in your browser:
{7}/usercp2.php?action=removesubscription&type=forum&fid={9}

------------------------------------------";

$l['email_activateaccount'] = "{1},

To complete the registration process on {2}, you will need to go to the URL below in your web browser.

{3}/member.php?action=activate&uid={4}&code={5}

If the above link does not work correctly, go to

{3}/member.php?action=activate

You will need to enter the following:
Username: {1}
Activation Code: {5}

Thank you,
{2} Staff";

$l['email_subscription'] = "{1},

{2} has just replied to a thread which you have subscribed to at {3}. This thread is titled {4}.

Here is an excerpt of the message:
------------------------------------------
{5}
------------------------------------------

To view the thread, you can go to the following URL:
{6}/{7}

There may also be other replies to this thread but you will not receive anymore notifications until you visit the board again.

Thank you,
{3} Staff

------------------------------------------
Unsubscription Information:

If you would not like to receive any more notifications of replies to this thread, visit the following URL in your browser:
{6}/usercp2.php?action=removesubscription&tid={8}&key={9}

------------------------------------------";
$l['email_reachedpmquota'] = "{1},

This is an automated email from {2} to let you know that your Private Messaging inbox has reached its capacity.

One or more users may have tried to send you private messages and were unsuccessful in doing so because of this.

Please delete some of your private messages you currently have stored, remembering to also delete them from the 'Trash Can'.

Thank you,
{2} Staff
{3}";
$l['email_changeemail'] = "{1},

We have received a request on {2} to change your email address (see details below).

Old Email Address: {3}
New Email Address: {4}

If these changes are correct, please complete the validation process on {2} by going to the following URL in your web browser.

{5}/member.php?action=activate&uid={8}&code={6}

If the above link does not work correctly, go to

{5}/member.php?action=activate

You will need to enter the following:
Username: {7}
Activation Code: {6}

If you choose not to validate your new email address your profile will not be updated and will still contain your existing email address.

Thank you,
{2} Staff
{5}";

$l['email_newpm'] = "{1},
		
You have received a new private message on {3} from {2}. To view this message, you can follow this link:

{4}/private.php

Please note that you will not receive any further notifications of new messages until you visit {3}.

You can disable new message notifications on your account options page:

{4}/usercp.php?action=options

Thank you,
{3} Staff
{4}";

$l['email_emailuser'] = "{1},

{2} from {3} has sent you the following message:
------------------------------------------
{5}
------------------------------------------

Thank you,
{3} Staff
{4}

------------------------------------------
Don't want to receive email messages from other members?

If you don't want other members to be able to email you please visit your User Control Panel and enable the option 'Hide your email from other members':
{4}/usercp.php?action=options

------------------------------------------";
?>