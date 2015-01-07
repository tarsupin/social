<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require the user to log in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/followers", "/");
}

/****** Page Configuration ******/
$config['canonical'] = "/followers";
$config['pageTitle'] = "My Followers";		// Up to 70 characters. Use keywords.

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Display the Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">My Followers</div>
	<div class="inner-box">';

// Run the auto-scrolling script
echo '
<script>
	urlToLoad = "/ajax/follower-loader";
	elementIDToAutoScroll = "follower-list";
	startPos = 1;
	entriesToReturn = 30;
	maxEntriesAllowed = 300;
	waitDuration = 1200;
	appendURL = "&uniID=' . Me::$id . '";
	
	function afterAutoScroll() {
		// picturefill();
	}
</script>';

// People who are following me
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$followList = AppFriends::getFollowerList(Me::$id, $currentPage, 20);

echo '
<div id="follower-list">';

// Cycle through the list
foreach($followList as $userData)
{
	$userData['uni_id'] = (int) $userData['uni_id'];
	
	echo '
	<div class="friend-block"><a href="/' . $userData['handle'] . '"><img class="circimg-large" src="' . ProfilePic::image($userData['uni_id'], "large") . '" /></a><br />' . $userData['display_name'] . '<br /><a href="/' . $userData['handle'] . '">@' . $userData['handle'] . '</a></div>';
}

echo '
</div>
	</div>
</div>';

// Prepare the pagination
$social = new AppSocial(Me::$id);
$page = new Pagination((int) $social->data['followers'], 20, $currentPage);
if($page->highestPage > 1)
{
	echo '<div class="thread-tline" style="text-align:right;">Page: ';
	
	foreach($page->pages as $nextPage)
	{
		echo '<a class="thread-page' . ($nextPage == $page->currentPage ? ' thread-page-active' : '') . '" href="/friends/followers?page=' . $nextPage . '"><span>' . $nextPage . '</span></a> ';
	}
	
	echo '</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
