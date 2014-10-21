<?php

// Make sure the appropriate data is sent
if(!isset($_GET['startPos']) or !isset($_GET['uniID']) or !Me::$loggedIn)
{
	exit;
}

// Recognize Integers
$_GET['uniID'] = (int) $_GET['uniID'];

// Get the social data for this page
if(!$socialPage = AppSocial::getPage($_GET['uniID']))
{
	exit;
}

// Determine Role
if(Me::$id == $_GET['uniID'])
{
	You::$id = Me::$id;
	You::$handle = Me::$vals['handle'];
	
	$viewClearance = 10;
	$interactClearance = 10;
}
else
{
	// Get the user data
	if($userData = User::get($_GET['uniID'], "uni_id, handle"))
	{
		You::$id = (int) $userData['uni_id'];
		You::$handle = $userData['handle'];
	}
	
	list($viewClearance, $interactClearance) = AppFriends::getClearance(Me::$id, $_GET['uniID']);
}

// Determine Permissions
$clearance = AppSocial::clearance(Me::$id, $viewClearance, $interactClearance, $socialPage);

// Display the social posts if allowed
if($clearance['access'])
{
	// Get the list of posts on that user's social page
	$socialPosts = AppSocial::getPostList((int) $_GET['uniID'], (int) $_GET['startPos']);
	
	echo AppSocial::displayFeed($socialPosts, $clearance);
}
