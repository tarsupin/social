<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$feedPosts = array();

if(Me::$loggedIn)
{
	// Update your Feed
	AppFeed::update(Me::$id);
	
	// Get the list of posts in your feed
	$feedPosts = AppFeed::get(Me::$id);
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

foreach($feedPosts as $post)
{
	// Get the user's display name if we don't have it already (reduces times caled)
	$pID = (int) $post['poster_id'];
	$post['date_posted'] = (int) $post['date_posted'];
	
	if(!isset(User::$cache[$pID]))
	{
		User::$cache[$pID] = Database::selectOne("SELECT handle, display_name FROM users WHERE uni_id=? LIMIT 1", array($pID));
	}
	
	echo '
	<div class="post">
		<div class="post-header">
			<div><span class="post-icon icon-comment"></span> &nbsp; <a href="/' . User::$cache[$pID]['handle'] . '" style="">' . User::$cache[$pID]['display_name'] . '</a> has written</div>
			<div><span class="post-icon icon-calendar"></span>  &nbsp; ' . Time::fuzzy($post['date_posted']) . '</div>
		</div>
		<div class="post-footer">
			<div>
				<a href="/' . User::$cache[$pID]['handle'] . '"><img class="circimg" src="' . ProfilePic::image($pID, "large") . '" /></a>
				<p class="post-message">' . Comment::showSyntax($post['post']) . '</p>
			</div>
			<div class="extralinks">
				<a href="#">Reply</a>
				<a href="#">ReChat</a>
			</div>';
	
	// Show Comments
	if($post['has_comments'] > 0)
	{
		// Get Comments
		$comments = AppComment::getList((int) $post['id'], 0, 3, "DESC");
		$comLen = count($comments);
		
		// Reverse the order (since you're providing the last three)
		if($comLen > 1)
		{
			$comments = array_reverse($comments);
		}
		
		// Provide option to show all comments
		if($post['has_comments'] > $comLen)
		{
			echo '
			<div class="block-group block-interior">
				Show all comments
			</div>';
		}
		
		// Display Last Three Comments
		foreach($comments as $comment)
		{
			$cpID = (int) $comment['uni_id'];
			$comment['date_posted'] = (int) $comment['date_posted'];
			
			if(!isset(User::$cache[$cpID]))
			{
				User::$cache[$cpID] = Database::selectOne("SELECT handle, display_name FROM users WHERE uni_id=? LIMIT 1", array($pID));
			}
			
			// Display the Comment
			echo '
			<div>
				<div style="float:left; margin-left:12px;"><a href="/' . User::$cache[$cpID]['handle'] . '"><img class="circimg-small" src="' . ProfilePic::image($cpID, "small") . '" /></a></div>
				<p class="post-message">' . Comment::showSyntax($comment['comment']) . '
					<br /><span style="font-size:0.8em;">' . User::$cache[$cpID]['display_name'] . ' &bull; ' . Time::fuzzy($comment['date_posted']) . '</span>
				</p>
			</div>';
		}
	}
	
	if(isset($clearance['comment']))
	{
		echo '
		<div>
			<form id="comment_' . $post['id'] . '"  action="/' . $social['url'] . '" method="post">' . Form::prepare() . '
				<img class="circimg-small post-avi" src="' . ProfilePic::image($pID) . '" />
				<p class="comment-box-wrap"><textarea class="comment-box" name="commentBox[' . $post['id'] . ']" value="" placeholder="Add a Comment . . ." onkeypress="return commentPost(event, ' . $post['id'] . ');"></textarea></p>
				<input class="comment-box-input" type="submit" name="subCom_' . $post['id'] . '" value="Submit" hidefocus="true" tabindex="-1" />
			</form>
		</div>';
	}
	
	echo '
		</div>
	</div>';
}

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
