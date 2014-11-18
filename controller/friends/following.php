<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require the user to log in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/following", "/");
}

/****** Page Configuration ******/
$config['canonical'] = "/following";
$config['pageTitle'] = "Social Following";		// Up to 70 characters. Use keywords.

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
<div id="content" class="content-open">' . Alert::display();

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

echo '
<style>
.follower { display:inline-block; padding:4px; text-align:center; font-size:0.8em; }
</style>

<div id="follower-list">';

// People who I'm following
$followList = AppFriends::getFollowingList(Me::$id);

echo '
<h3>People I\'m Following</h3>';

// Cycle through the list
foreach($followList as $userData)
{
	$userData['uni_id'] = (int) $userData['uni_id'];
	
	echo '
	<div class="follower"><a href="/' . $userData['handle'] . '"><img src="' . ProfilePic::image($userData['uni_id'], "large") . '" /></a><br />@' . $userData['handle'] . '</div>';
}

echo '
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
