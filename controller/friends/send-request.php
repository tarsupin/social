<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends/send-request", "/");
}

// Get the Friend Data
if(isset($_GET['id']))
{
	$friendData = User::get((int) $_GET['id'], "uni_id, handle, display_name");
}
else if(isset($_GET['handle']))
{
	$_GET['handle'] = Sanitize::variable($_GET['handle']);
	
	$friendData = User::getDataByHandle($_GET['handle'], "uni_id, handle, display_name");
}
else
{
	header("Location: /friends"); exit;
}

// Deliver the Friend Data
if(!$friendData)
{
	$friendData = User::silentRegister($_GET['handle']);
}

if(!$friendData)
{
	Alert::saveError("Friend Invalid", "The friend selected is invalid.", 2);
	
	header("Location: /friends"); exit;
}

// Submit the Form
if(Form::submitted("send-req-uf"))
{
	// Recognize Integers
	$friendData['uni_id'] = (int) $friendData['uni_id'];
	
	// Check if the other user requested you as a friend
	if($clearance = AppFriends::getRequest($friendData['uni_id'], Me::$id))
	{
		// Approve the friend
		if(AppFriends::approve(Me::$id, $friendData['uni_id']))
		{
			Alert::saveSuccess("Friend Added", 'You are now friends with ' . $friendData['display_name'] . '!');
		}
		else
		{
			Alert::saveError("Friend Error", 'An error has occurred trying to add ' . $friendData['display_name'] . ' as a friend.', 4);
		}
		
		header("Location: /friends"); exit;
	}
	
	// Check if you're already friends with the user
	if(!AppFriends::isFriend(Me::$id, $friendData['uni_id']))
	{
		// Create the Friend Request
		AppFriends::sendRequest(Me::$id, $friendData['uni_id']);
		
		Alert::saveSuccess("Sent Friend Request", 'You have sent a friend request to ' . $friendData['display_name'] . '.');
		
		header("Location: /friends"); exit;
	}
	else
	{
		Alert::saveInfo("Already Friends", "You are already friends with that user.");
		
		header("Location: /friends"); exit;
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content" class="content-open">' . Alert::display();

echo '
<h3>Would you like to send a friend request to <a href="' . URL::unifaction_social() . '/' . $friendData['handle'] . '">' . $friendData['display_name'] . '</a> (<a href="' . URL::fastchat_social() . '/' . $friendData['handle'] . '">@' . $friendData['handle'] . '</a>)?</h3>

<form class="uniform" action="/friends/send-request?id=' . $friendData['uni_id'] . '" method="post">' . Form::prepare("send-req-uf") . '
<p><input type="submit" name="submit" value="Yes, Send Friend Request" /></p>
</form>

</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
