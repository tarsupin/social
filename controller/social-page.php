<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(You::$id == 0)
{
	header("Location: /" . (Me::$loggedIn ? Me::$vals['handle'] : ""));
	exit;
}
// Get the social data
$social = new AppSocial(You::$id);

// If you submitted a post
if(Form::submitted("social-uni6-post"))
{
	// If you submitted a post
	if(isset($_POST['post_message']))
	{
		// Make sure the post is within an acceptable limit
		$_POST['post_message'] = isset($_POST['post_message']) ? Security::purify($_POST['post_message']) : '';
		if(strlen($_POST['post_message']) < 1)
		{
			Alert::error("Post Length", "Please enter a message.");
		}
		elseif(strlen($_POST['post_message']) > 255)
		{
			Alert::error("Post Length", "Your post length may not exceed 255 characters.");
		}
		
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
		// Make sure the comment is within an acceptable limit
		$comment = isset($_POST['social_reply_text']) ? Security::purify($_POST['social_reply_text']) : '';
		if(strlen($comment) < 1)
		{
			Alert::error("Post Length", "Please enter a message.");
		}
		elseif(strlen($comment) > 255)
		{
			Alert::error("Post Length", "Your post length may not exceed 255 characters.");
		}
		
		if(FormValidate::pass())
		{
			// Identify the post being commented on (also confirms that it's this profile)
			if($postData = AppSocial::getPost(You::$id, $commentID))
			{
				$isPublic = $postData['clearance'] ? false : true;
				
				// Create the comment
				AppComment::create((int) $postData['id'], Me::$id, $comment, SITE_URL . "/" . You::$handle, You::$id, $isPublic);
			}
		}
		else
		{
			Alert::info("Content", "You were trying to post the following:<br/><textarea style='width:100%;'>" . htmlspecialchars($_POST['post_message']) . "</textarea>");
		}
	}
}

// If there is a delete action
else if($value = Link::clicked() and $value == "uni6-social")
{
	// Check if there is an action to run
	if(isset($_GET['action']))
	{
		// If you're following a user
		if($_GET['action'] == "follow" and Me::$id != You::$id)
		{
			if(AppFriends::follow(Me::$id, You::$id))
			{
				$social->clearance = max($social->clearance, 1);
				
				Alert::saveSuccess("Follow Success", "You have successfully followed " . You::$handle);
			}
		}
		
		// If you're unfollowing a user
		else if($_GET['action'] == "unfollow" and Me::$id != You::$id)
		{
			if(AppFriends::unfollow(Me::$id, You::$id))
			{
				$social->clearance = 0;
				
				Alert::saveSuccess("Unfollow Success", "You have successfully unfollowed " . You::$handle);
			}
		}
	}
	
	// Delete a post
	else if(isset($_GET['delete']))
	{
		$social->deletePost(You::$id, (int) $_GET['delete']);
	}
}

/****** Page Configuration ******/
$config['canonical'] = "/social-page";
$config['pageTitle'] = (Me::$id == You::$id ? "My" : You::$handle . '\'s') . " Wall";		// Up to 70 characters. Use keywords.

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
		<div id="social-name">@' . You::$handle . '</div>';
	
	// Friend, Follow, and My Page
	if(Me::$loggedIn)
	{
		if(Me::$clearance >= 6 && Me::$id != You::$id)	{ $clearance = AppFriends::getClearance(Me::$id, You::$id); }
		else					{ $clearance = $social->clearance; }
			
		if($clearance == 0 or $clearance == 2)
		{
			echo '
			<div id="follow-high" class="follow-button"><a href="/friends/send-request?handle=' . You::$handle . '">+ Friend</a></div>
			<div id="follow-low" class="follow-button"><a href="/' . You::$handle . '?action=follow&' . AppSocial::$linkProtect . '">+ Follow</a></div>';
		}
		else if($clearance < 4)
		{
			echo '
			<div id="follow-high" class="follow-button"><a href="/friends/send-request?handle=' . You::$handle . '">+ Friend</a></div>
			<div id="follow-low" class="follow-button clicked"><a href="/' . You::$handle . '?action=unfollow&' . AppSocial::$linkProtect . '">Following</a></div>';
		}
		else if($clearance < 8)
		{
			echo '
			<div id="follow-low" class="follow-button clicked"><a href="/friends/edit?handle=' . You::$handle . '">Friend</a></div>';
		}
	}
	
	echo '
	</div>
	
	<div id="personal-stats">
		<div id="personal-stats-left">
			<div class="stat-module"><div class="sm-top">' . $social->data['posts'] . '</div><div class="sm-bot">Posts</div></div>
			<div class="stat-module"><div class="sm-top">' . $social->data['friends'] . '</div><div class="sm-bot">Friends</div></div>
			<div class="stat-module hide-600"><div class="sm-top">' . $social->data['following'] . '</div><div class="sm-bot">Following</div></div>
			<div class="stat-module"><div class="sm-top">' . $social->data['followers'] . '</div><div class="sm-bot">Followers</div></div>
		</div>
	</div>
</div>';

if($social->canPost)
{
	echo '
<div class="overwrap-box">
<form class="uniform" action="/' . You::$handle . '" method="post">' . Form::prepare("social-uni6-post") . '
	<div id="post-top">' . UniMarkup::buttonLine() . '<div id="post-textwrap"><textarea id="core_text_box" name="post_message" maxlength="255" placeholder="Enter your ' . (Me::$id != You::$id ? 'message to ' . You::$name . ' ' : 'status ') . 'here...">' . (isset($_POST['post_message']) ? $_POST['post_message'] : '') . '</textarea></div></div>
	<div id="post-bottom">
		<div id="post-bottom-left">
			<a href="/post?gen=image"><span class="icon-image"></span></a>
			<a href="/post?gen=video"><span class="icon-video"></span></a>
		</div>
		<div id="post-bottom-right"><input type="button" value="Preview" onclick="previewPost();"/> ' . ($social->clearance<4 ? '' : '<input type="submit" name="submit_friends" value="Post to Friends" class="button" /> ') . '<input type="submit" name="submit_public" value="Public Post" class="button" /></div>
	</div>
	<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
</form>
</div>
';
}

// <a href="/post"><span class="icon-attachment"></span></a>

// Social Posts

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

if($postList != array())
{
	echo '
<div id="post-feed">';

$social->displayFeed($postList, $social->clearance);

echo '
</div>';
}

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
	getAjax("", "getComments", "commentReturn", "postID=" + postID, "user=" + user, "page=1");
}

function commentReturn(response)
{
	if(!response) { return; }
	
	obj = JSON.parse(response);
	console.log(obj);
	commentData = obj.commentData;
	page = obj.page;
	
	var a = document.getElementById("replies-" + obj.postID);
	
	var prepHTML = "";
	
	if(obj.hasmore == 1)
	{
		prepHTML += '<div class="thread-tline" style="margin:0px 22px 0px 44px;"><a href="javascript:getAjax(\'\', \'getComments\', \'commentReturn\', \'postID=' + obj.postID + '\', \'user=' + obj.user + '\', \'page=' + (page+1) + '\');">View Older Comments</a></div>';
	}

	for(var comment in commentData)
	{
		var c = commentData[comment];
		
		prepHTML += '<div style="margin-top:8px;"><div style="float:left; width:90px; text-align:right;"><img class="circimg-small" src="' + c.img + '" /></div><div style="margin-left:100px;">';
		
		var display = c.display_name.charAt(0).toLowerCase();
		var handle = c.handle.charAt(0).toLowerCase();
		if(display != handle)
		{
			prepHTML += '<span style="font-weight:bold">' + c.display_name + '</span>';
		}
		prepHTML +=' <a ';
		if(c.role != "")
		{
			prepHTML += 'class="role-' + c.role + '" ';
		}
		prepHTML += 'href="/' + c.handle + '">@' + c.handle + '</a><div class="comment-time-post">' + c.date_posted + '</div><br />' + c.comment + '</div></form></div><div style="clear:both;"></div>';
	}
	
	// Get the universal social reply form
	var b = document.getElementById("social_reply_box");
	var c = document.getElementById("social_reply_input");
	
	// Set the comments
	if(page == 1)
	{
		a.innerHTML = prepHTML;
		a.appendChild(b);
	}
	else
	{
		var old = a.getElementsByClassName("thread-tline");
		for(i=0; i<old.length; i++)
		{
			a.removeChild(old[i]);
		}
		a.innerHTML = prepHTML + a.innerHTML;		
	}	
	
	// Position the reply box
	c.value = obj.postID;
}

function previewPost()
{
	var text = encodeURIComponent(document.getElementById("core_text_box").value);
	getAjax("", "preview-post", "parse", "body=" + text);
}
function parse(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("preview").style.display = "block";
	document.getElementById("preview").innerHTML = response;
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