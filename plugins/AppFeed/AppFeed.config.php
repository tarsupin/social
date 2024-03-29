<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

class AppFeed_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppFeed";
	public $title = "Social Feed";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "The Social Feed (the updates from your friends).";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_feed`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`engage_value`			smallint(6)		unsigned	NOT NULL	DEFAULT '0',
			
			`post_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `post_id`),
			INDEX (`uni_id`, `engage_value`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 31;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("social_feed", array("uni_id", "post_id"));
		
		return ($pass1);
	}
	
}