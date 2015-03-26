<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AvatarWidget Plugin ------
-----------------------------------------

This plugin creates an avatar box widget that appears when you visit someone's page, providing they have selected an actual avatar (not the profile picture) to display.


-------------------------------
------ Methods Available ------
-------------------------------

AvatarWidget::display();

*/

abstract class AvatarWidget {
	
	
/****** Display the Widget ******/
	public static function display
	(
		$uniID			// <int> The uniID to display the avatar of.
	,	$handle			// <str> The handle of the user to display the avatar of.
	)					// RETURNS <str> the HTML for this widget.
	
	// AvatarWidget::display($uniID);
	{
		$html = '';
		
		if($avi = User::get($uniID, "avatar_opt"))
		{
			$html .= '
		<!-- My Avatar -->
		<div class="chat-wrap">
			<div class="chat-header">
				<span class="icon-user"></span> ' . (Me::$id == $uniID ? 'My' : '@' . $handle . '\'s') . ' Avatar
			</div>
			<div class="widget-inner" style="text-align:center;">
				' . ((int) $avi['avatar_opt'] > 0 ? '<img src="' . Avatar::image($uniID, (int) $avi['avatar_opt']) . '"/>' : (Me::$id == $uniID ? 'Would you like to display one of your avatars here? You can do so via the <a href="/settings">settings</a>!' : '@' . $handle . ' has not set an avatar.')) . '
			</div>
		</div>';
		}
		
		return $html;
	}
}