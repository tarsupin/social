<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class social_posts_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Social Posts";		// <str> The title for this table.
	public $description = "The posts on a user's social page.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "social_posts";		// <str> The name of the table.
	public $fieldIndex = array("id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 6;			// <int> The clearance level required to view this table.
	public $permissionSearch = 6;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 6;			// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 6;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_posts`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`poster_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`post`					varchar(255)				NOT NULL	DEFAULT '',
			
			`attachment_id`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			`has_comments`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`date_posted`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(id) PARTITIONS 61;
		");
		
		return DatabaseAdmin::tableExists($this->tableKey);
	}
	
	
/****** Build the schema for the table ******/
	public function buildSchema (
	)			// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $schema->buildSchema();
	{
		Database::startTransaction();
		
		// Create Schmea
		$define = new SchemaDefine($this->tableKey, true);
		
		$define->set("id")->title("Post ID")->description("The ID of the post.")->isUnique()->isReadonly();
		$define->set("poster_id")->title("Author's UniID")->description("The UniFaction ID of the person who created the post.")->isReadonly();
		$define->set("post")->title("Post Message")->description("The message of the post.");
		$define->set("attachment_id")->title("Attachment ID")->description("The ID of an attachment to the post.");
		$define->set("has_comments")->title("Has Comments?")->description("Whether or not there are comments on the site.")->pullType("select", "yes-no");
		$define->set("date_posted")->title("Date Posted")->description("The timestamp when the post was created.")->fieldType("timestamp");
		
		Database::endTransaction();
		
		return true;
	}
	
	
/****** Set the rules for interacting with this table ******/
	public function __call
	(
		$name		// <str> The name of the method being called ("view", "search", "create", "delete")
	,	$args		// <mixed> The args sent with the function call (generaly the schema object)
	)				// RETURNS <mixed> The resulting schema object.
	
	// $schema->view($schema);		// Set the "view" options
	// $schema->search($schema);	// Set the "search" options
	{
		// Make sure that the appropriate schema object was sent
		if(!isset($args[0])) { return; }
		
		// Set the schema object
		$schema = $args[0];
		
		switch($name)
		{
			case "view":
				$schema->addFields("id", "poster_id", "post", "attachment_id", "has_comments", "date_posted");
				$schema->sort("id", "desc");
				break;
				
			case "search":
				$schema->addFields("id", "poster_id", "post", "date_posted");
				break;
				
			case "create":
				break;
				
			case "edit":
				$schema->addFields("id", "poster_id", "post", "attachment_id", "has_comments", "date_posted");
				break;
		}
		
		return $schema;
	}
	
}