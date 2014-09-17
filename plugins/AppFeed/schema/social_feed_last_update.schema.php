<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class social_feed_last_update_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Last Social Feed Update";		// <str> The title for this table.
	public $description = "Tracks the last time each user's social feed was updated.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "social_feed_last_update";		// <str> The name of the table.
	public $fieldIndex = array("uni_id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = true;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 5;			// <int> The clearance level required to view this table.
	public $permissionSearch = 11;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 11;		// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 6;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_feed_last_update`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_lastUpdate`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 7;
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
		
		$define->set("uni_id")->title("UniID")->description("The User's UniFaction ID.")->isUnique()->isReadonly();
		$define->set("date_lastUpdate")->title("Date of Last Update")->description("The timestamp of the last time the user's social feed was updated.")->isReadonly();
		
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
				$schema->addFields("uni_id");
				$schema->sort("uni_id");
				break;
				
			case "search":
			case "create":
			case "edit":
				break;
		}
		
		return $schema;
	}
	
}