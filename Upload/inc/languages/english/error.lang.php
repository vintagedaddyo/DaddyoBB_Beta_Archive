<?php
/**
 * DaddyoBB 1.0 Beta - English Language Pack
 * Copyright © 2008  DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 15:12 23.12.2008
 */
   
### User Based Errors Errors ###
$l['error_invalidemail'] = "You did not enter a valid email address.";
$l['error_nomember'] = "The member you specified is either invalid or doesn't exist.";
$l['error_nohostname'] = "No hostname could be found for the IP you entered.";
$l['error_nopassword'] = "You did not enter a valid password.";
$l['error_usernametaken'] = "The username you have chosen is already registered.";
$l['error_nousername'] = "You did not enter a username.";
$l['error_invalidusername'] = "The username you have entered appears to be invalid.";
$l['error_invalidpassword'] = "The password you entered is incorrect. If you have forgotten your password, click <a href=\"member.php?action=lostpw\">here</a>. Otherwise, go back and try again.";
$l['error_nopermission_guest_1'] = "You are either not logged in or do not have permission to view this page. This could be because one of the following reasons:";
$l['error_nopermission_guest_2'] = "You are not logged in or registered. Please use the form at the bottom of this page to login.";
$l['error_nopermission_guest_3'] = "You do not have permission to access this page. Are you trying to access administrative pages or a resource that you shouldn't be?  Check in the forum rules that you are allowed to perform this action.";
$l['error_nopermission_guest_4'] = "Your account may have been disabled by an administrator, or it may be awaiting account activation.";
$l['error_nopermission_user_1'] = "You do not have permission to access this page. This could be because of one of the following reasons:";
$l['error_nopermission_user_ajax'] = "You do not have permission to access this page.";
$l['error_nopermission_user_2'] = "Your account has either been suspended or you have been banned from accessing this resource.";
$l['error_nopermission_user_3'] = "You do not have permission to access this page. Are you trying to access administrative pages or a resource that you shouldn't be? Check in the forum rules that you are allowed to perform this action.";
$l['error_nopermission_user_4'] = "Your account may still be awaiting activation or moderation.";
$l['error_nopermission_user_resendactivation'] = "Resend Activation Code";
$l['error_nopermission_user_5'] = "You are currently logged in with the username: '{1}'";
$l['please_correct_errors'] = "Please correct the following errors before continuing:";
$l['error_invaliduser'] = "The specified user is invalid or does not exist.";
$l['error_invalidaction'] = "Invalid action";
$l['failed_login_wait'] = "You have failed to login within the required number of attempts. You must now wait {1}h {2}m {3}s before you can login again.";
$l['failed_login_again'] = "<br />You have <strong>{1}</strong> more login attempts.";

### Posts, Threads, Messages ###
$l['error_invalidthread'] = "The specified thread does not exist.";
$l['error_invalidpost'] = "The specified post does not exist.";
$l['error_invalidattachment'] = "The specified attachment does not exist.";
$l['error_invalidforum'] = "Invalid forum";
$l['error_incompletefields'] = "It appears you have left one or more required fields blank. Please go back and enter the required fields."; 
$l['error_alreadyuploaded'] = "This post already contains an attachment with the same name. Please rename the file and upload it again.";
$l['error_nomessage'] = "Sorry, we cannot proceed because you did not enter a valid message. Please go back and do so.";
$l['error_maxposts'] = "I'm sorry, but your daily post limit has been exceeded.  Please wait till tomorrow to post further or contact your administrator.<br /><br />The maximum amount of posts you may make in a day is {1}";
$l['error_closedinvalidforum'] = "You may not post in this forum either because the forum is closed, or it is a category.";
$l['error_attachtype'] = "The type of file that you attached is not allowed. Please remove the attachment or choose a different type.";
$l['error_attachsize'] = "The file you attached is too large. The maximum size for that type of file is {1} kilobytes.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_uploadfailed_detail'] = "Error details: ";
$l['error_uploadfailed_php1'] = "PHP returned: Uploaded file exceeded upload_max_filesize directive in php.ini.  Please contact your forum administrator with this error.";
$l['error_uploadfailed_php2'] = "The uploaded file exceeded the maximum file size specified.";
$l['error_uploadfailed_php3'] = "The uploaded file was only partially uploaded.";
$l['error_uploadfailed_php4'] = "No file was uploaded.";
$l['error_uploadfailed_php6'] = "PHP returned: Missing a temporary folder.  Please contact your forum administrator with this error.";
$l['error_uploadfailed_php7'] = "PHP returned: Failed to write the file to disk.  Please contact your forum administrator with this error.";
$l['error_uploadfailed_phpx'] = "PHP returned error code: {1}.  Please contact your forum administrator with this error.";
$l['error_uploadfailed_nothingtomove'] = "An invalid file was specified, so the uploaded file could not be moved to its destination.";
$l['error_uploadfailed_movefailed'] = "There was a problem moving the uploaded file to its destination.";
$l['error_uploadfailed_lost'] = "The attachment could not be found on the server.";
$l['error_emailmismatch'] = "The email addresses you entered do not match. Please go back and try again";
$l['error_postflooding'] = "We are sorry but we cannot process your post. The administrator has specified you are only allowed to post once every {1} seconds.";
$l['error_too_many_images'] = "Too Many Images.";
$l['error_too_many_images2'] = "We are sorry, but we cannot process your post because it contains too many images. Please remove some images from your post to continue.";
$l['error_too_many_images3'] = "<b>Note:</b> The maximum amount of images per post is";
$l['error_attach_file'] = "Error Attaching File";
$l['error_reachedattachquota'] = "Sorry but you cannot attach this file because you have reached your attachment quota of {1}";
$l['error_messagelength'] = "Sorry, your message is too long and cannot be posted. Please try shortening your message and try again.";
$l['error_message_too_short'] = "Sorry, your message is too short and cannot be posted.";
$l['error_max_emails_day'] = "You cannot use the 'Send Thread to a Friend' or the 'Email User' features because you've already used up your allocated quota of sending {1} messages in the past 24 hours.";
$l['error_notallowedtohavevms'] = "{1} is not able to receive visitor messages";
$l['error_vmsdisabled'] = "{1} has disabled visitor messages";
$l['error_polloptiontoolong'] = "One or more poll options you entered are longer than the acceptable limit. Please go back and shorten them.";
$l['error_noquestionoptions'] = "You either did not enter a question for your poll or do not have enough options. The minimum number of options a poll can have is 2.<br />Please go back and correct this error.";
$l['error_pollalready'] = "Thread already has poll!";
$l['error_nopolloptions'] = "The specified poll option is invalid or does not exist.";
$l['error_alreadyvoted'] = "You have already voted in this poll.";
$l['error_invalidpoll'] = "The specified poll is invalid or does not exist.";
$l['error_pollclosed'] = "You cannot vote in a poll that has been closed.";
$l['invalid_captcha'] = "The image verification code that you entered was incorrect. Please enter the code exactly how it appears in the image.";
$l['error_post_already_submitted'] = "You have already posted this thread in this forum. Please visit the forum to see your thread.";
$l['error_nonextnewest'] = "There are no threads that are newer than the one you were previously viewing.";
$l['error_nonextoldest'] = "There are no threads that are older than the one you were previously viewing.";

### User Base Errors ###
# Edit Signature 
$l['sig_remove_chars_plural'] = "Please remove {1} characters and try again.";
$l['sig_remove_chars_singular'] = "Please remove 1 character and try again.";
$l['sig_too_long'] = "You cannot update your signature because it is too long. The maximum length for signatures is {1} characters. ";
$l['too_many_sig_images'] = "We are sorry, but we cannot update your signature because it contains too many images. Please remove some images from your signature to continue.";
$l['too_many_sig_images2'] = "<strong>Note:</strong> The maximum amount of images for signatures is {1}.";

# Registering/Loggin In
$l['error_awaitingcoppa'] = "You cannot login using this account as it is still awaiting COPPA validation from a parent or guardian.<br /><br />A parent or legal guardian will need to download, fill in and submit to us a completed copy of our <a href=\"member.php?action=coppa_form\">COPPA Compliance &amp; Permission form</a>.<br /><br />Once we receive a completed copy of this form, the account will be activated.";
$l['registrations_disabled'] = "Sorry but you cannot register at this time because the administrator has disabled new account registrations.";
$l['error_username_length'] = "Your username is invalid. Usernames have to be within {1} to {2} characters.";
$l['error_activated_by_admin'] = "You cannot resend your account activation email as all registrations must be approved by an Administrator.";
$l['error_alreadyregistered'] = "Sorry, but our system shows that you have already registered on these forums and the registration of multiple accounts has been disabled.";
$l['error_alreadyregisteredtime'] = "We cannot process your registration because there has already been {1} new registration(s) from your ip address in the past {2} hours. Please try again later.";
$l['error_badlostpwcode'] = "You seem to have entered an invalid password reset code. Please re-read the email you were sent or contact the forum administrators for more help.";
$l['error_badactivationcode'] = "You have entered an invalid account activation code. To resend all activation emails to the email address on file, please click <a href=\"member.php?action=resendactivation\">here</a>.";
$l['error_alreadyactivated'] = "It appears your account is already activated or does not require email verification.";
$l['error_nothreadurl'] = "Your message does not contain the URL of the thread. Please use the \"send to friend\" feature for it's intended purpose.";
$l['error_invalidpworusername'] = "You have entered an invalid username or password combination. <br /><br />If you have forgotten your password please <a href=\"member.php?action=lostpw\">retrieve a new one</a>.";
$l['error_bannedusername'] = "You have entered a username that is banned from registration.  Please choose another username.";
$l['error_notloggedout'] = "Your user ID could not be verified to log you out.  This may have been because a malicious Javascript was attempting to log you out automatically.  If you intended to log out, please click the Log Out button at the top menu.";
$l['error_regimageinvalid'] = "The image verification code that you entered was incorrect. Please enter the code exactly how it appears in the image.";

$l['js_validator_no_username'] = "You must enter a username";
$l['js_validator_invalid_email'] = "You need to enter a valid email address";
$l['js_validator_email_match'] = "You need to enter the same email address again";
$l['js_validator_no_image_text'] = "You need to enter the text in the image above";
$l['js_validator_password_matches'] = "The passwords you enter must match";
$l['js_validator_password_complexity'] = "Passwords must contain one or more symbols";
$l['js_validator_password_length'] = "Your password must be {1} or more characters long";
$l['js_validator_not_empty'] = "You must select or enter a value for this field";
$l['js_validator_checking_username'] = "Checking if username is available";
$l['js_validator_username_length'] = "Usernames must be between {1} and {2} characters long";
$l['js_validator_checking_referrer'] = "Checking if referrer username exists.";
$l['js_validator_captcha_valid'] = "Checking whether or not you entered the correct image verification code.";

# Send Mail
$l['error_hideemail'] = "The recipient has chosen to hide their email address and as a result you cannot email them.";
$l['error_no_email_subject'] = "You need to enter a subject for your email";
$l['error_no_email_message'] = "You need to enter a message for your email";

# Private Messaging
$l['pms_disabled'] = "You cannot use the private messaging functionality as it has been disabled by the Administrator.";
$l['error_nopmsarchive'] = "Sorry, but there are no private messages matching the criteria you specified.";
$l['error_invalidpmfoldername'] = "Sorry, but a folder name you have entered contains characters which are not allowed.";
$l['error_emptypmfoldername'] = "Sorry, but a folder name you have entered does not contain any text.  Please enter a name for the folder, or completely blank the name to delete the folder.";
$l['error_invalidpmrecipient'] = "The recipient you entered is either invalid or doesn't exist. Please go back and enter a correct one.";
$l['error_invalidpm'] = "Invalid PM";
$l['error_pmrecipientreachedquota'] = "You cannot send a private message to {1} because he/she has reached their private messaging quota. They cannot be sent any message until their messages have been cleared out. An email has been sent to the user about this. Please try sending your message at a later stage.";
$l['error_recipientpmturnedoff'] = "{1} has chosen not to receive private messages or may not be allowed to do so. Therefore you may not send your private message to this user.";
$l['error_pmsturnedoff'] = "You currently have private messages disabled in your profile.<br />To be able to use the private messaging system this setting must be enabled.";
$l['error_recipientignoring'] = "We are sorry but we cannot process your private message to {1}. You do not have permission to perform this action.";
$l['error_pm_already_submitted'] = "You have already submitted the same private message to the same recipient within the last 5 minutes.";

### Manage Groups ###
$l['error_alreadyingroup'] = "The user specified is already part of the user group.";

### User CP ###
$l['error_noavatar'] = "You did not choose an avatar. Please go back and do so now. If you don't want an avatar, select the \"No avatar\" option.";
$l['error_avatartype'] = "Invalid file type. An uploaded avatar must be in GIF, JPEG, or PNG format.";
$l['error_alreadyingroup'] = "The user specified is already a part of the user group.";
$l['error_usercp_return_date_past'] = "You cannot return in the past!";
$l['error_avatarresizefailed'] = "Your avatar was unable to be resized so that it is within the required dimensions.";
$l['error_avataruserresize'] = "You can also try checking the 'attempt to resize my avatar' check box and uploading the same image again.";

?>