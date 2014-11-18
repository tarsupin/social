<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the appropriate information was provided
if(!isset($_POST['user']) or !isset($_POST['postID']))
{
	exit;
}

// Prepare Values
$postID = (int) $_POST['postID'];

if(!$uniID = User::getIDByHandle(Sanitize::variable($_POST['user'])))
{
	exit;
}

// Get the post data
if(!$postData = AppSocial::getPost($uniID, $postID))
{
	exit;
}

// Get the social data
$social = new AppSocial((int) $postData['poster_id']);

// Make sure you have clearance to view the poster's comments (and this comment)
if($social->canAccess)
{
	$comments = array();
	
	// Show Comments
	if($postData['has_comments'] > 0)
	{
		// Get Comments
		$comments = AppComment::getListAJAX($postData['id'], 1, 3, "DESC");
		
		foreach($comments as $key => $val)
		{
			$comments[$key]["img"] = ProfilePic::image((int) $comments[$key]['uni_id'], "small");
		}
		
		$comLen = count($comments);
		
		// Reverse the order (since you're providing the last three)
		if($comLen > 1)
		{
			$comments = array_reverse($comments);
		}
		
		// Provide option to show all comments
		if($postData['has_comments'] > $comLen)
		{
			// Provide an option to view all comments
			// echo 'view all comments';
		}
	}
	
	echo json_encode(array("postID" => $postID, "commentData" => $comments)); exit;
}