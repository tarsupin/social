<?php

// Make sure the appropriate data is sent
if(!isset($_GET['startPos']) or !isset($_GET['uniID']) or !Me::$loggedIn)
{
	exit;
}

$social = new AppSocial((int) $_GET['uniID']);

// Get the post list
$postList = $social->getUserPosts($social->uniID, $social->clearance, (int) $_GET['startPos']);

// Display the social feed (infinite scroll)
$social->displayFeed($postList);