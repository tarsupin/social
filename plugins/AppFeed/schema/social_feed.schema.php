<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class social_feed_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Social Feed";		// <str> The title for this table.
	public $description = "Tracks the last time each user's social feed was updated.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "social_feed";		// <str> The name of the table.
	public $fieldIndex = array("uni_id", "post_id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = true;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 6;			// <int> The clearance level required to view this table.
	public $permissionSearch = 6;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 11;		// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 11;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_feed`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`engage_value`			smallint(6)		unsigned	NOT NULL	DEFAULT '0',
			
			`post_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			INDEX (`uni_id`, `engage_value`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 11;
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
		
		$define->set("uni_id")->title("UniID")->description("The User's UniFaction ID.")->isReadonly();
		$define->set("engage_value")->title("User Engagement Rating")->description("The rating of how much user engagement this post has received.");
		$define->set("post_id")->title("Post ID")->description("The ID of the post being added to the user's feed.");
		
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
				$schema->addFields("uni_id", "engage_value", "post_id");
				$schema->sort("uni_id");
				$schema->sort("engage_value");
				break;
				
			case "search":
				$schema->addFields("uni_id");
				break;
				
			case "create":
			case "edit":
				break;
		}
		
		return $schema;
	}
	
}