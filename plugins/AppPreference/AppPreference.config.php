<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

class AppPreference_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppPreference";
	public $title = "Preference Tools";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Tracks user preferences and interests to improve results for searching and finding people.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("friend_engagement", array("uni_id", "friend_id"));
		$pass2 = DatabaseAdmin::columnsExist("friends_requests", array("uni_id", "friend_id"));
		$pass3 = DatabaseAdmin::columnsExist("friends_list", array("uni_id", "friend_id"));
		
		return ($pass1 and $pass2 and $pass3);
	}
	
}