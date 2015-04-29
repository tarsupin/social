<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppPage Plugin ------
-----------------------------------------

This plugin handles personal pages on the social site.


-------------------------------
------ Methods Available ------
-------------------------------

$page	=	AppPage::createPage(Me::$id, "My favorite Recipies", "...");
$page	=	AppPage::editPage(Me::$id, "My favorite Recipies", "...");
$pages	=	AppPage::getList(Me::$id);
$page	=	AppPage::getPage(Me::$id, 1);
			AppPage::deletePage(Me::$id, 1);
$count	=	AppPage::countParents(Me::$id);
$count	=	AppPage::countChildren(Me::$id);
			AppPage::moveFlat(Me::$id, 1, -1);
			AppPage::moveOther(Me::$id, 1, 0);

*/

abstract class AppPage {
	
	
/****** Create new page ******/
	public static function createPage
	(
		int $uniID				// <int> The ID of the user creating the page.
	,	string $title				// <str> The title of the page.
	,	string $body				// <str> The content of the page in BB Code.
	): mixed						// RETURNS <mixed> The ID and url slug of the new page, or false on failure.
	
	// $page = AppPage::createPage(Me::$id, "My favorite Recipies", "...");
	{			
		// Check input
		FormValidate::text("Title", $title, 1, 48);
		$body = Security::purify($body);
		if(strlen($body) < 1)
		{
			Alert::error("Content Length", "Please enter a message.");
		}
		elseif(strlen($_POST['body']) > 32000)
		{
			Alert::error("Content Length", "Your content length may not exceed 32000 characters.");
		}
		
		if(FormValidate::pass())
		{
			Database::startTransaction();
		
			// Prepare Values
			$uniqueID = UniqueID::get("personal-pages");
			if(!$uniqueID)
			{
				UniqueID::newCounter("personal-pages");
				$uniqueID = UniqueID::get("personal-pages");
			}
			
			// Create the URL Slug for this page
			$urlSlug = Sanitize::variable(str_replace(" ", "-", strtolower($title)), "-");
			
			// Get order
			$order = self::countParents($uniID)+1;
			
			// Create the page
			$pass = Database::query("UPDATE social_data SET pages=pages+1 WHERE uni_id=?", array($uniID));
			if($pass)
			{
				$pass = Database::query("INSERT INTO pages VALUES (?, ?, ?, ?, ?, ?, ?)", array($uniID, $uniqueID, 0, $order, $urlSlug, $title, $body));
			}
			
			Database::endTransaction($pass);
			return array($uniqueID, $urlSlug);
		}
		
		return false;
	}
	
/****** Edit page ******/
	public static function editPage
	(
		int $uniID				// <int> The ID of the user creating the page.
	,	int $pageID				// <int> The ID of the page.
	,	string $title				// <str> The title of the page.
	,	string $body				// <str> The content of the page in BB Code.
	): bool						// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $page = AppPage::editPage(Me::$id, "My favorite Recipies", "...");
	{			
		// Check input
		FormValidate::text("Title", $title, 1, 48);
		$body = Security::purify($body);
		if(strlen($body) < 1)
		{
			Alert::error("Content Length", "Please enter a message.");
		}
		elseif(strlen($_POST['body']) > 32000)
		{
			Alert::error("Content Length", "Your content length may not exceed 32000 characters.");
		}
		
		if(FormValidate::pass())
		{
			Database::startTransaction();
					
			// Edit the page
			$pass = Database::query("UPDATE pages SET title=?, body=? WHERE uni_id=? AND page_id=? LIMIT 1", array($title, $body, $uniID, $pageID));
			
			return Database::endTransaction($pass);
		}
		
		return false;
	}
	
/****** Get list of pages ******/
	public static function getList
	(
		int $uniID				// <int> The ID of the user owning the pages.
	): array <int, mixed>						// RETURNS <int:mixed> The pages.
	
	// $pages = AppPage::getList(Me::$id);
	{
		$temp = Database::selectMultiple("SELECT page_id, parent_id, page_order, url_slug, title FROM pages WHERE uni_id=? ORDER BY parent_id, page_order", array($uniID));
		$pages = array();
		foreach($temp as $t)
		{
			$pages[(int) $t['parent_id']][(int) $t['page_order']] = array("page_id" => $t['page_id'], "parent_id" => $t['parent_id'], "url_slug" => $t['url_slug'], "title" => $t['title']);
		}
		
		return $pages;
	}
	
/****** Get page ******/
	public static function getPage
	(
		int $uniID				// <int> The ID of the user owning the page.
	,	int $pageID				// <int> The page ID.
	): array <str, mixed>						// RETURNS <str:mixed> The page.
	
	// $page = AppPage::getPage(Me::$id, 1);
	{
		return Database::selectOne("SELECT * FROM pages WHERE uni_id=? AND page_id=? LIMIT 1", array($uniID, $pageID));
	}
	
/****** Delete page ******/
	public static function deletePage
	(
		int $uniID				// <int> The ID of the user owning the page.
	,	int $pageID				// <int> The page ID.
	): bool						// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $page = AppPage::deletePage(Me::$id, 1);
	{
		// get page data
		$page = self::getPage($uniID, $pageID);
		if(!$page)
		{
			return false;
		}
		
		Database::startTransaction();
		
		// check for children and move them
		$children = Database::selectMultiple("SELECT page_id FROM pages WHERE uni_id=? AND parent_id=?", array($uniID, $pageID));
		foreach($children as $child)
		{
			AppPage::moveOther($uniID, (int) $child['page_id'], 0);
		}
		
		if($pass = Database::query("DELETE FROM pages WHERE uni_id=? AND page_id=? LIMIT 1", array($uniID, $pageID)))
		{
			if($pass = Database::query("UPDATE social_data SET pages=pages-1 WHERE uni_id=? AND pages>0 LIMIT 1", array($uniID)))
			{				
				$pass = Database::query("UPDATE pages SET page_order=page_order-1 WHERE uni_id=? AND parent_id=? AND page_order>?", array($uniID, $page['parent_id'], $page['page_order']));
			}
		}
		return Database::endTransaction($pass);
	}
	
/******* Count the top level pages *******/	
	public static function countParents
	(
		int $uniID				// <int> The ID of the user owning the pages.
	): int						// RETURNS <int> The number of top level (parent) pages.
	
	// $count = AppPage::countParents(Me::$id);
	{
		return (int) Database::selectValue("SELECT COUNT(page_id) FROM pages WHERE uni_id=? AND parent_id=?", array($uniID, 0));
	}
	
/******* Count the low level pages *******/	
	public static function countChildren
	(
		int $uniID				// <int> The ID of the user owning the pages.
	,	int $parentID			// <int> The ID of the parent page.
	): int						// RETURNS <int> The number of low level (children) pages.
	
	// $count = AppPage::countChildren(Me::$id);
	{
		return (int) Database::selectValue("SELECT COUNT(page_id) FROM pages WHERE uni_id=? AND parent_id=?", array($uniID, $parentID));
	}
	
/****** Move Page ******/
	public static function moveFlat
	(
		int $uniID				// <int> The ID of the user owning the page.
	,	int $pageID				// <int> The ID of the page to move.
	,	int $direction			// <int> -1 for left/up, 1 for right/down.
	): bool						// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppPage::moveFlat(Me::$id, 1, -1);
	{
		// get page data
		$page = self::getPage($uniID, $pageID);
		if(!$page)
		{
			return false;
		}
		
		if($direction < 0 && $page['page_order'] <= 1)
		{
			return true;
		}
		
		// get number of siblings (including this page)
		if($page['parent_id'] == 0)
		{
			$count = self::countParents($uniID);
		}
		else
		{
			$count = self::countChildren($uniID, (int) $page['parent_id']);
		}
		
		if($direction > 0 && $page['page_order'] >= $count)
		{
			return true;
		}
		
		Database::startTransaction();
		if($pass = Database::query("UPDATE pages SET page_order=page_order-? WHERE uni_id=? AND parent_id=? AND page_order=? LIMIT 1", array($direction, $uniID, $page['parent_id'], $page['page_order']+$direction)))
		{
			$pass = Database::query("UPDATE pages SET page_order=page_order+? WHERE uni_id=? AND page_id=? LIMIT 1", array($direction, $uniID, $pageID));
		}
		
		return Database::endTransaction($pass);
	}
	
/****** Move Page ******/
	public static function moveOther
	(
		int $uniID				// <int> The ID of the user owning the page.
	,	int $pageID				// <int> The ID of the page to move.
	,	int $parentID			// <int> The ID of the new parent. 0 for top level.
	): bool						// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppPage::moveOther(Me::$id, 1, 0);
	{
		if($parentID == 0)
		{
			$count = self::countParents($uniID);
		}
		else
		{
			$count = self::countChildren($uniID, $parentID);
		}
		
		// get page data
		$page = self::getPage($uniID, $pageID);
		if(!$page)
		{
			return false;
		}

		if($parentID != 0)
		{
			$parent = self::getPage($uniID, $parentID);
			if(!$parent)
			{
				return false;
			}
		}
		
		Database::startTransaction();
		if($pass = Database::query("UPDATE pages SET parent_id=?, page_order=? WHERE uni_id=? AND page_id=? LIMIT 1", array($parentID, $count+1, $uniID, $pageID)))
		{
			$pass = Database::query("UPDATE pages SET page_order=page_order-1 WHERE uni_id=? AND parent_id=? AND page_order>?", array($uniID, $page['parent_id'], $page['page_order']));
		}
		
		return Database::endTransaction($pass);
	}
	
}