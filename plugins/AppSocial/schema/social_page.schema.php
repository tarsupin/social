<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class social_page_schema {
	
	
/****** Plugin Variables ******/
	public $title = "Social Page";		// <str> The title for this table.
	public $description = "Settings that are configured for a user's social page.";		// <str> The description of this table.
	
	// Table Settings
	public $tableKey = "social_page";		// <str> The name of the table.
	public $fieldIndex = array("uni_id");	// <int:str> The field(s) used for the index (for editing, deleting, row ID, etc).
	public $autoDelete = false;			// <bool> TRUE will delete rows instantly, FALSE will require confirmation.
	
	// Permissions
	// Note: Set a permission value to 11 or higher to disallow it completely.
	public $permissionView = 6;			// <int> The clearance level required to view this table.
	public $permissionSearch = 6;		// <int> The clearance level required to search this table.
	public $permissionCreate = 11;		// <int> The clearance level required to create an entry on this table.
	public $permissionEdit = 6;			// <int> The clearance level required to edit an entry on this table.
	public $permissionDelete = 9;		// <int> The clearance level required to delete an entry on this table.
	
	
/****** Install the table ******/
	public function install (
	)			// RETURNS <bool> TRUE if the installation was success, FALSE if not.
	
	// $schema->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `social_page`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`has_headerPhoto`		tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`description`			varchar(80)					NOT NULL	DEFAULT '',
			
			`perm_follow`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_access`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_post`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_comment`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`perm_approval`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 5;
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
		$define->set("has_headerPhoto")->title("Header Photo?")->description("Whether or not the user has designated a header photo.")->pullType("select", "yes-no");
		$define->set("description")->description("Description or caption for the social page.");
		$define->set("perm_follow")->title("Follow Clearance")->description("The level of clearance required to follow this user.")->pullType("select", "users-clearance");
		$define->set("perm_access")->title("Access Clearance")->description("The level of clearance required to access this user's social page.")->pullType("select", "users-clearance");
		$define->set("perm_post")->title("Post Clearance")->description("The level of clearance required to post on this user's social page.")->pullType("select", "users-clearance");
		$define->set("perm_comment")->title("Comment Clearance")->description("The level of clearance required to comment on this user's social page.")->pullType("select", "users-clearance");
		$define->set("perm_approval")->title("Approval Clearance")->description("The level of clearance required to bypass post approval on the page.")->pullType("select", "users-clearance");
		
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
				$schema->addFields("uni_id", "description", "has_headerPhoto", "perm_access", "perm_follow", "perm_post", "perm_comment", "perm_approval");
				$schema->sort("uni_id");
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