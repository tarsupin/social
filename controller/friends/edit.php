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
$clearance = AppFriends::getClearance(Me::$id, $friend['uni_id']);

if($clearance < 4)
{
	Alert::saveError("Not Friends", "You are not friends with that user.", 8);
	
	header("Location: /friends"); exit;
}

// Check if there was a form submission
if(Form::submitted("friend-update"))
{
	FormValidate::number("Permissions", $_POST['clearance'], 0, 9);
	
	if(FormValidate::pass())
	{
		// If you set the friend to be deleted
		if($_POST['clearance'] == 0)
		{
			if(AppFriends::unfriend(Me::$id, $friend['uni_id']))
			{
				Alert::saveInfo("Friend Updated", "You and " . $friend['handle'] . " are no longer friends.");
			}
		}
		else
		{
			AppFriends::setClearance(Me::$id, $friend['uni_id'], $_POST['clearance']);
		}
		
		Alert::saveSuccess("Friend Updated", "You have updated " . $friend['handle'] . "'s permissions.");
		
		header("Location: /friends"); exit;
	}
}

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

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
		<br /><a href="' . URL::unifaction_social() . '">@' . $friend['handle'] . '</a>
		
		<div>
			Permissions:<br /><select name="clearance">' . str_replace('value="' . $clearance . '"', 'value="' . $clearance . '" selected', '
				<option value="6">Trusted Friend</option>
				<option value="4">Standard Access</option>
				<option value="0">REMOVE FRIEND</option>') . '
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
