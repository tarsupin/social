<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// If the profile was not loaded properly
if(!You::$id)
{
	// If the profile cannot exist, redirect 404
	header("Location: /404.php"); exit;
}

// My Friends List
/*
if(You::$id)
{
	WidgetLoader::add("SidePanel", 50, FriendWidget::display(You::$id));
	WidgetLoader::add("SidePanel", 60, SharedContentWidget::display(You::$id, You::$handle));
}
*/

// Get the social data for this page
$socialPage = AppSocial::getPage(You::$id);

// Determine Role
if(Me::$id == You::$id)
{
	$viewClearance = 10;
	$interactClearance = 10;
}
else
{
	// Get the View and Interact clearance with the other user
	list($viewClearance, $interactClearance) = AppFriends::getClearance(Me::$id, You::$id);
	
	// Track engagement with this user (view rate)
	AppFriends::trackEngagement(Me::$id, You::$id, 1);
}

// Determine Permissions
$clearance = AppSocial::clearance(Me::$id, $viewClearance, $interactClearance, $socialPage);

// Delete a Post
if($getData = Link::getData("delete-post") and is_array($getData) and isset($getData[0]))
{
	AppSocial::deletePost(You::$id, (int) $getData[0]);
}

// If you submitted content (post or comment)
if(Form::submitted("social-post"))
{
	// If you submitted a post
	if(isset($_POST['mainPostBox']) && isset($clearance['post']))
	{
		// Make sure the post is within an acceptable limit
		FormValidate::text("Post", $_POST['mainPostBox'], 1, 1000);
		
		if(FormValidate::pass())
		{
			// Create the Post
			$postID = AppSocial::createPost(You::$id, Me::$id, 0, $_POST['mainPostBox'], URL::unifaction_social() . "/" . You::$handle);
		}
	}
	
	// If you submitted a comment
	else if(isset($_POST['commentBox']) && isset($clearance['comment']) && count($_POST['commentBox']) == 1)
	{
		$commentID = key($_POST['commentBox']);
		$comment = $_POST['commentBox'][$commentID];
		
		// Make sure the comment is within an acceptable limit
		FormValidate::text("Comment", $comment, 1, 600);
		
		if(FormValidate::pass())
		{
			// Identify the post being commented on (also confirms that it's this profile)
			if($postData = AppSocial::getPost(You::$id, $commentID))
			{
				// Create the comment
				AppComment::create((int) $postData['id'], Me::$id, $comment, "/test", You::$id);
				// URL::unifaction_social() . "/" . You::$handle
			}
		}
	}
}

// Run Tip Exchanges
if($getData = Link::getData("send-tip-social") and is_array($getData) and isset($getData[0]))
{
	// Get the user from the post
	Credits::tip(Me::$id, (int) $getData[0]);
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

// Load the Page
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

// The Photo Display (Top Bar)
if($socialPage['has_headerPhoto'] == 1)
{
	$urlPath = AppSocial::headerPhoto(You::$id, You::$handle);
	
	$headPhoto = $urlPath['imageDir'] . '/' . $urlPath['mainDir'] . '/' . $urlPath['secondDir'] . '/' . $urlPath['filename'] . '.' . $urlPath['ext'];
}
else
{
	$headPhoto = "/assets/images/empty_header.png";
}

echo '
<div id="top-bar">
	<img id="top-bar-img" src="' . $headPhoto . '" />
	<div id="top-avi-wrap">
		<img id="top-propic" src="' . ProfilePic::image(You::$id, $size = "large") . '" />
	</div>
	<div id="top-name">@' . You::$handle . '</div>';
	
	if($viewClearance < 2)
	{
		echo '
		<div id="top-connect"><a href="/friends/send-request?friend=' . You::$handle . '" class="button"><span class="icon-user"></span> &nbsp; Add Friend</a></div>';
	}
	
echo '
</div>';

// Show the ability to post
if(isset($clearance['post']))
{
	echo '
	<div class="post" style="margin-top:10px;">
		<div class="post-header">
			<div><span class="post-icon icon-edit"></span> &nbsp; Post on <a href="#" style="">' . You::$name . '\'s</a> Social Profile</div>
			<div><span class="post-icon icon-calendar"></span>  &nbsp; ' . date("M jS") . '</div>
		</div>
		<div style="margin-bottom:8px;">
			<form class="uniform" id="main_post_form" action="/' . You::$handle . '" method="post">' . Form::prepare("social-post") . '
				<img class="circimg-small" src="' . ProfilePic::image(Me::$id) . '" style="float:left; margin:10px 5px 10px 20px;" />
				<p class="comment-box-wrap">
					<textarea class="comment-box" name="mainPostBox" placeholder="Write a quick post . . ." onkeypress="return commentPost(event, 0);"></textarea>
					<br /><input class="" type="submit" name="main_post_submit" value="Submit Post" />
				</p>
			</form>
		</div>
	</div>';
}

// Show the Profile Wall (posts)
if(isset($clearance['access']))
{
	// Get the list of posts on that user's social page
	$socialPosts = AppSocial::getPostList(You::$id, (isset($_POST['page']) ? $_POST['page'] + 0 : 1));
	
	// I think this section would be slower, but we might as well test it at some point
	// The current setup only scans for the username if we don't have it in memory yet (User::$cache[$uniID])
	// $socialPosts = Database::selectMultiple("SELECT p.id, p.uni_id, p.poster_id, u.display_name, p.post, p.attachment_id, p.date_posted, p.has_comments FROM social_posts as p INNER JOIN users as u ON p.poster_id = u.uni_id WHERE p.uni_id=? ORDER BY date_posted DESC LIMIT 0, 20", array(You::$id));
	
	// Run the auto-scrolling script
	echo '
	<script>
		urlToLoad = "/ajax/wall-loader";
		elementIDToAutoScroll = "social-feed";
		startPos = 2;
		entriesToReturn = 1;
		maxEntriesAllowed = 20;
		waitDuration = 1200;
		appendURL = "&uniID=' . You::$id . '";
		
		function afterAutoScroll() {
			picturefill();
		}
	</script>';
	
	echo '
	<div id="social-feed">';
	
	AppSocial::displayFeed($socialPosts, $clearance);
	
	echo '
	</div>';
}

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
