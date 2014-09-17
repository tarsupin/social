<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/post");
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Check if the form is submitted
if(Form::submitted("social-post-adv"))
{
	// Prepare Values
	$hashData = array();
	$subImage = (isset($_FILES['image']) and $_FILES['image']['tmp_name'] != "") ? true : false;
	$subVideo = ($_POST['video'] != "") ? true : false;
	
	FormValidate::text(($subImage or $subVideo) ? "Caption" : "Message", $_POST['message'], ($subImage or $subVideo) ? 0 : 1, ($subImage or $subVideo) ? 300 : 1000);
	FormValidate::number("Post Time", $_POST['post_hours'], 0, 9999);
	
	if($subVideo) { FormValidate::url("Video URL", $_POST['video'], 1, 255); }
	if($subImage) { FormValidate::filepath("Image URL", $_FILES['image']['tmp_name'], 1, 255); }
	
	if(FormValidate::pass())
	{
		// Prepare Values
		$attachmentID = 0;
		$setMobile = false;
		
		// Load an image, if one was submitted
		if($subImage)
		{
			// Initialize the plugin
			$imageUpload = new ImageUpload($_FILES['image']);
			
			// Set your image requirements
			$imageUpload->maxWidth = 4200;
			$imageUpload->maxHeight = 3500;
			$imageUpload->maxFilesize = 1024 * 3000;	// 3 megabytes
			$imageUpload->saveMode = Upload::MODE_UNIQUE;
			
			// Set the image directory
			$srcData = Upload::fileBucketData(Me::$id, 10000);	// Change to an actual integer
			$bucketDir = '/assets/images/' . $srcData['main_directory'] . '/' . $srcData['second_directory'];
			$imageDir = APP_PATH . $bucketDir;
			
			// Save the image to a chosen path
			if($imageUpload->validate())
			{
				$image = new Image($imageUpload->tempPath, $imageUpload->width, $imageUpload->height, $imageUpload->extension);
				
				if(FormValidate::pass())
				{
					// Prepare the proper scaling for the image
					$origWidth = ($imageUpload->width < 900) ? $imageUpload->width : 900;
					
					// Save the original image
					$image->autoWidth($origWidth, $origWidth / $imageUpload->scale);
					$image->save($imageDir . "/" . $imageUpload->filename . ".jpg");
					
					if($origWidth > 550)
					{
						// Save the tablet version of the image
						/* // Actually, in our case, we don't seem to need the tablet version - skip to mobile.
						$origWidth = $origWidth / 1.5;
						$image->autoWidth($origWidth, $origWidth / $imageUpload->scale);
						$image->save($imageDir . "/" . $imageUpload->filename . "-tablet.jpg");
						*/
						
						$origWidth = 360;
						
						// Save the mobile version of the image
						$image->autoWidth($origWidth, $origWidth / $imageUpload->scale);
						$image->save($imageDir . "/" . $imageUpload->filename . "-mobile.jpg");
						
						$setMobile = true;
					}
					
					// Create the attachment
					$attachment = new Attachment(Attachment::TYPE_IMAGE, $bucketDir . '/' . $imageUpload->filename . ".jpg");
					
					$hashData['image_url'] = SITE_URL . $bucketDir . '/' . $imageUpload->filename . '.jpg';
					
					// Update the attachment's important settings
					$attachment->setPosterHandle(Me::$vals['handle']);
					
					if($setMobile)
					{
						// Set the mobile attachment value
						$attachment->setMobileImage($bucketDir . '/' . $imageUpload->filename . "-mobile.jpg");
						
						$hashData['mobile_url'] = SITE_URL . $bucketDir . '/' . $imageUpload->filename . '-mobile.jpg';
					}
					
					// Save the attachment into the database
					$attachment->save();
					
					// Connect the attachment to your post
					$attachmentID = $attachment->id;
				}
			}
		}
		else if($_POST['video'] != "")
		{
			// Pull the valid video embed from the URL
			if($embed = Attachment::getVideoEmbedFromURL($_POST['video']))
			{
				// Create the attachment
				$attachment = new Attachment(Attachment::TYPE_VIDEO);
				
				// Update the attachment's important settings
				$attachment->setPosterHandle(Me::$vals['handle']);
				
				// Add important data
				$attachment->setAsset($_POST['video']);
				$attachment->setEmbed($embed);
				
				$hashData['video_url'] = $_POST['video'];
				
				// Save the attachment into the database
				$attachment->save();
				
				// Connect the attachment to your post
				$attachmentID = $attachment->id;
			}
			else
			{
				Alert::error("Video Invalid", "That video link was invalid or not recognized.");
			}
		}
		
		// There may have been errors from the image upload, so double check here
		if(FormValidate::pass())
		{
			// Create the post
			$postID = AppSocial::createPost(Me::$id, Me::$id, $attachmentID, $_POST['message'], "", ($_POST['post_hours'] ? (time() + round(3600 * $_POST['post_hours'])) : 0), $hashData);
			
			// Display Success
			Alert::saveSuccess("Post Successful", "You have successfully posted to your wall!");
			header("Location: /" . Me::$vals['handle']); exit;
		}
	}
}

// Sanitize Values
$_POST['message'] = isset($_POST['message']) ? Sanitize::text($_POST['message']) : "";
$_POST['image'] = isset($_POST['image']) ? Sanitize::filepath($_POST['video']) : "";
$_POST['video'] = isset($_POST['video']) ? Sanitize::url($_POST['video']) : "";
$_POST['post_hours'] = (isset($_POST['post_hours']) and $_POST['post_hours'] != 0) ? $_POST['post_hours'] + 0 : "0.0";

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="panel-right"></div>
<div id="content" class="content-open">' . Alert::display();

echo '
<h3>Create a Wall Post</h3>

<div style="margin-top:12px;">
<form class="uniform" action="/post" method="post" enctype="multipart/form-data">' . Form::prepare("social-post-adv") . '
	<p>
		<textarea name="message" placeholder="Write your message here . . ." style="width:90%;" tabindex="10" autofocus>' . htmlspecialchars($_POST['message']) . '</textarea></p>
	<p>Upload Image: <input type="file" name="image" value="' . $_POST['image'] . '"></p>
	<p>Video URL: <input type="text" name="video" value="' . $_POST['video'] . '"> (from vimeo.com or youtube.com)</p>
	<p>Post In: <input type="text" name="post_hours" value="' . $_POST['post_hours'] . '" size="6" maxlength="6"> hours</p>
	<p><input type="submit" name="submit" value="Submit Post" tabindex="20"></p>
</form>
</div>

</div>';


// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
