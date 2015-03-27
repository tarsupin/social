<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/post");
}

// Stores the current time & date so that the Rome.js Calendar knows what your current time (and so that people can't schedule posts in the past)
$getNow = date("Y-m-d H:i:s"); 

// Stores the maximum calendar date (2 Months)
$getMax =  date("Y-m-d H:i:s", time() + (3600 * 24 * 60));

// Prepare the "Rome" Calendar
Metadata::addFooter('<script src="' . CDN . '/scripts/rome.js"></script>');
Metadata::addHeader('<link rel="stylesheet" type="text/css" href="' . CDN . '/css/rome.css" />');
Metadata::addFooter('<script>rome(dt, { min: "' . $getNow . '" , max: "' . $getMax . '", inputFormat: "YYYY-MM-DD HH:mm:ss"})</script>');

/****** Page Configuration ******/
$config['canonical'] = "/post";
$config['pageTitle'] = "Advanced Post";		// Up to 70 characters. Use keywords.

// Set the active user to yourself
You::$id = Me::$id;
You::$handle = Me::$vals['handle'];

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Check if the form is submitted
if(Form::submitted("social-post-adv"))
{
	// Prepare Values
	$hashData = array();
	$subImage = (isset($_FILES['image']) and $_FILES['image']['tmp_name'] != "") ? true : false;
	$subVideo = (isset($_POST['video']) and $_POST['video'] != "") ? true : false;
	
	FormValidate::text(($subImage or $subVideo) ? "Caption" : "Message", $_POST['message'], ($subImage or $subVideo) ? 0 : 1, 255);
	FormValidate::variable("valid date", $_POST['post_date'], 0, 19, ":- ");
	
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
			$bucketDir = '/assets/photos/' . $srcData['main_directory'] . '/' . $srcData['second_directory'];
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
					$image->autoWidth($origWidth, (int) ($origWidth / $imageUpload->scale));
					$image->save($imageDir . "/" . $imageUpload->filename . ".jpg");
					
					if($origWidth > 550)
					{
						// Save the tablet version of the image
						/* // Actually, in our case, we don't seem to need the tablet version - skip to mobile.
						$origWidth = $origWidth / 1.5;
						$image->autoWidth($origWidth, (int) ($origWidth / $imageUpload->scale));
						$image->save($imageDir . "/" . $imageUpload->filename . "-tablet.jpg");
						*/
						
						$origWidth = 360;
						
						// Save the mobile version of the image
						$image->autoWidth($origWidth, (int) ($origWidth / $imageUpload->scale));
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
			// Set the Clearance Level
			$clearance = isset($_POST['submit_public']) ? 0 : 4;
			
			// Create the post
			$postID = AppSocial::createPost(Me::$id, Me::$id, $clearance, $attachmentID, $_POST['message'], URL::unifaction_social() . "/" . You::$handle, strtotime($_POST['post_date']), $hashData);
			
			// Display Success
			Alert::saveSuccess("Post Successful", "You have successfully posted to your wall!");
			header("Location: /" . Me::$vals['handle']); exit;
		}
	}
}

// Sanitize Values
$_POST['message'] = isset($_POST['message']) ? Security::purify($_POST['message']) : '';
if(strlen($_POST['message']) < 1)
{
	Alert::error("Post Length", "Please enter a message.");
}
elseif(strlen($_POST['message']) > 255)
{
	Alert::error("Post Length", "Your post length may not exceed 255 characters.");
}
$_POST['video'] = isset($_POST['video']) ? Sanitize::url($_POST['video']) : "";
$_POST['post_date'] = (isset($_POST['post_date'])) ? $_POST['post_date'] : $getNow;

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Create a Wall Post</div>
	<div class="inner-box">';

echo '
<form class="uniform" action="/post" method="post" enctype="multipart/form-data">' . Form::prepare("social-post-adv") . '
	' . UniMarkup::buttonLine() . '
	<textarea id="core_text_box" name="message" placeholder="Enter your message here..." maxlength="255" style="resize:vertical; width:100%;" tabindex="10" autofocus>' . $_POST['message'] . '</textarea>';

if(!isset($_GET['gen']) or $_GET['gen'] == "image")
{
	echo '
	<p>Upload Image: <input type="file" name="image"> (max 4200x3500, 3MB)</p>';
}

if(!isset($_GET['gen']) or $_GET['gen'] == "video")
{
	echo '
	<p>Video URL: <input type="text" name="video" value="' . $_POST['video'] . '"> (from vimeo.com or youtube.com)</p>';
}

echo '
	<p>Post at: <input type="text" id="dt" name="post_date" class="input" value="' . $_POST['post_date'] . '" size="25"></p>
	<p><input type="button" value="Preview" onclick="previewPost();"/> <input type="submit" name="submit_friends" value="Post to Friends" class="button" /> <input type="submit" name="submit_public" value="Public Post" class="button" /></p>
</form>

	</div>
<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
</div>
</div>
<script>
function previewPost()
{
	var text = encodeURIComponent(document.getElementById("core_text_box").value);
	getAjax("", "preview-post", "parse", "body=" + text);
}
function parse(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("preview").style.display = "block";
	document.getElementById("preview").innerHTML = response;
}
</script>';


// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
