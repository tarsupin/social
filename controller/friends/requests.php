<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends/requests", "/");
}

// Run the Request Updates
if(isset($_GET['friend']))
{
	// Get Friend Data
	if($friend = User::getDataByHandle($_GET['friend'], "uni_id, handle, display_name"))
	{
		// Recognize Integer
		$friend['uni_id'] = (int) $friend['uni_id'];
		
		// Get details about the friend request
		$clearance = AppFriends::getRequest(Me::$id, $friend['uni_id']);
		
		// Check if there were any updates run
		if($link = Link::clicked())
		{
			// If the friend request is being approved
			if($link == "approve-friend-" . $friend['handle'])
			{
				Alert::saveSuccess("Friend Approved", "You have approved " . $friend['handle'] . "'s friend request.");
				AppFriends::approve(Me::$id, $friend['uni_id']);
			}
			
			// If the friend request is being denied
			else if($link == "deny-friend-" . $friend['handle'])
			{
				Alert::saveSuccess("Friend Approved", "You have denied " . $friend['handle'] . "'s friend request.");
				AppFriends::deny(Me::$id, $friend['uni_id']);
			}
		}
	}
	else
	{
		Alert::error("Friend Invalid", "The friend selected is invalid.", 5);
	}
}

// Get the list of friend requests
$requests = AppFriends::getRequestList(Me::$id, 0, 20);

// If you have no friend requests, return to the last page
if(count($requests) == 0)
{
	header("Location: /friends"); exit;
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");


echo '
<div id="panel-right"></div>
<div id="content" class="content-open">' . Alert::display();

echo '
<style>
	.friend-block { display:inline-block; padding:12px; text-align:center; background-color:#eeeeee; border-radius:6px; }
</style>';

// Display your friend requests
echo '
<h3>Friend Requests</h3>';

// Cycle through each friend request
foreach($requests as $request)
{
	echo '
	<div class="friend-block">
		<img class="circimg" src="' . ProfilePic::image((int) $request['friend_id'], "medium") . '" />
		<br /><a href="' . URL::unifaction_social() . '">' . $request['display_name'] . '</a>
		<br /><a href="' . URL::fastchat_social() . '">@' . $request['handle'] . '</a>
		<br /><br /><a class="button" href="/friends/requests?friend=' . $request['handle'] . '&' . Link::prepare("approve-friend-" . $request['handle']) . '">Approve</a>
		<br /><a class="button" href="/friends/requests?friend=' . $request['handle'] . '&' . Link::prepare("deny-friend-" . $request['handle']) . '">Deny</a>
	</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
