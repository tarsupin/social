<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class friend_engagement_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Friend Engagement Tracker";		// <str> The title for this table.
	public $description = "Contains the list of friend requests.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "friend_engagement";			// <str> The name of the table.
	public $fieldIndex = array("uni_id", "cycle", "friend_id");		// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 6;			// <int> The clearance level required to view this table.
	public $permissionSearch = 6;		// <int> The clearance level required to search this table.
	public $permissionCreate = 9;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 9;			// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 9;		// <int> The clearance level required to delete an entry on this table.
	
	
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
		$define->set("cycle")->title("Cycle")->description("The year+month cycle that this engagement tracking is associated with.")->pullType("select", "friend-view-clearance");
		$define->set("engage_value")->title("Engagement Level")->description("The level of engagement the user has had with the friend.");
		
		return Database::endTransaction();
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
	
	
/****** The "FORM" pull method for the "cycle" field ******/
	public static function pullMethodForm_cycle
	(
		$postVal	// <int> The POST value that is currently assigned.
	)				// RETURNS <str>
	
	// $schema->pullMethod_cycle($type, $postVal);
	{
		$prepare = array();
		
		// Get values around the current date
		$year = date("Y") - 1;
		$month = date("m");
		
		for($a = 0;$a < 24;$a++)
		{
			$date = DateTime::createFromFormat("Ym", $year . $month);
			$prepare[$date->format('Ym')] = $date->format("F Y");
			$month++;
		}
		
		// Get values around the current setting
		$postVal = max((int) date("Ym"), $postVal);
		
		$year = substr($postVal, 0, 4);
		$month = substr($postVal, 4) - 5;
		
		for($a = 0;$a < 10;$a++)
		{
			$date = DateTime::createFromFormat("Ym", $year . $month);
			$prepare[$date->format('Ym')] = $date->format("F Y");
			$month++;
		}
		
		ksort($prepare);
		
		return $prepare;
	}
	
	
/****** The "VIEW" pull method for the "cycle" field ******/
	public static function pullMethodView_cycle
	(
		$postVal	// <int> The POST value that is currently assigned.
	)				// RETURNS <str>
	
	// $schema->pullMethod_cycle($type, $postVal);
	{
		$date = DateTime::createFromFormat("Ym", $postVal);
		
		return $date->format("F Y");
	}
	
}