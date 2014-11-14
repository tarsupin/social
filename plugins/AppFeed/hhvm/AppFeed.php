<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppFeed Plugin ------
-----------------------------------------

This plugin generates and provides a social feed to the users.


-------------------------------
------ Methods Available ------
-------------------------------

AppFeed::update($uniID);

$feedList = AppFeed::get($uniID);

$lastFeedUpdate = AppFeed::lastUpdate($uniID);

*/

abstract class AppFeed {
	
	
/****** Update The User's Feed ******/
	public static function update
	(
		int $uniID			// <int> The Uni-ID to return feeds to.
	): void					// RETURNS <void> PREPARES the list of feeds for the user routinely.
	
	// AppFeed::update($uniID);
	{
		// Check the last time that you updated your feed
		$lastUpdate = self::lastUpdate($uniID);
		
		// If you haven't updated your feed for at least 15 minutes (900 seconds)
		if(time() > ($lastUpdate + 900))
		{
			/*
				Note: Eventually we'll want to get more clever with posts. Find associations between
				the users you click on the most, the engagement of the posts, the duration since the
				post, etc. For now, we'll keep it simple.
			*/
			
			// Prepare Values
			$timestamp = time();
			$lastWeek = $timestamp - (3600 * 24 * 7);
			$postList = array();
			$postsScanned = 0;
			
			// Set the last update to now
			Database::query("REPLACE INTO `social_feed_last_update` (uni_id, date_lastUpdate) VALUES (?, ?)", array($uniID, $timestamp));
			
			// Cycle through friends, favored by friend engagement and recent activity
			// Then cycle through posts, determine post engagement
			
			// Get your friends (in order of engagement value)
			$friends = Database::selectMultiple("SELECT friend_id, engage_value FROM friends_list WHERE uni_id=? ORDER BY engage_value DESC LIMIT 500", array($uniID));
			
			$friendCount = count($friends);
			$limitScan = ($friendCount < 10 ? 5 : ($friendCount < 50 ? 4 : ($friendCount < 100 ? 3 : 2)));
			
			foreach($friends as $friend)
			{
				// Get the friends most recent posts
				$posts = Database::selectMultiple("SELECT * FROM social_posts_user spu INNER JOIN social_posts sp ON spu.id=sp.id WHERE spu.uni_id=? AND sp.poster_id=? ORDER BY sp.id DESC LIMIT " . $limitScan, array((int) $friend['friend_id'], (int) $friend['friend_id']));
				
				foreach($posts as $post)
				{
					$perBoost = max(0, (100 - (($timestamp - (int) $post['date_posted']) / 1000)) + mt_rand(0, mt_rand(10, 40))) / 100;
					
					$postEngage = 25 + ((int) $friend['engage_value'] / 4);
					
					if((int) $post['attachment_id'] != 0)
					{
						// Determine the type of attachment (generally an image)
						
						// Then add engagement value as necessary
						$postEngage += 20;
					}
					
					$postEngage += ((int) $post['has_comments'] * 8);
					
					$postEngage = ($postEngage * $perBoost);
					
					$postList[(int) $post['id']] = $postEngage;
				}
				
				// If you've scanned more than 100 posts, end here
				if($postsScanned++ >= 100) { break; }
			}
			
			arsort($postList);
			
			Database::startTransaction();
			
			// Destroy the user's current social feed
			Database::query("DELETE FROM social_feed WHERE uni_id=?", array($uniID));
			
			// Build the new social feed
			foreach($postList as $key => $value)
			{
				Database::query("INSERT INTO social_feed (uni_id, engage_value, post_id) VALUES (?, ?, ?)", array($uniID, $value, $key));
			}
			
			Database::endTransaction();
		}
	}
	
	
/****** Retrieve a list of posts from your feed ******/
	public static function get
	(
		int $uniID			// <int> The Uni-ID to get the feed for.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> a list of posts from your feed.
	
	// $posts = AppFeed::get($uniID);
	{
		return Database::selectMultiple("SELECT p.*, u.display_name FROM social_feed f INNER JOIN social_posts p ON p.id = f.post_id INNER JOIN users as u ON p.poster_id = u.uni_id WHERE f.uni_id=? ORDER BY engage_value DESC", array($uniID));
	}
	
	
/****** Check the last time the feed was updated ******/
	public static function lastUpdate
	(
		int $uniID			// <int> The Uni-ID to check the last feed update was at.
	): int					// RETURNS <int> the timestamp of your last update, or 0 if never.
	
	// $lastUpdate = AppFeed::lastUpdate($uniID);
	{
		return (int) Database::selectValue("SELECT date_lastUpdate FROM social_feed_last_update WHERE uni_id=? LIMIT 1", array($uniID));
	}
}