<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

class AppPage_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppPage";
	public $title = "Social Pages System";
	public $version = 1.0;
	public $author = "Brint Paris & Pegasus";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Allows personal pages handling on Social.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `pages`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`page_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`parent_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`page_order`			tinyint(3)		unsigned	NOT NULL	DEFAULT '0',
			
			`url_slug`				varchar(48)					NOT NULL	DEFAULT '',
			`title`					varchar(48)					NOT NULL	DEFAULT '',
			`body`					text						NOT NULL,
			
			UNIQUE (`uni_id`, `page_id`),
			INDEX (`parent_id`, `order`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 23;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass = DatabaseAdmin::columnsExist("pages", array("uni_id", "page_id"));
		
		return ($pass);
	}
	
}