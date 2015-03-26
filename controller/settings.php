<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/settings");
}

// Run the page data
if(!$social = new AppSocial(Me::$id))
{
	Me::redirectLogin("/");
}

// Check if the form is submitted
if(Form::submitted("upl-social-header"))
{
	// If an image has been submitted
	if(isset($_FILES['image']['tmp_name']) and $_FILES['image']['tmp_name'])
	{
		// Initialize the plugin
		$imageUpload = new ImageUpload($_FILES['image']);
		
		// Set your image requirements
		$imageUpload->maxWidth = 4200;
		$imageUpload->maxHeight = 3500;
		$imageUpload->maxFilesize = 1024 * 3000;	// 3 megabytes
		$imageUpload->saveMode = Upload::MODE_UNIQUE;
		
		// Set the image directory
		$urlPath = AppSocial::headerPhoto(Me::$id, Me::$vals['handle']);
		$bucketDir = $urlPath['imageDir'] . '/' . $urlPath['mainDir'];
		$imageDir = APP_PATH . $bucketDir;
		
		// Save the image to a chosen path
		if($imageUpload->validate())
		{
			$imageUpload->filename = $urlPath['filename'];
			
			$image = new Image($imageUpload->tempPath, $imageUpload->width, $imageUpload->height, $imageUpload->extension);
			
			if(FormValidate::pass())
			{
				// Save the original image
				$image->autoCrop(1200, 420);
				$image->save($imageDir . "/" . $imageUpload->filename . ".jpg");
				
				// Set the user as having a header photo
				Database::query("UPDATE social_data SET has_headerPhoto=? WHERE uni_id=? LIMIT 1", array(1, Me::$id));
				
				Alert::success("Header Photo", "You have uploaded a header photo to your profile.");
			}
		}
	}
	
	// Prepare Values
	$social->data['perm_access'] = isset($_POST['access']) ? (int) $_POST['access'] : (int) $social->data['perm_access'];
	$social->data['perm_post'] = isset($_POST['post']) ? (int) $_POST['post'] : (int) $social->data['perm_post'];
	$social->data['perm_comment'] = isset($_POST['comment']) ? (int) $_POST['comment'] : (int) $social->data['perm_comment'];
	$social->data['feed_sort'] = isset($_POST['feed_sort']) ? (int) $_POST['feed_sort'] : (int) $social->data['feed_sort'];
	$social->data['feed_notify'] = isset($_POST['feed_notify']) ? (int) $_POST['feed_notify'] : (int) $social->data['feed_notify'];
	
	// Update the page settings
	Database::query("UPDATE social_data SET perm_access=?, perm_post=?, perm_comment=?, feed_sort=?, feed_notify=? WHERE uni_id=? LIMIT 1", array($social->data['perm_access'], $social->data['perm_post'], $social->data['perm_comment'], $social->data['feed_sort'], $social->data['feed_notify'], Me::$id));
	
	Alert::success("Settings Updated", "Your page settings have been updated.");
}

// If you chose an avatar
if(isset($_GET['def']))
{
	// Check if that avatar is valid
	$packet = array(
		"uni_id"	=> Me::$id
	,	"avi_id"	=> (int) $_GET['def']		// The ID of the avatar to test for
	);
	
	if((int) $_GET['def'] == 0)
	{
		$avatarExists = true;
	}
	else
	{
		$avatarExists = Connect::to("avatar", "AvatarExists", $packet);
	}
	
	if($avatarExists)
	{
		// If the avatar is valid, update your default avatar
		Database::query("UPDATE users SET avatar_opt=? WHERE uni_id=? LIMIT 1", array((int) $_GET['def'], Me::$id));
		
		Me::$vals['avatar_opt'] = (int) $_GET['def'];
		
		Alert::success("Avatar Updated", "You have chosen your default avatar.");
	}
	else
	{
		Alert::error("Avatar Error", "There was an error trying to load this avatar.");
	}
}

// Prepare Values
$avatarList = json_decode($social->data['avatar_list'], true);

// If you're loading your avatars
if($value = Link::clicked() and $value == "load-avatars")
{
	// Prepare a list of plugins and their current versions
	$packet = array(
		"uni_id"			=> Me::$id			// The UniID to check avatars for
	);
	
	if($avatarList = Connect::to("avatar", "MyAvatarsAPI", $packet))
	{
		// Update your avatar list
		Database::query("UPDATE social_data SET avatar_list=? WHERE uni_id=? LIMIT 1", array(json_encode($avatarList), Me::$id));
		
		if(!Me::$vals['avatar_opt'])
		{
			Database::query("UPDATE users SET avatar_opt=? WHERE uni_id=? LIMIT 1", array(1, Me::$id));
		}
	}
}

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

/****** Page Configuration ******/
$config['canonical'] = "/settings";
$config['pageTitle'] = "Settings";		// Up to 70 characters. Use keywords.

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Upload your Header Photo</div>
	<div class="inner-box">';

// Create the image upload form
echo '
<form class="uniform" action="/settings" method="post" enctype="multipart/form-data">' . Form::prepare("upl-social-header") . '
	
	<input type="file" name="image"> (max 4200x3500, 3MB)
	<input type="submit" name="submit" value="Upload">

</form>
	</div>
</div>';

// Display your list of avatars available
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">My Avatars</div>
	</div>
	<div class="inner-box">
		<div style="display:inline-block; padding:6px; text-align:center;"><img src="' . ProfilePic::image(Me::$id, "large") . '" /><br /><a class="button" href="/settings?def=0">Set as Default</a></div>';
if($avatarList)
{
	foreach($avatarList as $aviID => $aviName)
	{
		echo '
		<div style="display:inline-block; padding:6px; text-align:center;"><img src="' . Avatar::image(Me::$id, (int) $aviID) . '" /><br /><a class="button" href="/settings?def=' . $aviID . '">Set as Default</a></div>';
	}
}

echo '
	<div style="padding:8px;"><a class="button" href="/settings?loadAvis=1&' . Link::prepare("load-avatars") . '">Load My Avatars</a> <a class="button" href="' . URL::avatar_unifaction_com() . Me::$slg . '">Create an Avatar</a></div>
	</div>
</div>';

// Other Settings
echo '
<div class="overwrap-box">
	<div class="overwrap-line">Your Privacy Settings</div>
	<div class="inner-box">
<form class="uniform" action="/settings" method="post">' . Form::prepare("upl-social-header") . '
	<p>
		<strong>Who is allowed to view my page?</strong><br />
		<select name="access">' . str_replace('value="' . $social->data['perm_access'] . '"', 'value="' . $social->data['perm_access'] . '" selected', '
			<option value="8">Only I can view my page</option>
			<option value="4">Only my friends can view my page</option>
			<option value="0">Guests are allowed to view my page - it\'s public</option>') . '
		</select>
	</p>
	
	<p>
		<strong>Who is allowed to post on my page?</strong><br />
		<select name="post">' . str_replace('value="' . $social->data['perm_post'] . '"', 'value="' . $social->data['perm_post'] . '" selected', '
			<option value="8">Only I can post</option>
			<option value="4">Only my friends can post</option>
			<option value="0">Guests can post</option>') . '
		</select>
	</p>
	
	<p>
		<strong>Who is allowed to comment on my page?</strong><br />
		<select name="comment">' . str_replace('value="' . $social->data['perm_comment'] . '"', 'value="' . $social->data['perm_comment'] . '" selected', '
			<option value="8">Only I can comment on my page</option>
			<option value="4">Only my friends can comment on my page</option>
			<option value="0">Guests are allowed to comment on my page</option>') . '
		</select>
	</p>
	
	<input type="submit" name="submit" value="Update Privacy Settings">

</form>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Feed Settings</div>
	<div class="inner-box">
<form class="uniform" action="/settings" method="post">' . Form::prepare("upl-social-header") . '
	<p>
		<strong>How should I sort the feed?</strong><br />
		<select name="feed_sort">' . str_replace('value="' . $social->data['feed_sort'] . '"', 'value="' . $social->data['feed_sort'] . '" selected', '
			<option value="1">Sort by date (recent posts first)</option>
			<option value="0">Sort by relevance</option>') . '
		</select>
	</p>
	<p>
		<strong>Should I receive notifications of new statuses?</strong><br />
		If you choose the second option, you will not receive notifications of statuses that your friends or people you\'re following post. They can still be seen in your <a href="' . URL::unifaction_social() . '">feed</a>, and you will still receive notifications of other events.<br/>
		<select name="feed_notify">' . str_replace('value="' . $social->data['feed_notify'] . '"', 'value="' . $social->data['feed_notify'] . '" selected', '
			<option value="1">Notify me</option>
			<option value="0">Do not notify me</option>') . '
		</select>
	</p>
	
	<input type="submit" name="submit" value="Update Feed Settings">
	 
</form>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
