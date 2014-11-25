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
	
	// Update the page settings
	Database::query("UPDATE social_data SET perm_access=?, perm_post=?, perm_comment=?, feed_sort=? WHERE uni_id=? LIMIT 1", array($social->data['perm_access'], $social->data['perm_post'], $social->data['perm_comment'], $social->data['feed_sort'], Me::$id));
	
	Alert::success("Settings Updated", "Your page settings have been updated.");
}

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="content" class="content-open">' . Alert::display() . '';

// Create the image upload form
echo '
<h3>Upload your Header Photo</h3>

<form class="uniform" action="/settings" method="post" enctype="multipart/form-data">' . Form::prepare("upl-social-header") . '
	
	Upload Image: <input type="file" name="image">
	
	<h3 style="margin-top:22px;">Your Privacy Settings</h3>
	
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
	
	<h3 style="margin-top:22px;">Feed Settings</h3>
	
	<p>
		<strong>How should I sort the feed?</strong><br />
		<select name="feed_sort">' . str_replace('value="' . $social->data['feed_sort'] . '"', 'value="' . $social->data['feed_sort'] . '" selected', '
			<option value="1">Sort by date (recent posts first)</option>
			<option value="0">Sort by relevance</option>') . '
		</select>
	</p>
	
	<input type="submit" name="submit" value="Update My Page">
	 
</form>

</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
