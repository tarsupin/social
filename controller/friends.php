<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends", "/");
}

// Get the social data
$social = new AppSocial(Me::$id);

// Prepare Values
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$resultsPerPage = 30;

// Get the list of friends
$friends = AppFriends::getFriendList(Me::$id, $currentPage, $resultsPerPage);

// Get the list of friend requests
$requests = AppFriends::getRequestList(Me::$id, 0, 4);

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

/****** Page Configuration ******/
$config['canonical'] = "/friends";
$config['pageTitle'] = "My Friends";		// Up to 70 characters. Use keywords.

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");


echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Add New Friends</div>
	<div class="inner-box" style="overflow:visible;">

<script>
function UserHandle(handle)
{
	window.location = "/friends/send-request?handle=" + handle;
}
</script>';

echo Search::searchBarUserHandle("userHandle", "", "search-add-user", "", "", "search", "Search for friends . . .") . "<br />";

echo '
	</div>
</div>';

// Display your friend requests, if applicable
if(count($requests) > 0)
{
	echo '
<div class="overwrap-box">
	<div class="overwrap-line">Friend Requests</div>
	<div class="inner-box">';
	
	if(count($requests) > 3)
	{
		array_pop($requests);
		
		$totalRequests = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM friends_requests WHERE uni_id=?", array(Me::$id));
		
		echo '
		<div style="margin-bottom:22px;"><a href="/friends/requests">View your full list of <span style="background-color:#ffcccc; padding:4px; border-radius:4px;">' . $totalRequests . ' friend requests</span>.</a></div>';
	}
	
	// Cycle through each friend request
	foreach($requests as $request)
	{
		echo '
		<div class="friend-block">
			<a href="/' . $request['handle'] . '"><img class="circimg" src="' . ProfilePic::image((int) $request['uni_id'], "medium") . '" /></a>
			<br />' . $request['display_name'] . '
			<br /><a ' . ($request['role'] != '' ? 'class="role-' . $request['role'] . '" ' : '') . 'href="/' . $request['handle'] . '">@' . $request['handle'] . '</a>
			<br /><br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("approve-friend-" . $request['handle']) . '">Approve</a>
			<br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("deny-friend-" . $request['handle']) . '">Deny</a>
		</div>';
	}
	
	echo '
	</div>
</div>';
}

// Display each of your friends
echo '
<div class="overwrap-box">
	<div class="overwrap-line">Friends List</div>
	<div class="inner-box">';

if(count($friends) > 0)
{
	foreach($friends as $friend)
	{
		echo '
		<div class="friend-block">
			<a href="/' . $friend['handle'] . '"><img class="circimg-large" src="' . ProfilePic::image((int) $friend['uni_id'], "large") . '" /></a>
			<br />' . $friend['display_name'] . '
			<br /><a ' . ($friend['role'] != '' ? 'class="role-' . $friend['role'] . '" ' : '') . 'href="/' . $friend['handle'] . '">@' . $friend['handle'] . '</a>
			<br /><br /><a href="/friends/edit?handle=' . $friend['handle'] . '"><span class="icon-pencil"></span> Edit</a>
		</div>';
	}
}
else
{
	echo "<p>There are no friends in your friends list currently.</p>";
}

echo '
	</div>
</div>';

// Prepare the pagination
$page = new Pagination((int) $social->data['friends'], $resultsPerPage, $currentPage);

if($page->highestPage > 1)
{
	echo '<div class="thread-tline" style="text-align:right;">Page: ';
	
	foreach($page->pages as $nextPage)
	{
		echo '<a class="thread-page' . ($nextPage == $page->currentPage ? ' thread-page-active' : '') . '" href="/friends?page=' . $nextPage . '"><span>' . $nextPage . '</span></a> ';
	}
	
	echo '</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
