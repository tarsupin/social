<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

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
		$uniID			// <int> The uniID to find friends of.
	)					// RETURNS <str> the HTML for this widget.
	
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
			$social = new AppSocial($uniID);
			$friendCount = (int) $social->data['friends'];
			
			// Search through the list of friends
			$friendList = AppFriends::getFriendList($uniID, 1, $friendCount);
			
			$duration = $friendCount * $friendCount * 900; // 15 minutes * $friendCount^2
			
			Cache::set("friends:" . $uniID, json_encode(array($friendCount, $friendList)), $duration);
		}

		$len = count($friendList);
		
		/*// Make sure you have at least three friends to list
		if($len < 3) { return ''; }
		
		// If you have a number of friends that aren't divisible by three, reduce to nearest three
		while($len % 3 !== 0)
		{
			array_shift($friendList);
			$len--;
		}*/
		
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
			$html .= '<a href="/' . $friend['handle'] . '"><img src="' . ProfilePic::image((int) $friend['uni_id'], "large") . '" /></a>';
		}
		
		$html .= '
			</div>
		</div>';
		
		return $html;
	}
}