<?php

// Make sure the appropriate data is sent
if(!isset($_GET['startPos']) or !isset($_GET['uniID']) or !Me::$loggedIn)
{
	exit;
}

// Recognize Integers
$_GET['uniID'] = (int) $_GET['uniID'];

// Get the social data for this page
$socialPage = AppSocial::getPage($_GET['uniID']);

// Determine Role
if(Me::$id == $_GET['uniID'])
{
	$viewClearance = 10;
	$interactClearance = 10;
}
else
{
	list($viewClearance, $interactClearance) = Friends::getClearance(Me::$id, $_GET['uniID']);
}

// Determine Permissions
$clearance = AppSocial::clearance(Me::$id, $viewClearance, $interactClearance, $socialPage);

// Display the social posts if allowed
if($clearance['access'])
{
	// Get the list of posts on that user's social page
	$socialPosts = AppSocial::getPostList((int) $_GET['uniID'], (int) $_GET['startPos']);
	
	echo AppSocial::displayFeed($socialPosts);
}
