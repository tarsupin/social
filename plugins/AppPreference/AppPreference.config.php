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
		// `cycle` is equal to date("Ym");
		Database::exec("
		CREATE TABLE IF NOT EXISTS `friend_engagement`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`friend_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`cycle`					mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`engage_value`			mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `cycle`, `friend_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 63;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `friend_requests`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`friend_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`view_clearance`		tinyint(1)					NOT NULL	DEFAULT '0',
			`interact_clearance`	tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `friend_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(friend_id) PARTITIONS 3;
		");
		
		/*
			`engage_value` is the rating of how engaged they are with that friend
			`view_clearance` is the level of viewing privileges the friend has granted
			`interact_clearance` is the level of interaction privileges the friend has granted
		*/
		Database::exec("
		CREATE TABLE IF NOT EXISTS `friends_list`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`friend_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`engage_value`			mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			
			`view_clearance`		tinyint(1)					NOT NULL	DEFAULT '0',
			`interact_clearance`	tinyint(1)					NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `friend_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 63;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("friend_engagement", array("uni_id", "friend_id"));
		$pass2 = DatabaseAdmin::columnsExist("friend_requests", array("uni_id", "friend_id"));
		$pass3 = DatabaseAdmin::columnsExist("friends_list", array("uni_id", "friend_id"));
		
		return ($pass1 and $pass2 and $pass3);
	}
	
}