<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends", "/");
}

// Prepare Values
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$resultsPerPage = 30;

// Total friend count
$friendCount = AppFriends::getFriendCount(Me::$id);

// Get the list of friends
$friends = AppFriends::getList(Me::$id, $currentPage, $resultsPerPage);

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
	window.location = "/friends/send-request?handle=" + handle;
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
			<br /><br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("approve-friend-" . $request['handle']) . '">Approve</a>
			<br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("deny-friend-" . $request['handle']) . '">Deny</a>
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
			<br /><br /><a href="/friends/edit?handle=' . $friend['handle'] . '"><span class="icon-pencil"></span> Edit</a>
		</div>';
	}
}
else
{
	echo "There are no friends in your friends list currently.";
}

// Prepare the pagination
$page = new Pagination($friendCount, $resultsPerPage, $currentPage);

if($page->highestPage > 1)
{
	echo '<div style="margin-top:12px;">Page: ';
	
	foreach($page->pages as $nextPage)
	{
		if($nextPage == $page->currentPage)
		{
			echo '<span style="padding:2px 4px 2px 4px; background-color:#c0c0c0;">' . $nextPage . '</span> ';
		}
		else
		{
			echo '<span><a href="/friends?page=' . $nextPage . '" style="padding:2px 4px 2px 4px; background-color:#e0e0e0;">' . $nextPage . '</a></span> ';
		}
	}
	
	echo '</div>';
}

echo '
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
