<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$feedPosts = array();

if(Me::$loggedIn)
{
	// Update your Feed
	AppFeed::update(Me::$id);
	
	// Get the list of posts in your feed
	$feedPosts = AppFeed::get(Me::$id);
	
	// Set the active user to yourself
	You::$id = Me::$id;
	You::$handle = Me::$vals['handle'];
	
	// Get the social data
	$social = new AppSocial(Me::$id);
}

// Include Responsive Script
Photo::prepareResponsivePage();

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

if(!Me::$loggedIn)
{
	echo '
	<div class="post">
		<div class="post-header">
			<h2>Welcome to ' . $config['site-name'] . '!</h2>
			<p><a href="/login">Log in</a> and connect with other users!</p>
			<p>Visit the related sites:</p>
		</div>
	</div>';
}
else if(count($feedPosts) == 0)
{
	echo '
	<div class="post">
		<div class="post-header">
			<h2>Welcome to ' . $config['site-name'] . '!</h2>
			<p>This is your Social Feed!</p>
			<p>Your friends\' activities will be posted here as they do things.</p>
		</div>
	</div>';
}

// Display the feed
if($feedPosts)
{
	$social->displayFeed($feedPosts);
}

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
