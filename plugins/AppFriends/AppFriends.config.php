<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppFriends_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppFriends";
	public $title = "Friend System";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "The main friend system, designed to synchronize with all UniFaction sites.";
	
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
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 127;
		");
		
		/*
			`clearance` is the level of privileges the friend has granted
			`engage_value` is the rating of how engaged they are with that friend
			
			Notes:
				* If clearance is 1, you're following them
				* If clearance is 2, that person is following you
				* If clearance is 3, you're following each other
				* If clearance is 4, it means you're friends
				* If clearance is 5, you've added them to "close friends"
		*/
		Database::exec("
		CREATE TABLE IF NOT EXISTS `friends_list`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`friend_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`clearance`				tinyint(1)					NOT NULL	DEFAULT '0',
			`engage_value`			mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `friend_id`, `clearance`),
			INDEX (`uni_id`, `clearance`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 127;
		");
		
		/*
			`uni_id`		the user receiving the request
			`friend_id`		the user that created the request
		*/
		Database::exec("
		CREATE TABLE IF NOT EXISTS `friends_requests`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`friend_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_requested`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `friend_id`),
			INDEX (`friend_id`, `uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 3;
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
		$pass2 = DatabaseAdmin::columnsExist("friends_list", array("uni_id", "friend_id"));
		$pass3 = DatabaseAdmin::columnsExist("friends_requests", array("uni_id", "friend_id"));
		
		return ($pass1 and $pass2 and $pass3);
	}
	
}
