<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends", "/");
}

// Get the list of friends
$friends = AppFriends::getList(Me::$id);

// Get the list of friend requests
$requests = AppFriends::getRequestList(Me::$id, 0, 4);

// Prepare Values
$fastchatURL = URL::fastchat_social();

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
	.friend-block { display:inline-block; padding:12px; text-align:center; background-color:#eeeeee; border-radius:6px; font-size:0.8em; }
</style>

<script>
function UserHandle(handle)
{
	window.location = "/friends/send-request?friend=" + handle;
}
</script>

<div>
<h3>Add New Friends</h3>';

echo Search::searchBarUserHandle("userHandle", "", "search-add-user", "", "", "search", "Search for friends . . .") . "<br />";

// Display your friend requests, if applicable
if(count($requests) > 0)
{
	echo '
	<div style="margin-bottom:22px;">
	<h3>Friend Requests</h3>';
	
	if(count($requests) > 3)
	{
		array_pop($requests);
		
		$totalRequests = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM friend_requests WHERE uni_id=?", array(Me::$id));
		
		echo '
		<div style="margin-bottom:22px;"><a href="/friends/requests">View your full list of <span style="background-color:#ffcccc; padding:4px; border-radius:4px;">' . $totalRequests . ' friend requests</span>.</a></div>';
	}
	
	// Cycle through each friend request
	foreach($requests as $request)
	{
		echo '
		<div class="friend-block">
			<a href="/' . $request['handle'] . '"><img class="circimg" src="' . ProfilePic::image((int) $request['friend_id'], "medium") . '" /></a>
			<br />' . $request['display_name'] . '
			<br /><a href="' . $fastchatURL . '/' . $request['handle'] . '">@' . $request['handle'] . '</a>
			<br /><br /><a class="button" href="/friends/requests?friend=' . $request['handle'] . '&' . Link::prepare("approve-friend-" . $request['handle']) . '">Approve</a>
			<br /><a class="button" href="/friends/requests?friend=' . $request['handle'] . '&' . Link::prepare("deny-friend-" . $request['handle']) . '">Deny</a>
		</div>';
	}
	
	echo '
	</div>';
}

// Display each of your friends
echo '
<h3>Friends List</h3>';

if(count($friends) > 0)
{
	foreach($friends as $friend)
	{
		echo '
		<div class="friend-block">
			<a href="/' . $friend['handle'] . '"><img class="circimg-large" src="' . ProfilePic::image((int) $friend['friend_id'], "large") . '" /></a>
			<br />' . $friend['display_name'] . '
			<br /><a href="' . $fastchatURL . '/' . $friend['handle'] . '">@' . $friend['handle'] . '</a>
			<br /><br /><a href="/friends/edit?friend=' . $friend['handle'] . '"><span class="icon-pencil"></span> Edit</a>
		</div>';
	}
}
else
{
	echo "There are no friends in your friends list currently.";
}

echo '
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
