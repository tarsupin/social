<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppFriends Plugin ------
-----------------------------------------

This plugin provides tools to identify a user's friends and check their permissions.

------------------------------
------ Friend Clearance ------
------------------------------

There are a few levels of clearances that work with friends:

6 - Trusted Friends
4 - Friends
3 - Both Following Each Other
2 - Being Followed
1 - Following


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
	
	
/****** Get the clearance level of a friend ******/
	public static function getClearance
	(
		$uniID			// <int> The UniID of the main user.
	,	$friendID		// <int> The UniID of the friend to check the clearance of.
	)					// RETURNS <int> the friend's clearance level, 0 on failure.
	
	// $clearance = AppFriends::getClearance($uniID, $friendID);
	{
		return (int) Database::selectValue("SELECT clearance FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $friendID));
	}
	
	
/****** Set a relationship with a friend ******/
	public static function setClearance
	(
		$uniID			// <int> The User's UniID.
	,	$friendID		// <int> The Friend's UniID.
	,	$clearance 		// <int> The level of clearance being set.
	)					// RETURNS <bool> TRUE if the clearance was set properly, FALSE on failure.
	
	// AppFriends::setClearance($uniID, $friendID, $clearance);
	{
		// Get the friend's current clearance
		$clearance = self::getClearance($uniID, $friendID);
		
		if($clearance)
		{
			Database::startTransaction();
			
			if($pass = Database::query("DELETE FROM friends_list WHERE uni_id=? AND friend_id=?", array($uniID, $friendID)))
			{
				$pass = Database::query("INSERT INTO friends_list (uni_id, friend_id, clearance) VALUES (?, ?, ?)", array($uniID, $friendID, $clearance));
			}
			
			return Database::endTransaction($pass);
		}
		
		return Database::query("REPLACE INTO friends_list (uni_id, friend_id, clearance) VALUES (?, ?, ?)", array($uniID, $friendID, $clearance));
	}
	
	
/****** Follow a User ******/
	public static function follow
	(
		$uniID			// <int> The UniID that's going to follow someone.
	,	$friendID		// <int> The UniID to follow.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::follow($uniID, $friendID);
	{
		// Get your clearance level with the target friend
		$myClearance = AppFriends::getClearance($uniID, $friendID);
		
		// Check if you're already following that person
		if($myClearance >= 1 and $myClearance != 2)
		{
			return true;
		}
		
		// Get the friend's clearance of you
		$friendClearance = AppFriends::getClearance($friendID, $uniID);
		
		// Begin the transaction
		$pass = true;
		
		Database::startTransaction();
		
		// Set the friend's entry
		if(!$friendClearance)
		{
			$pass = Database::query("REPLACE INTO friends_list (uni_id, friend_id, clearance) VALUES (?, ?, ?)", array($friendID, $uniID, 2));
		}
		else
		{
			// Set the new friend clearance level
			$nfc = max(3, $friendClearance);
			
			$pass = Database::query("UPDATE friends_list SET clearance=? WHERE uni_id=? AND friend_id=? LIMIT 1", array($nfc, $friendID, $uniID));
		}
		
		if(!$pass) { return Database::endTransaction(false); }
		
		// Set the user's entry
		if(!$myClearance)
		{
			$pass = Database::query("REPLACE INTO friends_list (uni_id, friend_id, clearance) VALUES (?, ?, ?)", array($uniID, $friendID, 1));
		}
		else
		{
			// Set the new clearance level
			$nc = max(3, $myClearance);
			
			$pass = Database::query("UPDATE friends_list SET clearance=? WHERE uni_id=? AND friend_id=? LIMIT 1", array($nc, $uniID, $friendID));
		}
		
		if($pass)
		{
			// Update the friend counts
			if($pass = self::updateFriendCounts($uniID))
			{
				$pass = self::updateFriendCounts($friendID);
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Unfollow a User ******/
	public static function unfollow
	(
		$uniID			// <int> The UniID that's going to unfollow someone.
	,	$friendID		// <int> The UniID to unfollow.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::unfollow($uniID, $friendID);
	{
		// Get your clearance level with the target friend
		$myClearance = AppFriends::getClearance($uniID, $friendID);
		
		// Make sure you're actually following that person
		if(!$myClearance or $myClearance == 2)
		{
			return true;
		}
		
		// Begin the transaction
		$pass = true;
		
		Database::startTransaction();
		
		// Remove the friend's entry (if applicable)
		if($pass = Database::query("DELETE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($friendID, $uniID)))
		{
			// Remove the user's entry
			if($pass = Database::query("DELETE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $friendID)))
			{
				// Update the friend counts
				if($pass = self::updateFriendCounts($uniID))
				{
					$pass = self::updateFriendCounts($friendID);
				}
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Unfriend a User ******/
	public static function unfriend
	(
		$uniID			// <int> The UniID that's going to unfriend someone.
	,	$friendID		// <int> The UniID to unfriend.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::unfriend($uniID, $friendID);
	{
		// Get your clearance level with the target friend
		$myClearance = AppFriends::getClearance($uniID, $friendID);
		
		// Make sure you're actually friends with that person
		if($myClearance < 4)
		{
			return true;
		}
		
		// Begin the transaction
		Database::startTransaction();
		
		// Update the friend's entry
		if($pass = Database::query("DELETE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($friendID, $uniID)))
		{
			// Update the user's entry
			if($pass = Database::query("DELETE FROM friends_list WHERE uni_id=? AND friend_id=? LIMIT 1", array($uniID, $friendID)))
			{
				// Update the friend counts
				if($pass = self::updateFriendCounts($uniID))
				{
					$pass = self::updateFriendCounts($friendID);
				}
			}
		}
		
		if($pass)
		{
			// Prepare the packet with details about the friends to remove
			$packet = array(
				"uni_id" => $uniID
			,	"friend_id" => $friendID
			);
			
			// Run the API
			$pass = Connect::to("sync_friends", "RemoveFriendAPI", $packet);
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Get the number of friends and followers you have ******/
	public static function getFriendFollowCount
	(
		$uniID			// <int> The UniID to get the friend and follower count of.
	)					// RETURNS <int:int> an array of count values.
	
	// list($friends, $followers, $following) = AppFriends::getFriendFollowCount($uniID);
	{
		// Prepare Values
		$friends = 0;
		$followers = 0;
		$following = 0;
		
		// Get the full list of clearance
		$list = Database::selectMultiple("SELECT clearance FROM friends_list WHERE uni_id=?", array($uniID));
		
		foreach($list as $l)
		{
			// Add friends
			if($l['clearance'] >= 4) { $friends++; continue; }
			
			// Add followers
			if($l['clearance'] >= 2) { $followers++; }
			
			// Add following
			if($l['clearance'] != 2) { $following++; }
		}
		
		return array($friends, $followers, $following);
	}
	
	
/****** Update the friend counts for a user ******/
	public static function updateFriendCounts
	(
		$uniID			// <int> The UniID to update the proper friend and follower counts for.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::updateFriendCounts($uniID);
	{
		// Pull the count list
		list($friends, $followers, $following) = self::getFriendFollowCount($uniID);
		
		// Update the counts
		return Database::query("UPDATE social_data SET friends=?, followers=?, following=? WHERE uni_id=? LIMIT 1", array($friends, $followers, $following, $uniID));
	}
	
	
/****** Get the list of followers of a user ******/
	public static function getFollowerList
	(
		$uniID					// <int> The UniID of the user to find followers of.
	,	$page = 1				// <int> The row number to start at.
	,	$numRows = 20			// <int> The number of rows to return.
	,	$byEngagement = false	// <bool> Sort the list by engagement value.
	)							// RETURNS <int:[str:mixed]> the list of followers, array() on failure.
	
	// $followers = AppFriends::getFollowerList($uniID, [$page], [$numRows], [$byEngagement]);
	{
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name FROM friends_list as f INNER JOIN users as u ON f.friend_id = u.uni_id WHERE f.uni_id=? AND f.clearance IN (?, ?)" . ($byEngagement === true ? " ORDER BY f.engage_value DESC" : " ORDER BY u.handle") . " LIMIT " . (($page - 1) * $numRows) . ", " . ($numRows + 0), array($uniID, 2, 3));
	}
	
	
/****** Get the list of people the user is following ******/
	public static function getFollowingList
	(
		$uniID					// <int> The UniID of the user to find followers of.
	,	$page = 1				// <int> The row number to start at.
	,	$numRows = 20			// <int> The number of rows to return.
	,	$byEngagement = false	// <bool> Sort the list by engagement value.
	)							// RETURNS <int:[str:mixed]> the list of followers, array() on failure.
	
	// $followers = AppFriends::getFollowingList($uniID, [$page], [$numRows], [$byEngagement]);
	{
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name FROM friends_list as f INNER JOIN users as u ON f.friend_id = u.uni_id WHERE f.uni_id=? AND f.clearance IN (?, ?) " . ($byEngagement === true ? " ORDER BY f.engage_value DESC" : " ORDER BY u.handle") . " LIMIT " . (($page - 1) * $numRows) . ", " . ($numRows + 0), array($uniID, 1, 3));
	}
	
	
/****** Get the list of a user's friends ******/
	public static function getFriendList
	(
		$uniID					// <int> The UniID of the user to find friends of.
	,	$page = 1				// <int> The row number to start at.
	,	$numRows = 20			// <int> The number of rows to return.
	,	$byEngagement = false	// <bool> Sort the list by engagement value.
	)							// RETURNS <int:[str:mixed]> the list of friends, array() on failure.
	
	// $friends = AppFriends::getFriendList($uniID, [$page], [$numRows], [$byEngagement]);
	{
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name FROM friends_list as f INNER JOIN users as u ON f.friend_id = u.uni_id WHERE f.uni_id=? AND f.clearance >= ?" . ($byEngagement === true ? " ORDER BY f.engage_value DESC" : " ORDER BY u.handle") . " LIMIT " . (($page - 1) * $numRows) . ", " . ($numRows + 0), array($uniID, 4));
	}
	
	
/****** Check if UniID has already requested friendship with FriendID (the receiver of the request) ******/
	public static function getRequest
	(
		$uniID			// <int> The UniID of the user.
	,	$friendID		// <int> The UniID of the friend.
	)					// RETURNS <bool> TRUE if there was a request, FALSE if not
	
	// AppFriends::getRequest($uniID, $friendID);
	{
		return (bool) Database::selectOne("SELECT friend_id FROM friends_requests WHERE uni_id=? AND friend_id=? LIMIT 1", array($friendID, $uniID));
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
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name FROM friends_requests f INNER JOIN users u ON f.friend_id = u.uni_id WHERE f.uni_id=? LIMIT " . ($startPos + 0) . ", " . ($limit + 0), array($uniID));
	}
	
	
/****** Get a list of a user's sent friend requests ******/
	public static function getRequestSentList
	(
		$uniID			// <int> The UniID of the user to find friend requests they sent.
	,	$startPos = 0	// <int> The row number to start at.
	,	$limit = 20		// <int> The number of rows to return.
	)					// RETURNS <int:[str:mixed]> the list containing friend requests.
	
	// $requestsSent = AppFriends::getRequestSentList($uniID, [$startPos], [$limit]);
	{
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name FROM friends_requests f INNER JOIN users u ON f.uni_id = u.uni_id WHERE f.friend_id=? LIMIT " . ($startPos + 0) . ", " . ($limit + 0), array($uniID));
	}
	
	
/****** Send a friend request ******/
	public static function sendRequest
	(
		$uniID		// <int> The UniID that is requesting the friendship.
	,	$friendID	// <int> The Friend's UniID.
	)				// RETURNS <bool> TRUE if the request was sent properly, FALSE on failure.
	
	// AppFriends::sendRequest($uniID, $friendID);
	{
		// To send a request, you must be following the user
		AppFriends::follow($uniID, $friendID);
		
		// Make sure you're not already a friend
		$clearance = AppFriends::getClearance($uniID, $friendID);
		
		if($clearance >= 4)
		{
			return true;
		}
		
		// Check if the user has already sent a request
		if($check = self::getRequest($friendID, $uniID))
		{
			return true;
		}
		
		// Attempt to add the friend request
		if(!$check = Database::query("REPLACE INTO friends_requests (uni_id, friend_id, date_requested) VALUES (?, ?, ?)", array($friendID, $uniID, time())))
		{
			return false;
		}
		
		// Notify the friend that there is a friend request
		$userData = User::get($uniID, "handle, display_name");
		
		Notifications::create($friendID, URL::unifaction_social() . "/friends", "@" . $userData['handle'] . " has sent you a friend request.");
		
		return true;
	}
	
	
/****** Approve a Friend Request ******/
	public static function approve
	(
		$uniID			// <int> The UniID of the user that is approving the request.
	,	$friendID		// <int> the UniID of the friend that is receiving the new permissions.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFriends::approve($uniID, $friendID);
	{
		// Make sure a request was actually provided
		if(!$request = self::getRequest($friendID, $uniID))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Delete Friend Request
		if($pass = Database::query("DELETE FROM friends_requests WHERE (uni_id=? AND friend_id=?) or (uni_id=? AND friend_id=?) LIMIT 1", array($uniID, $friendID, $friendID, $uniID)))
		{
			// Update the Friend List
			if($pass = Database::query("UPDATE friends_list SET clearance=? WHERE uni_id=? AND friend_id=? LIMIT 1", array(4, $uniID, $friendID)))
			{
				if($pass = Database::query("UPDATE friends_list SET clearance=? WHERE uni_id=? AND friend_id=? LIMIT 1", array(4, $friendID, $uniID)))
				{
					// Update the friend counts
					if($pass = self::updateFriendCounts($uniID))
					{
						$pass = self::updateFriendCounts($friendID);
					}
				}
			}
		}
		
		if($pass)
		{
			// Prepare the packet with details about adding the friend (Friend Sync)
			$packet = array(
				"uni_id"		=> $uniID
			,	"friend_id"		=> $friendID
			);
			
			// Run the API
			$pass = Connect::to("sync_friends", "AddFriendAPI", $packet);
		}
		
		$success = Database::endTransaction($pass);
		
		// Notify the friend of the friend update
		if($success)
		{
			// Get data about the User
			if($userData = User::get($uniID, "handle, display_name"))
			{
				// Notify the friend that the request was approved
				Notifications::create($friendID, URL::unifaction_social() . "/" . $userData['handle'], "@" . $userData['handle'] . " has approved your friend request.");
			}
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
		Database::startTransaction();
		
		// Remove the friend request
		if($pass = Database::query("DELETE FROM friends_requests WHERE (uni_id=? AND friend_id=?) or (uni_id=? AND friend_id=?) LIMIT 1", array($uniID, $friendID, $friendID, $uniID)))
		{
			// Update the friend counts
			if($pass = self::updateFriendCounts($uniID))
			{
				$pass = self::updateFriendCounts($friendID);
			}
		}
		
		return Database::endTransaction($pass);
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
		if(!$uniID) { return false; }
		
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
		// End if the user is not a friend or follower
		if(!$clearance = AppFriends::getClearance($uniID, $friendID))
		{
			return false;
		}
		
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

