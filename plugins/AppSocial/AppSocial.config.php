<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

class AppSocial_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppSocial";
	public $title = "Social Handler";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides important tools for the social system.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_data`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`has_headerPhoto`		tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`perm_access`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_post`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_comment`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_approval`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`feed_sort`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`posts`					mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`friends`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`followers`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`following`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			`last_feed_update`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 7;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_posts`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`orig_handle`			varchar(22)					NOT NULL	DEFAULT '',
			`poster_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`post`					varchar(255)				NOT NULL	DEFAULT '',
			
			`clearance`				tinyint(1)					NOT NULL	DEFAULT '0',
			`attachment_id`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			`has_comments`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`date_posted`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(id) PARTITIONS 127;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `users_posts`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`id`					int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 61;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("social_data", array("uni_id", "has_headerPhoto"));
		$pass2 = DatabaseAdmin::columnsExist("social_posts", array("id", "post"));
		$pass3 = DatabaseAdmin::columnsExist("users_posts", array("uni_id", "id"));
		
		return ($pass1 and $pass2 and $pass3);
	}
	
}