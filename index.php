<?php

/****** Preparation ******/
define("CONF_PATH",		dirname(__FILE__));
define("SYS_PATH", 		dirname(CONF_PATH) . "/system");

// Load phpTesla
require(SYS_PATH . "/phpTesla.php");

// Initialize and Test Active User's Behavior
Me::$getColumns = "uni_id, handle, clearance, role, display_name";

Me::initialize();

// Determine which page you should point to, then load it
require(SYS_PATH . "/routes.php");

/****** Dynamic URLs ******/
// If a page hasn't loaded yet, check if there is a dynamic load
if($url[0] != '')
{
	if(!$userData = User::getDataByHandle($url[0], "uni_id, display_name, handle"))
	{
		User::silentRegister($url[0]);
		
		$userData = User::getDataByHandle($url[0], "uni_id, display_name, handle");
	}
	
	if($userData)
	{	
		// Prepare "You"
		You::$id = (int) $userData['uni_id'];
		You::$name = $userData['display_name'];
		You::$handle = $userData['handle'];
		
		if(isset($url[1]) && $url[1] == "pages")
		{
			if(isset($url[2]))
			{
				if(File::exists(APP_PATH . "/controller/pages/" . $url[2] . ".php"))
				{
					require(APP_PATH . "/controller/pages/" . $url[2] . ".php"); exit;
				}
			}
			require(APP_PATH . '/controller/pages.php'); exit;
		}
		require(APP_PATH . '/controller/social-page.php'); exit;
	}
}
//*/

/****** 404 Page ******/
// If the routes.php file or dynamic URLs didn't load a page (and thus exit the scripts), run a 404 page.
require(SYS_PATH . "/controller/404.php");
