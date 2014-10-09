<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class friend_requests_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Friend Requests";		// <str> The title for this table.
	public $description = "Contains the list of friend requests.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "friend_requests";			// <str> The name of the table.
	public $fieldIndex = array("uni_id", "friend_id");		// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 6;			// <int> The clearance level required to view this table.
	public $permissionSearch = 6;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 11;		// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 11;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Build the schema for the table ******/
	public function buildSchema (
	)			// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $schema->buildSchema();
	{
		Database::startTransaction();
		
		// Create Schmea
		$define = new SchemaDefine($this->tableKey, true);
		
		$define->set("uni_id")->title("UniID")->description("The user's UniFaction ID.")->isReadonly();
		$define->set("friend_id")->title("Friend UniID")->description("The UniFaction ID of the friend.")->isReadonly();
		$define->set("view_clearance")->title("View Clearance")->description("The level of viewing clearance the user is requesting to share.")->pullType("select", "friend-view-clearance");
		$define->set("interact_clearance")->title("Interaction Clearance")->description("The level of interaction clearance the user is requesting to share.")->pullType("select", "friend-post-clearance");
		
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
				$schema->addFields("uni_id", "friend_id", "view_clearance", "interact_clearance");
				$schema->sort("uni_id");
				$schema->sort("friend_id");
				break;
				
			case "search":
				$schema->addFields("uni_id", "friend_id", "view_clearance", "interact_clearance");
				break;
				
			case "create":
				break;
				
			case "edit":
				break;
		}
		
		return $schema;
	}
	
}