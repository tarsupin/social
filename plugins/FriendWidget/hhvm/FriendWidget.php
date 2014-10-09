<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the FriendWidget Plugin ------
-----------------------------------------

This plugin creates a friend box widget that appears when you visit someone's page. It will show you their active friends, and attempt to show you friends that are also associated with you, that you might know, or that you might be interested in knowing.


-------------------------------
------ Methods Available ------
-------------------------------

FriendWidget::display();

*/

abstract class FriendWidget {
	
	
/****** Display the Widget ******/
	public static function display
	(
		int $uniID			// <int> The uniID to find friends of.
	): string					// RETURNS <str> the HTML for this widget.
	
	// FriendWidget::display($uniID);
	{
		// Get list of friends
		if($friendData = Cache::get("friends:" . $uniID))
		{
			list($friendCount, $friendList) = json_decode($friendData, true);
		}
		else
		{
			// Get a Friend Count
			$friendCount = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM users_friends WHERE uni_id=?", array($uniID));
			
			// Search through the list of friends
			$friendList = Database::selectMultiple("SELECT fl.friend_id as id, u.handle FROM users_friends fl INNER JOIN users u ON fl.friend_id=u.uni_id WHERE fl.uni_id=? ORDER BY RAND() LIMIT 9", array($uniID));
			
			$duration = count($friendList) * count($friendList) * 900; // 15 minutes * $friendCount^2
			
			Cache::set("friends:" . $uniID, json_encode(array($friendCount, $friendList)), $duration);
		}
		
		$len = count($friendList);
		
		// Make sure you have at least three friends to list
		if($len < 3) { return ''; }
		
		// If you have a number of friends that aren't divisible by three, reduce to nearest three
		while($len % 3 !== 0)
		{
			array_shift($friendList);
		}
		
		$html = '
		<!-- My Friends -->
		<div class="side-module">
			<div class="side-header">
				<span class="icon-group"></span> <a href="/friends">Friends (' . $friendCount . ')</a>
			</div>
			<div class="side-photos">';
			
		// Loop through each friend and add them to the list
		foreach($friendList as $friend)
		{
			$html .= '<a href="/' . $friend['handle'] . '"><img src="' . ProfilePic::image((int) $friend['id'], "large") . '" /></a>';
		}
		
		$html .= '
			</div>
		</div>';
		
		return $html;
	}
}