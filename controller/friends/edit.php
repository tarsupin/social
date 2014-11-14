<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/friends", "/");
}

// Get the friend that you're interacting with
if(!isset($_GET['handle']) or !$friend = User::getDataByHandle($_GET['handle'], "uni_id, display_name, handle"))
{
	Alert::saveError("Invalid Friend", "That friend is not valid.", 4);
	
	header("Location: /friends"); exit;
}

// Recognize Integers
$friend['uni_id'] = (int) $friend['uni_id'];

// Make sure you are actually their friend
if(!AppFriends::isFriend(Me::$id, $friend['uni_id']))
{
	Alert::saveError("Not Friends", "You are not friends with that user.", 8);
	
	header("Location: /friends"); exit;
}

// Check if there was a form submission
if(Form::submitted("friend-update"))
{
	FormValidate::number("View Permissions", $_POST['view_clearance'], 0, 9);
	FormValidate::number("Write Permissions", $_POST['interact_clearance'], 0, 9);
	
	if(FormValidate::pass())
	{
		// If you set the friend to be deleted
		if($_POST['view_clearance'] == 0)
		{
			AppFriends::delete(Me::$id, $friend['uni_id']);
			
			Alert::saveInfo("Friend Updated", "You and " . $friend['handle'] . " are no longer friends.");
		}
		else
		{
			AppFriends::setClearance(Me::$id, $friend['uni_id'], $_POST['view_clearance'], $_POST['interact_clearance']);
		}
		
		Alert::saveSuccess("Friend Updated", "You have updated " . $friend['handle'] . "'s permissions.");
		
		header("Location: /friends"); exit;
	}
}

// Get the clearance levels of this friend
list($viewClearance, $interactClearance) = AppFriends::getClearance(Me::$id, $friend['uni_id']);

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
<style>
	#friend-block { display:inline-block; padding:12px; text-align:center; }
</style>

<h3>Edit Friend: ' . $friend['display_name'] . ' (@' . $friend['handle'] . ')</h3>

<form class="uniform" action="/friends/edit?handle=' . $friend['handle'] . '" method="post">' . Form::prepare('friend-update') . '
<div id="friend-block">
	<p>
		<a href="/' . $friend['handle'] . '"><img class="circimg-large" src="' . ProfilePic::image($friend['uni_id'], "large") . '" /></a>
		<br /><a href="' . URL::unifaction_social() . '">' . $friend['display_name'] . '</a>
		<br /><a href="' . URL::fastchat_social() . '">@' . $friend['handle'] . '</a>
		
		<div>
			View Permissions:<br /><select name="view_clearance">' . str_replace('value="' . $viewClearance . '"', 'value="' . $viewClearance . '" selected', '
				<option value="7">Trusted - Full Access</option>
				<option value="5">Standard Access</option>
				<option value="3">Limited Access</option>
				<option value="1">Restricted Access</option>
				<option value="0">Untrusted - DELETE FRIEND</option>') . '
			</select>
		</div>
		
		<div style="margin-top:22px;">
			Write / Post Permissions:<br /><select name="interact_clearance">' . str_replace('value="' . $interactClearance . '"', 'value="' . $interactClearance . '" selected', '
				<option value="7">Trusted - Full Rights</option>
				<option value="5">Standard Rights</option>
				<option value="3">Limited Rights</option>
				<option value="1">Restricted Rights</option>
				<option value="0">Untrusted - No Rights</option>') . '
			</select>
		</div>
	</p>
	<p><input type="submit" name="submit" value="Update Friend" /></p>
</div>
</form>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
