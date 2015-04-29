<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$feedPosts = array();

if(Me::$loggedIn)
{
	// Update your Feed
	AppFeed::update(Me::$id);
	
	// Set the active user to yourself
	You::$id = Me::$id;
	You::$handle = Me::$vals['handle'];
	
	// Get the social data
	$social = new AppSocial(Me::$id);
	
	// Get the list of posts in your feed
	$feedPosts = AppFeed::get(Me::$id, (int) $social->data['feed_sort']);
	
	// If you submitted a comment
	if(Form::submitted("social-reply-box") and isset($_POST['social_reply_text']) and isset($_POST['social_reply_input']))
	{
		// Get the ID of the post
		$postID = (int) $_POST['social_reply_input'];
		
		// Get the post Data
		$postData = AppSocial::getPostDirect($postID);
		
		// Prepare a social connection
		$fSocial = new AppSocial((int) $postData['poster_id']);
		
		if($fSocial->canComment)
		{
			// Prepare Values
			$comment = Sanitize::text($_POST['social_reply_text'], "/~");
			
			// Make sure the comment is within an acceptable limit
			$comment = substr($comment, 0, 600);
			
			if(FormValidate::pass())
			{
				$isPublic = $postData['clearance'] ? false : true;
				
				// Create the comment
				AppComment::create((int) $postData['id'], Me::$id, $comment, SITE_URL . "/" . $postData['handle'], (int) $postData['poster_id'], $isPublic);
			}
		}
	}
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
	echo '
<div id="post-feed">';
	$social->displayFeed($feedPosts);
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
	getAjax("", "get-comments", "commentReturn", "postID=" + postID, "user=" + user);
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
		prepHTML += '<div class="thread-tline" style="margin:0px 22px 0px 44px;"><a href="javascript:getAjax(\'\', \'get-comments\', \'commentReturn\', \'postID=' + obj.postID + '\', \'user=' + obj.user + '\', \'page=' + (page+1) + '\');">View Older Comments</a></div>';
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
</script>

<?php

// Prepare the social reply box
echo '
<div id="conceal_reply_box" style="display:none;">
<div id="social_reply_box" style="margin-top:8px;">
<form class="uniform" method="post" value="/">' . Form::prepare("social-reply-box") . '
	<input id="social_reply_input" type="hidden" name="social_reply_input" value="0" />
	<div style="float:left; width:90px; text-align:right;"><img class="circimg-small" src="' . ProfilePic::image(Me::$id) . '" /></div>
	<div style="margin-left:100px;">
		<textarea name="social_reply_text" placeholder="Enter your comment here..." maxlength="600" style="width:98%; height:48px;"></textarea>
		<div><input type="submit" name="submit" value="Post Reply" /></div>
	</div>
</form>
</div>
</div>';

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
