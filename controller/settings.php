<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/settings");
}

// Run the page data
if(!$socialPage = AppSocial::getPage(Me::$id))
{
	header("Location: /"); exit;
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
		$bucketDir = $urlPath['imageDir'] . '/' . $urlPath['mainDir'] . '/' . $urlPath['secondDir'];
		$imageDir = APP_PATH . $bucketDir;
		
		// Save the image to a chosen path
		if($imageUpload->validate())
		{
			$imageUpload->filename = $urlPath['filename'];
			
			$image = new Image($imageUpload->tempPath, $imageUpload->width, $imageUpload->height, $imageUpload->extension);
			
			if(FormValidate::pass())
			{
				// Save the original image
				$image->autoCrop(1140, 400);
				$image->save($imageDir . "/" . $imageUpload->filename . ".jpg");
				
				// Set the user as having a header photo
				Database::query("UPDATE social_page SET has_headerPhoto=? WHERE uni_id=? LIMIT 1", array(1, Me::$id));
				
				Alert::success("Header Photo", "You have uploaded a header photo to your profile.");
			}
		}
	}
	
	// Prepare Values
	$socialPage['perm_post'] = isset($_POST['post']) ? (int) $_POST['post'] : $socialPage['perm_post'];
	$socialPage['perm_access'] = isset($_POST['access']) ? (int) $_POST['access'] : $socialPage['perm_access'];
	$socialPage['perm_comment'] = isset($_POST['comment']) ? (int) $_POST['comment'] : $socialPage['perm_comment'];
	
	// Update the page settings
	Database::query("UPDATE social_page SET perm_access=?, perm_post=?, perm_comment=? WHERE uni_id=? LIMIT 1", array($socialPage['perm_access'], $socialPage['perm_post'], $socialPage['perm_comment'], Me::$id));
	
	Alert::success("Settings Updated", "Your page settings have been updated.");
}

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
		<select name="access">' . str_replace('value="' . $socialPage['perm_access'] . '"', 'value="' . $socialPage['perm_access'] . '" selected', '
			<option value="9">Only I can view my page</option>
			<option value="5">Only my friends can view my page</option>
			<option value="0">Guests are allowed to view my page - it\'s public</option>') . '
		</select>
	</p>
	
	<p>
		<strong>Who is allowed to post on my page?</strong><br />
		<select name="post">' . str_replace('value="' . $socialPage['perm_post'] . '"', 'value="' . $socialPage['perm_post'] . '" selected', '
			<option value="9">Only I can post</option>
			<option value="5">Only my friends can post</option>') . '
		</select>
	</p>
	
	<p>
		<strong>Who is allowed to comment on my page?</strong><br />
		<select name="comment">' . str_replace('value="' . $socialPage['perm_comment'] . '"', 'value="' . $socialPage['perm_comment'] . '" selected', '
			<option value="9">Only I can comment on my page</option>
			<option value="5">Only my friends can comment on my page</option>
			<option value="0">Guests are allowed to comment on my page</option>') . '
		</select>
	</p>
	
	<input type="submit" name="submit" value="Update My Page">
	 
</form>

</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
