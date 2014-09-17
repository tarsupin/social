<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class user_preferences_schema {
	
	
/****** Plugin Variables ******/
	public $title = "User Preferences";		// <str> The title for this table.
	public $description = "Keeps track of the user's preferences and level of preference.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "user_preferences";		// <str> The name of the table.
	public $fieldIndex = array("uni_id", "preference");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
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
		CREATE TABLE IF NOT EXISTS `user_preferences`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`preference`			varchar(22)					NOT NULL	DEFAULT '',
			`weight`				float(5,2)					NOT NULL	DEFAULT '0.00',
			
			UNIQUE (`uni_id`, `preference`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 31;
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
		
		$define->set("uni_id")->title("UniID")->description("The user's UniFaction ID.");
		$define->set("preference")->description("The user's preference.");
		$define->set("weight")->title("Preference Weight")->description("The strength of the preference.");
		
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
				$schema->addFields("uni_id", "preference", "weight");
				$schema->sort("uni_id");
				$schema->sort("preference");
				break;
				
			case "search":
				$schema->addFields("uni_id", "preference", "weight");
				break;
				
			case "create":
			case "edit":
				break;
		}
		
		return $schema;
	}
	
}