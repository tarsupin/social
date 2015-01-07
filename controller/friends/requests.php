<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends/requests", "/");
}

// Run the Request Updates
if(isset($_GET['handle']))
{
	// Get Friend Data
	if($friend = User::getDataByHandle($_GET['handle'], "uni_id, handle, display_name"))
	{
		// Recognize Integer
		$friend['uni_id'] = (int) $friend['uni_id'];
		
		// Check if there were any updates run
		if($link = Link::clicked())
		{
			// If the friend request is being approved
			if($link == "approve-friend-" . $friend['handle'])
			{
				if(AppFriends::approve(Me::$id, $friend['uni_id']))
				{
					Alert::success("Friend Approved", "You have approved " . $friend['handle'] . "'s friend request.");
				}
				else
				{
					Alert::error("Friend Failure", "There was an error while trying to approve " . $friend['handle'] . "'s friend request.");
				}
			}
			
			// If the friend request is being denied
			else if($link == "deny-friend-" . $friend['handle'])
			{
				if(AppFriends::deny(Me::$id, $friend['uni_id']))
				{
					Alert::success("Friend Approved", "You have denied " . $friend['handle'] . "'s friend request.");
				}
			}
		}
	}
	else
	{
		Alert::error("Friend Invalid", "The friend selected is invalid.", 5);
	}
}

// Get the list of friend requests
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$requests = AppFriends::getRequestList(Me::$id, $currentPage-1, 20);

// Get the list of friend requests sent out
$requestsSent = AppFriends::getRequestSentList(Me::$id, $currentPage-1, 20);

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

/****** Page Configuration ******/
$config['canonical'] = "/friends/requests";
$config['pageTitle'] = "Friend Requests";		// Up to 70 characters. Use keywords.

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
	<div class="overwrap-line">Friend Requests</div>
	<div class="inner-box">';

// Display your friend requests
if(count($requests) == 0)
{
	echo '<p>There are no friend requests open right now.</p>';
}

// Cycle through each friend request
foreach($requests as $request)
{
	echo '
	<div class="friend-block">
		<a href="/' . $request['handle'] . '"><img class="circimg" src="' . ProfilePic::image((int) $request['uni_id'], "medium") . '" /></a>
		<br /><a href="/' . $request['handle'] . '">' . $request['display_name'] . '</a>
		<br /><a href="' . URL::unifaction_social() . '/' . $request['handle'] . '">@' . $request['handle'] . '</a>
		<br /><br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("approve-friend-" . $request['handle']) . '">Approve</a>
		<br /><a class="button" href="/friends/requests?handle=' . $request['handle'] . '&' . Link::prepare("deny-friend-" . $request['handle']) . '">Deny</a>
	</div>';
}

echo '
	</div>
</div>';

// Display your friend requests sent out
echo '
<div class="overwrap-box">
	<div class="overwrap-line">Your Pending Friend Requests</div>
	<div class="inner-box">';

if(count($requestsSent) == 0)
{
	echo '<p>There are currently no sent friend requests waiting on responses.</p>';
}

// Cycle through each friend request sent
foreach($requestsSent as $request)
{
	echo '
	<div class="friend-block">
		<a href="/' . $request['handle'] . '"><img class="circimg" src="' . ProfilePic::image((int) $request['uni_id'], "medium") . '" /></a>
		<br /><a href="/' . $request['handle'] . '">' . $request['display_name'] . '</a>
		<br /><a href="' . URL::unifaction_social() . '/' . $request['handle'] . '">@' . $request['handle'] . '</a>
	</div>';
}

echo '
	</div>
</div>';

// Prepare the pagination
$totalRequests = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM friends_requests WHERE uni_id=?", array(Me::$id));
$page = new Pagination($totalRequests, 20, $currentPage);

if($page->highestPage > 1)
{
	echo '<div class="thread-tline" style="text-align:right;">Page: ';
	
	foreach($page->pages as $nextPage)
	{
		echo '<a class="thread-page' . ($nextPage == $page->currentPage ? ' thread-page-active' : '') . '" href="/friends/requests?page=' . $nextPage . '"><span>' . $nextPage . '</span></a> ';
	}
	
	echo '</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
