<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppFriends Plugin ------
-----------------------------------------

This plugin provides tools to identify a user's friends and check their permissions.

Friends have two types of permissions:

	"View Permissions" is how much the user is allowing you to view.
		
		Full Access would mean the friend can see everything the user creates .
		Standard Access means the friend can see most things the user creates, except those listed as trusted only.
		Limited Access means the friend can only see the things that the user creates that are listed as limited.
		Restricted Access means you're basically limited to public information only, but you're listed as a friend.
	
	"Interact Permissions" is what the friend can actually post.
		
		Full Access allows you to post on everything the user has enabled: comments, posts, articles, etc.
		Standard Access allows all standard features for the user.
		Limited Access means you're probably only able to interact with limited functionality, such as "likes".
		Restricted Access means you're probably not able to do much of anything unless it's the true minimum.
		No Access means you can't interact with anything.
		
		Note: Interact permissions are only as strong as the friend's view permissions allow. If the user cannot view
		something, they often won't have the ability to post in relation to it.

------------------------------
------ Friend Clearance ------
------------------------------

There are a few levels of clearances that work with friends:

7 - Trusted - Full Access
5 - Standard Access
3 - Limited Access
1 - Restricted Access (probably can't see much of anything)
0 - Untrusted


-------------------------------
------ Methods Available ------
-------------------------------

// Check if you are friends with a target UniID
AppFriends::isFriend($uniID, $targetID);

// Get the list of friends
$friends = AppFriends::getList($uniID, [$startPos], [$limit], [$byEngagement]);

// Get the clearance levels marked in a friend request
$clearance = AppFriends::getRequest($uniID, $friendID);

// Get the list of friend requests for a user
$requests = AppFriends::getRequestList($uniID, [$startPos], [$limit]);

// Get the level of clearance shared with a friend
$clearance = AppFriends::getClearance($uniID, $friendID);

// Set the level of clearance for a friend
// If $needsApproval is set to TRUE, this is a friend request
AppFriends::setClearance($uniID, $friendID, $viewClearance, $interactClearance, [$needsApproval]);

// Delete a friend
AppFriends::delete($uniID, $friendID);

// Approve a friend request
AppFriends::approve($uniID, $friendID);

// Deny a friend request
AppFriends::deny($uniID, $friendID);

*/

abstract class AppFriends {
	
	
/****** Check if the target ID is a friend ******/
	public static function isFriend
	(
		$uniID			// <int> The UniID of the user.
	,	$targetID		// <int> The UniID of the target to check if a friend.
	)					// RETURNS <bool> TRUE if target is a friend, FALSE if not.
	
	// AppFriends::isFriend($uniID, $targetID);
	{
		return Database::selectValue("SELECT friend_id FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $targetID)) ? true : false;
	}
	
	
/****** Get the list of friends of a user ******/
	public static function getList
	(
		$uniID					// <int> The UniID of the user to find friends of.
	,	$startPos = 0			// <int> The row number to start at.
	,	$limit = 20				// <int> The number of rows to return.
	,	$byEngagement = false	// <bool> Sort the list by engagement value.
	)							// RETURNS <int:[str:mixed]> the list of friends, array() on failure.
	
	// $friends = AppFriends::getList($uniID, [$startPos], [$limit], [$byEngagement]);
	{
		return Database::selectMultiple("SELECT f.friend_id, u.display_name, u.handle FROM friends_list as f INNER JOIN users as u ON f.friend_id = u.uni_id WHERE f.uni_id=?" . ($byEngagement === true ? " ORDER BY f.engage_value DESC" : "") . " LIMIT " . ($startPos + 0) . ", " . ($limit + 0), array($uniID));
	}
	
	
/****** Check if a user has already requested friendship with another user ******/
	public static function getRequest
	(
		$uniID			// <int> The UniID of the user.
	,	$friendID		// <int> The UniID of the friend.
	)					// RETURNS <str:int> the clearance levels of the request, array() if there is no request.
	
	// $clearance = AppFriends::getRequest($uniID, $friendID);
	{
		if(!$request = Database::selectOne("SELECT view_clearance, interact_clearance FROM friend_requests WHERE uni_id=? AND friend_id=? LIMIT 1", array($friendID, $uniID)))
		{
			return array();
		}
		
		// Recognize Integers
		$request['view_clearance'] = (int) $request['view_clearance'];
		$request['interact_clearance'] = (int) $request['interact_clearance'];
		
		return $request;
	}
	
	
/****** Get a list of a user's friend requests ******/
	public static function getRequestList
	(
		$uniID			// <int> The UniID of the user to find friend requests of.
	,	$startPos = 0	// <int> The row number to start at.
	,	$limit = 20		// <int> The number of rows to return.
	)					// RETURNS <int:[str:mixed]> the list containing friend requests.
	
	// $requests = AppFriends::getRequestList($uniID, [$startPos], [$limit]);
	{
		return Database::selectMultiple("SELECT f.friend_id, f.view_clearance, f.interact_clearance, u.display_name, u.handle FROM friend_requests f INNER JOIN users u ON f.friend_id = u.uni_id WHERE f.uni_id=? LIMIT " . ($startPos + 0) . ", " . ($limit + 0), array($uniID));
	}
	
	
/****** Check what clearance levels a user has permitted a friend ******/
	public static function getClearance
	(
		$uniID					// <int> The UniID of the user to check the role of.
	,	$friendID				// <int> The UniID of the friend.
	)							// RETURNS <int:int> the friend's clearance levels, FALSE on failure.
	
	// $clearance = AppFriends::getClearance($uniID, $friendID);
	{
		if(!$request = Database::selectOne("SELECT view_clearance, interact_clearance FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $friendID)))
		{
			return array(0, 0);
		}
		
		return array((int) $request['view_clearance'], (int) $request['interact_clearance']);
	}
	
	
/****** Set a relationship with a friend ******/
	public static function setClearance
	(
		$uniID					// <int> The User's UniID.
	,	$friendID				// <int> The Friend's UniID (or person requesting to be a friend, if need approval).
	,	$viewClearance 			// <int> The level of view clearance being set.
	,	$interactClearance		// <int> The level of interaction clearance being set.
	)							// RETURNS <bool> TRUE if the clearance was set properly, FALSE on failure.
	
	// AppFriends::setClearance($uniID, $friendID, $viewClearance, $interactClearance);
	{
		// If approval isn't needed, update the current friend settings
		return Database::query("REPLACE INTO friends_list (uni_id, friend_id, view_clearance, interact_clearance) VALUES (?, ?, ?, ?)", array($uniID, $friendID, $viewClearance, $interactClearance));
	}
	
	
/****** Send a friend request ******/
	public static function sendRequest
	(
		$uniID					// <int> The UniID that is requesting the friendship.
	,	$friendID				// <int> The Friend's UniID.
	,	$viewClearance = 5 		// <int> The level of view clearance being set.
	,	$interactClearance = 5	// <int> The level of interaction clearance being set.
	)							// RETURNS <bool> TRUE if the request was sent properly, FALSE on failure.
	
	// AppFriends::sendRequest($uniID, $friendID, [$viewClearance], [$interactClearance]);
	{
		// Check if the request already exists
		if($check = self::getRequest($friendID, $uniID))
		{
			Database::query("REPLACE INTO friend_requests (uni_id, friend_id, view_clearance, interact_clearance) VALUES (?, ?, ?, ?)", array($friendID, $uniID, $viewClearance, $interactClearance));
			
			return true;
		}
		
		// Attempt to add the friend request
		if(!$check = Database::query("INSERT IGNORE INTO friend_requests (uni_id, friend_id, view_clearance, interact_clearance) VALUES (?, ?, ?, ?)", array($friendID, $uniID, $viewClearance, $interactClearance)))
		{
			return false;
		}
		
		// Notify the friend that there is a friend request
		$userdata = User::get($uniID, "handle, display_name");
		
		Notifications::create($friendID, URL::unifaction_social() . "/" . $userData['handle'], "@" . $userdata['handle'] . " has sent you a friend request.");
		
		return true;
	}
	
	
/****** Delete a friend ******/
	public static function delete
	(
		$uniID					// <int> The User's UniID.
	,	$friendID				// <int> The Friend's UniID.
	)							// RETURNS <bool> TRUE if the friend was deleted, FALSE on failure.
	
	// AppFriends::delete($uniID, $friendID);
	{
		Database::startTransaction();
		
		if($pass = Database::query("DELETE IGNORE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $friendID)))
		{
			$pass = Database::query("DELETE IGNORE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($friendID, $uniID));
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Approve a Friend Request ******/
	public static function approve
	(
		$uniID			// <int> The UniID of the user that is approving the request.
	,	$friendID		// <int> the UniID of the friend that is receiving the new permissions.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::approve($uniID, $friendID);
	{
		if(!$request = self::getRequest($friendID, $uniID))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Delete Friend Request
		if($pass = Database::query("DELETE FROM friend_requests WHERE (uni_id=? AND friend_id=?) or (uni_id=? AND friend_id=?) LIMIT 1", array($uniID, $friendID, $friendID, $uniID)))
		{
			// Insert or Update the Friend List
			if($pass = Database::query("INSERT IGNORE INTO friends_list (uni_id, friend_id, view_clearance, interact_clearance) VALUES (?, ?, ?, ?)", array($uniID, $friendID, $request['view_clearance'], $request['interact_clearance'])))
			{
				$pass = Database::query("INSERT IGNORE INTO friends_list (uni_id, friend_id, view_clearance, interact_clearance) VALUES (?, ?, ?, ?)", array($friendID, $uniID, $request['view_clearance'], $request['interact_clearance']));
			}
		}
		
		$success = Database::endTransaction($pass);
		
		// Notify the friend of the friend update
		if($success)
		{
			// Notify the friend that there is a friend request
			$userdata = User::get($uniID, "handle, display_name");
			
			Notifications::create($friendID, URL::unifaction_social() . "/" . $userData['handle'], "@" . $userdata['handle'] . " has approved your friend request.");
		}
		
		return $success;
	}
	
	
/****** Deny a friend request ******/
	public static function deny
	(
		$uniID		// <int> The UniID of the user.
	,	$friendID	// <int> the UniID of the friend.
	)				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::deny($uniID, $friendID);
	{
		return Database::query("DELETE FROM friend_requests WHERE (uni_id=? AND friend_id=?) or (uni_id=? AND friend_id=?) LIMIT 1", array($uniID, $friendID, $friendID, $uniID));
	}
	
	
/****** Track engagement you have with a target ******/
	public static function trackEngagement
	(
		$uniID				// <int> The uniID to track.
	,	$targetID			// <int> The uniID of the target account.
	,	$engageValue = 1	// <int> The value of the engagement.
	)						// RETURNS <bool> TRUE on success, FALSE otherwise.
	
	// AppFriends::trackEngagement($uniID, $targetID, $engageValue);
	{
		/*
			Engagement Levels:
			View their page:		1
			Comment on their page:	4
			Send Message:			3
		*/
		
		// Prepare dates
		$cycle = date('Ym');
		
		$engageCheck = (int) Database::selectValue("SELECT engage_value FROM friend_engagement WHERE uni_id=? AND cycle=? AND friend_id=? LIMIT 1", array($uniID, $cycle, $targetID));
		
		// Update the level of engagement you have with this target (for this cycle)
		if(!$engageCheck)
		{
			Database::query("INSERT INTO friend_engagement (uni_id, friend_id, engage_value, cycle) VALUES (?, ?, ?, ?)", array($uniID, $targetID, $engageValue, $cycle));
			
			// Run the engagement update
			// This will activate every cycle
			return self::updateEngagement($uniID, $targetID);
		}
		
		return Database::query("UPDATE friend_engagement SET engage_value = engage_value + ? WHERE uni_id=? AND cycle=? AND friend_id=? LIMIT 1", array($engageValue, $uniID, $cycle, $targetID));
	}
	
	
/****** Run this in the beginning of each cycle (updates all friends to recent engagement value) ******/
	private static function updateEngagement
	(
		$uniID		// <int> The UniID of the user to update the engagement with.
	,	$friendID	// <int> The UniID of the friend to update the engagement with.
	)				// RETURNS <bool> TRUE when finished, FALSE if fails.
	
	// self::updateEngagement($uniID, $friendID)
	{
		// End if the user is not a friend
		if(!self::isFriend($uniID, $friendID)) { return false; }
		
		// Retrieve the user's engagement levels over the past few cycles
		$total = 0;
		$lastCycle = date("Ym", time() - (3600 * 24 * 31 * 4));
		
		$engagements = Database::selectMultiple("SELECT engage_value FROM friend_engagement WHERE uni_id=? AND cycle > ? AND friend_id=?", array($uniID, $lastCycle, $friendID));
		
		foreach($engagements as $eng)
		{
			$total += (int) $eng['engage_value'];
		}
		
		// Set the Friend Engagement Value
		return Database::query("UPDATE IGNORE friends_list SET engage_value=? WHERE uni_id=? AND friend_id=? LIMIT 1", array($total, $uniID, $friendID));
	}
	
	
/****** Get List of Mutual Friend ******/
	public static function getMutualFriends
	(
		$uniID		// <int> The uniID of the user.
	,	$friendID	// <int> The uniID of the friend.
	)				// RETURNS <array> list of mutual friends, or empty array if none available.
	
	// $friends = AppFriends::getMutualFriends($uniID, $friendID);
	{
		// For now, just return empty
		// When we build this, we'll want to cache the results for an amount of time based on friend engagement
		return array();
		
		/**********************************************
		****** Build a better algorithm for this ******
		**********************************************/
		/*
		$myFriends = array();
		$theirFriends = array();
		
		$getMyFriends = Database::selectMultiple("SELECT friend_uni_id FROM friends WHERE uni_id=?", array($uniID));
		$getTheirFriends = Database::selectMultiple("SELECT friend_uni_id FROM friends WHERE uni_id=?", array($friendID));
		
		foreach($getMyFriends as $friend)
		{
			$myFriends[] = (int) $friend['friend_uni_id'];
		}
		
		foreach($getTheirFriends as $friend)
		{
			$theirFriends[] = (int) $friend['friend_uni_id'];
		}
		
		$friendIDs = array_intersect($myFriends, $theirFriends);
		
		return Database::selectMultiple("SELECT uni_id, display_name FROM users WHERE uni_id IN (" . implode(", ", $friendIDs) . ")", array());
		*/
	}
	
	
/****** Get List of Suggested Friends (more likely that you may know them) ******/
	public static function suggestedFriendScan (
	)				// RETURNS <array> full list of suggested friend matrix, or empty array if fails.
	
	// $suggestedFriends = AppFriends::suggestedFriendScan();
	{
		// Thanks to MatBailie on Stack Overflow
		// http://stackoverflow.com/questions/12915547/efficient-friend-suggestion-sql-query-without-using-php
		
		/*
		return Database::selectMultiple("
			SELECT
				me.uni_id						AS member_id,
				their_friends.friend_uni_id		AS suggested_friend_id,
				COUNT(*)						AS friends_in_common
			FROM
				users			AS me
			INNER JOIN
				friends			AS my_friends
				ON my_friends.uni_id = me.uni_id
			INNER JOIN
				friends			AS their_friends
				ON their_friends.uni_id = my_friends.friend_uni_id
			LEFT JOIN
				friends			AS friends_with_me
				ON  friends_with_me.uni_id			= their_friends.friend_uni_id
				AND friends_with_me.friend_uni_id	= me.uni_id
			WHERE
				friends_with_me.uni_id IS NULL
			GROUP BY
				me.uni_id,
				their_friends.friend_uni_id;", array());
		*/
	}
	
}

