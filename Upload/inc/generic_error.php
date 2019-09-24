<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:09 19.12.2008
  */


// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>
      DaddyoBB Error
    </title>
  </head>
  <body>
    <h1>
      DaddyoBB Error
    </h1>
    <pre>

<strong>DaddyoBB has generated a critical error and as a result cannot function correctly.</strong><br />
 DaddyoBB Said:<br />
<br />
  Error Code: Error Code: <?php echo $code; ?><br />
  <?php echo $message; ?><br />
<br />
 Please try clicking the <a href="javascript:window.location=window.location;">Refresh</a> button in your web browser to see if this corrects this problem.<br />

We apologise for any inconvenience.<br />
</pre>
    <hr />
    <address>
     DaddyoBB <?php echo $this->version; ?>
    </address>
  </body>
</html>