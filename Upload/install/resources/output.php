<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 14:08 20.12.2008
 */

class installerOutput {
	var $doneheader;
	var $openedform;
	var $script = "index.php";
	var $steps = array();
	var $title = "DaddyoBB Installation Wizard";

	function print_header($title="Welcome", $image="welcome", $form=1, $error=0)
	{
		global $daddyobb, $lang;
		
		if($lang->title)
		{
			$this->title = $lang->title;
		}
		
		$this->doneheader = 1;
		if($image == "dbconfig")
		{
			$dbconfig_add = "<script type=\"text/javascript\">document.write('<style type=\"text/css\">.db_type { display: none; }</style>');</script>";
		}
		echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>$this->title &gt; $title</title>
	<link rel="stylesheet" href="stylesheet.css" type="text/css" />
	<script type="text/javascript" src="../jscripts/prototype.js"></script>
	<script type="text/javascript" src="../jscripts/general.js"></script>
	<script type="text/javascript">function getElementsByClass(searchClass,node,tag) {	var classElements = new Array();	if ( node == null ) node = document;	if ( tag == null ) tag = '*';	var els = node.getElementsByTagName(tag);	var elsLen = els.length;	var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");	var j = 0;	for (var i = 0, j = 0; i < elsLen; i++) {		if ( pattern.test(els[i].className) ) {			classElements[j] = els[i]; j++;		}	}	return classElements;}window.onload = function() {	var spoilers = getElementsByClass('spoiler');	for(x in spoilers) {		spoilers[x].getElementsByTagName('div')[0].style.display = 'none';	}};function spoiler(obj) {	var st = obj.getElementsByTagName('div')[0].style;	st.display = (st.display == 'none' || st.display == '') ? 'block' : 'none';}</script>
	{$dbconfig_add}
</head>
<body>
END;
		if($form)
		{
			echo "\n	<form method=\"post\" action=\"".$this->script."\">\n";
			$this->openedform = 1;
		}
		
		echo <<<END
		<div id="container">
		<div id="logo">
			<h1><span class="invisible">DaddyoBB</span></h1>
		</div>
		<div id="inner_container">
		<div id="header">$this->title</div>
END;
		if(empty($this->steps))
		{
			$this->steps = array();
		}
		if(is_array($this->steps))
		{
		echo "\n		<div id=\"progress\">";
				echo "\n			<ul>\n";
				foreach($this->steps as $action => $step)
				{
					if($action == $daddyobb->input['action'])
					{
						echo "				<li class=\"active\"><strong>$step</strong></li>\n";
					}
					else
					{
						echo "				<li>$step</li>\n";
					}
				}
				echo "			</ul>";
		echo "\n		</div>";
		echo "\n		<div id=\"content\">\n";
		}
		else
		{
		echo "\n		<div id=\"progress_error\"></div>";
		echo "\n		<div id=\"content_error\">\n";
		}
		if($title != "")
		{
		echo <<<END
			<h2 class="$image">$title</h2>\n
END;
		}
	}

	function print_contents($contents)
	{
		echo $contents;
	}

	function print_error($message)
	{
		global $lang;
		if(!$this->doneheader)
		{
			$this->print_header($lang->error, "", 0, 1);
		}
		echo "			<div class=\"error\">\n				";
		echo "<h3>".$lang->error."</h3>";
		$this->print_contents($message);
		echo "\n			</div>";
		$this->print_footer();
	}


	function print_footer($nextact="")
	{
		global $lang, $footer_extra;
		if($nextact && $this->openedform)
		{
			echo "\n			<input type=\"hidden\" name=\"action\" value=\"$nextact\" />";
			echo "\n				<div id=\"next_button\"><input type=\"submit\" class=\"submit_button\" value=\"".$lang->next." &raquo;\" /></div><br style=\"clear: both;\" />\n";
			$formend = "</form>";
		}
		else
		{
			$formend = "";
		}

		echo <<<END
		</div>
		<div id="footer">
END;

		$copyyear = date('Y');
		echo <<<END
			<div id="copyright">
				DaddyoBB &copy; $copyyear DaddyoBB Group
			</div>
		</div>
		</div>
		</div>
		$formend
		$footer_extra
</body>
</html>
END;
		exit;
	}
}
?>