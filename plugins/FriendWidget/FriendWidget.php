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
	,	$handle			// <str> The handle of the user to find friends for.
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
		
		$html = '
		<!-- My Friends -->	
		<style>
			.chat-inner img { height:32px; }
		</style>
		<div class="chat-wrap">
			<div class="chat-header">
				<span class="icon-group"></span> ' . (Me::$id == $uniID ? 'My' : '@' . $handle . '\'s') . ' Friends (' . $friendCount . ')
			</div>
			<div class="chat-inner">';
			
		// Loop through each friend and add them to the list
		foreach($friendList as $friend)
		{
			$html .= '
				<a href="/' . $friend['handle'] . '"><img src="' . ProfilePic::image((int) $friend['uni_id']) . '" /></a>';
		}
		
		$html .= '
			</div>
		</div>';
		
		return $html;
	}
}