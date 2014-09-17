<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/settings/upload-header");
}

// Check if the form is submitted
if(Form::submitted("upl-social-header"))
{
	// Set your image requirements
	$validateImage = array(
		'maxFileSize'		=> 1024 * 800			// 800 kilobytes
	,	'minWidth'			=> 1140
	,	'maxWidth'			=> 1800
	,	'minHeight'			=> 400
	,	'maxHeight'			=> 700
	,	'allowedMime'		=> array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png')
	,	'allowedExt'		=> array('png', 'jpg', 'gif')
	);
	
	//	# If you would like to add psd files (photoshop), add the following mimes & extensions:
	//	'image/photoshop', 'image/x-photoshop', 'image/psd', 'application/photoshop', 'application/psd', 'zz-application/zz-winassoc-psd'
	//	'psd'
	
	// Gather data about the image
	$imageData = Upload::imageData($_FILES['image']);
	
	// Set the image's save mode
	$imageData['saveMode'] = "overwrite_existing";
		// 'as_provided'			// This saves the provided file name, and rejects if there's a naming conflict
		// 'overwrite_existing'		// This overwrites the existing
		// 'overwrite_rename'		// This renames the image with unique characters if name conflicts
		// 'unique_name'			// This gives the image a unique name when uploaded; ignores original name
	
	// Prepare File Destination
	$uploadPath = AppSocial::headerPhoto(Me::$id);
	
	$imageData['directory'] = APP_PATH . $uploadPath['imageDir'] . '/' . $uploadPath['mainDir'] . '/' . $uploadPath['secondDir'];
	
	$imageData['filename'] = $uploadPath['filename'];	// Can overwrite auto-generated filename if desired
	$imageData['ext'] = $uploadPath['ext'];				// The extension you intend to write as
	
	// Validate the image properties
	Upload::validateImage($imageData, $validateImage);
	Upload::validateImageDirectory($imageData);
	Upload::validateImageFilename($imageData, 22);
	
	if(FormValidate::pass())
	{
		// Upload the Image (this part can still fail)
		if(Upload::image($imageData))
		{
			// Resize the image after it's produced
			$image = new Image($imageData['directory'] . '/' . $imageData['filename'] . '.' . $imageData['ext']);
			$image->autoCrop(1140, 400);
			$image->save($imageData['directory'] . '/' . $imageData['filename'] . '.' . $imageData['ext']);
			
			// Set the header photo to active
			Database::query("UPDATE social_page SET has_headerPhoto=? WHERE uni_id=? LIMIT 1", array(1, Me::$id));
			
			// Display success message
			Alert::success("Image Uploaded", "Your image has been uploaded!");
		}
	}
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
<div id="content">
' . Alert::display();

echo '
<div class="open-block">';

// Create the image upload form
echo '
	<form class="uniform" action="/settings/upload-header" method="post" enctype="multipart/form-data">' . Form::prepare("upl-social-header") . '
		Upload Image: <input type="file" name="image"> <input type="submit" value="Submit">
	</form>
</div>';

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
