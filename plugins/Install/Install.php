<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Social Installation
abstract class Install extends Installation {
	
	
/****** Plugin Variables ******/
	
	// These addon plugins will be selected for installation during the "addon" installation process:
	public static $addonPlugins = array(	// <str:bool>
		"Analytics"			=> true
	,	"Attachment"		=> true
	,	"Avatar"			=> true
	,	"UserActivity"		=> true
	,	"FeaturedWidget"	=> true
	,	"Friends"			=> true
	,	"Notifications"		=> true
	);
	
	
/****** App-Specific Installation Processes ******/
	public static function setup(
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	{
		return true;
	}
}
