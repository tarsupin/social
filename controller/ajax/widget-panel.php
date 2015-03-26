<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$activeHashtag = isset($_POST['activeHashtag']) ? Sanitize::variable($_POST['activeHashtag'], '-') : '';

if($activeHashtag != '' && $userID = User::getIDByHandle($activeHashtag))
{
	echo AvatarWidget::display($userID, $activeHashtag);	
	echo FriendWidget::display($userID, $activeHashtag);
}

// Prepare the Featured Widget Data
/*$hashtag = "";
$categories = array("articles", "people");

// Create a new featured content widget
$featuredWidget = new FeaturedWidget($hashtag, $categories);

// If you want to display the FeaturedWidget by itself:
echo $featuredWidget->get();*/