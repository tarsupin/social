<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Get the social data
$social = new AppSocial(You::$id);

// If you submitted a post
if(Form::submitted("social-uni6-post"))
{
	// If you submitted a post
	if(isset($_POST['post_message']))
	{
		// Make sure the post is within an acceptable limit
		FormValidate::text("Post", $_POST['post_message'], 1, 255, chr(13));
		
		// Make sure you have posting privileges
		if(!$social->canPost)
		{
			Alert::error("Invalid Permissions", "You need higher permissions to post on this page.");
		}
		
		if(FormValidate::pass())
		{
			// Set the Clearance Level
			$clearance = isset($_POST['submit_public']) ? 0 : 4;
			
			// Create the Post
			$postID = AppSocial::createPost(You::$id, Me::$id, $clearance, 0, $_POST['post_message'], URL::unifaction_social() . "/" . You::$handle);
		}
	}
}

// If you submitted a comment
else if(Form::submitted("social-reply-box"))
{
	if($social->canComment and isset($_POST['social_reply_text']) and isset($_POST['social_reply_input']))
	{
		// Prepare Values
		$commentID = (int) $_POST['social_reply_input'];
		$comment = Sanitize::text($_POST['social_reply_text'], "/'?");
		
		// Make sure the comment is within an acceptable limit
		$comment = substr($comment, 0, 255);
		
		if(FormValidate::pass())
		{
			// Identify the post being commented on (also confirms that it's this profile)
			if($postData = AppSocial::getPost(You::$id, $commentID))
			{
				$isPublic = $postData['clearance'] ? false : true;
				
				// Create the comment
				AppComment::create((int) $postData['id'], Me::$id, $comment, "/" . You::$handle, You::$id, $isPublic);
			}
		}
	}
}

// If there is a delete action
else if(isset($_GET['delete']))
{
	$social->deletePost(You::$id, (int) $_GET['delete']);
}

// Include Responsive Script
Photo::prepareResponsivePage();

// Load the auto-scroller
Metadata::addHeader('<script src="' . CDN . '/scripts/autoscroll.js"></script>');

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

// The Photo Display (Top Bar)
if($social->data['has_headerPhoto'] == 1)
{
	$urlPath = AppSocial::headerPhoto(You::$id, You::$handle);
	
	$headPhoto = $urlPath['imageDir'] . '/' . $urlPath['mainDir'] . '/' . $urlPath['filename'] . '.' . $urlPath['ext'];
}
else
{
	$headPhoto = "/assets/images/empty_header.png";
}

echo '
<div id="social-wrap">
	<div id="social-header">
		<img id="social-header-img" src="' . $headPhoto . '" />
		<div id="social-propic"><img src="' . ProfilePic::image(You::$id, $size = "large") . '" /></div>
		<div id="social-name">@' . You::$handle . '</div>
	</div>
	
	<div id="personal-stats">
		<div id="personal-stats-left">
			<div class="stat-module"><div class="sm-top">' . $social->data['posts'] . '</div><div class="sm-bot">Posts</div></div>
			<div class="stat-module"><div class="sm-top">' . $social->data['friends'] . '</div><div class="sm-bot">Friends</div></div>
			<div class="stat-module"><div class="sm-top">' . $social->data['following'] . '</div><div class="sm-bot">Following</div></div>
			<div class="stat-module"><div class="sm-top">' . $social->data['followers'] . '</div><div class="sm-bot">Followers</div></div>
		</div>
	</div>
</div>

<form class="uniform" action="/' . You::$handle . '" method="post">' . Form::prepare("social-uni6-post") . '
<div id="post-box">
	<div id="post-left"><span class="icon-pencil"></span><br />New Post</div>
	<div id="post-right">
		<div id="post-top"><div id="post-textwrap"><textarea name="post_message" placeholder="Create a post . . ."></textarea></div></div>
		<div id="post-bottom">
			<div id="post-bottom-left">
				<a href="/post"><span class="icon-image"></span></a>
				<a href="/post"><span class="icon-video"></span></a>
				<a href="/post"><span class="icon-attachment"></span></a>
			</div>
			<div id="post-bottom-right"><input type="submit" name="submit_friends" value="Post to Friends" class="button" /> <input type="submit" name="submit_public" value="Public Post" class="button" /></div>
		</div>
	</div>
</div>
</form>
';

// Social Posts
echo '
<div id="post-feed">';

// Run the auto-scrolling script
echo '
<script>
	urlToLoad = "/ajax/wall-loader";
	elementIDToAutoScroll = "post-feed";
	startPos = 2;
	entriesToReturn = 1;
	maxEntriesAllowed = 15;
	waitDuration = 1200;
	appendURL = "&uniID=' . You::$id . '";
	
	function afterAutoScroll() {
		// picturefill();
	}
</script>';

// Get the post list
$postList = $social->getUserPosts($social->uniID, $social->clearance);

$social->displayFeed($postList, $social->clearance);

echo '
</div>';

?>

<script>
function positionReplyBox(user, postID)
{
	// Get the reply box
	var a = document.getElementById("replies-" + postID);
	
	if(a.style.display == "none")
	{
		a.style.display = "block";
	}
	else
	{
		a.style.display = "none";
		
		// Reset the universal social reply form
		var b = document.getElementById("social_reply_box");
		if(b) { document.getElementById('conceal_reply_box').appendChild(b); }
		
		return;
	}
	
	// Pull the comments for a specific post
	getAjax("", "getComments", "commentReturn", "postID=" + postID, "user=" + user);
}

function commentReturn(response)
{
	if(!response) { return; }
	
	obj = JSON.parse(response);
	console.log(obj);
	commentData = obj.commentData;
	
	var a = document.getElementById("replies-" + obj.postID);
	
	// Check if there is any content
	checkFull = a.innerHTML;
	
	var prepHTML = "";
	
	for(var comment in commentData)
	{
		var c = commentData[comment];
		
		prepHTML += '<div style="margin-top:8px;"><div style="float:left; width:90px; text-align:right;"><img class="circimg-small" src="' + c.img + '" /></div><div style="margin-left:100px;"><span style="font-weight:bold">' + c.display_name + '</span> <a href="/' + c.handle + '">@' + c.handle + '</a><br />' + c.comment + '</div></form></div><div style="clear:both;"></div>';
	}
	
	// Set the comments
	a.innerHTML = prepHTML;
	
	// Get the universal social reply form
	var b = document.getElementById("social_reply_box");
	var c = document.getElementById("social_reply_input");
	
	// Position the reply box
	a.appendChild(b);
	c.value = obj.postID;
}
</script>

<?php

// Prepare the social reply box
echo '
<div id="conceal_reply_box" style="display:none;">
<div id="social_reply_box" style="margin-top:8px;">
<form class="uniform" method="post" value="/' . You::$handle . '">' . Form::prepare("social-reply-box") . '
	<input id="social_reply_input" type="hidden" name="social_reply_input" value="0" />
	<div style="float:left; width:90px; text-align:right;"><img class="circimg-small" src="' . ProfilePic::image(Me::$id) . '" /></div>
	<div style="margin-left:100px;">
		<textarea name="social_reply_text" placeholder="Enter your comment here..." maxlength="255" style="width:98%; height:48px;"></textarea>
		<div><input type="submit" name="submit" value="Post Reply" /></div>
	</div>
</form>
</div>
</div>';

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");