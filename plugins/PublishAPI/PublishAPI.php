<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API allows a user to automatically post content to his feed from another site.


------------------------------
------ Calling this API ------
------------------------------
	
	// Prepare the Packet
	$packet = array(
		'uni_id'		=> $uniID		// The UniID of the page that you're posting to
	,	'type'			=> $type		// The type of content being chatted (blog, article, etc)
	,	'poster_id'		=> $posterID	// The person posting to the page (usually the same as UniID)
	,	'message'		=> $message		// Set this value to the message or caption to write
	,	'image_url'		=> $imageURL	// Set this value (absolute url) if you're posting an image
	,	'mobile_url'	=> $mobileURL	// Set this to the mobile verson of the image, if applicable
	,	'video_url'		=> $videoURL	// Set this value (absolute url) if you're posting a video
	,	'attach_title'	=> $title		// If set, this is the title of the attachment
	,	'attach_desc'	=> $desc		// If set, this is the description of the attachment
	,	'source'		=> $source		// The URL source to link to (if someone clicks on it)
	);
	
	Connect::to("social", "PublishAPI", $packet);
	
	
[ Possible Responses ]
	TRUE if the post was successful
	FALSE if not

*/

class PublishAPI extends API {
	
	
/****** API Variables ******/
	public $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public $microCredits = 2;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public $minClearance = 7;			// <int> The minimum clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	)					// RETURNS <bool> TRUE on success, FALSE on failure
	
	// $this->runAPI()
	{
		// Make sure the appropriate data was sent
		if(!isset($this->data['uni_id']) or !isset($this->data['poster_id']))
		{
			return false;
		}
		
		// Prepare Values
		$attachmentID = 0;
		$uniID = (int) $this->data['uni_id'];
		$posterID = (int) $this->data['poster_id'];
		$source = (isset($this->data['source']) ? Sanitize::url($this->data['source']) : "");
		$message = (isset($this->data['message']) ? Sanitize::text($this->data['message']) : "");
		$origHandle = (isset($this->data['orig_handle']) ? Sanitize::variable($this->data['orig_handle']) : "");
		
		$attachTitle = (isset($this->data['attach_title']) ? Sanitize::safeword($this->data['attach_title'], " !?'") : "");
		$attachDesc = (isset($this->data['attach_desc']) ? Sanitize::safeword($this->data['attach_desc'], " !?'\"") : "");
		
		$imageURL = (isset($this->data['image_url']) ? Sanitize::url($this->data['image_url']) : "");
		$mobileURL = (isset($this->data['mobile_url']) ? Sanitize::url($this->data['mobile_url']) : "");
		$videoURL = (isset($this->data['video_url']) ? Sanitize::url($this->data['video_url']) : "");
		
		// Get the Current Type
		$type = (isset($this->data['type']) ? Sanitize::variable($this->data['type']) : '');
		
		if($type == "")
		{
			if($imageURL) { $type = "image"; }
			else if($videoURL) { $type = "video"; }
			else if($message != "") { $type = "message"; }
		}
		
		// If we're publishing an Image
		if($type == "image")
		{
			// Create the attachment
			$attachment = new Attachment(Attachment::TYPE_IMAGE, $imageURL);
			
			// Update the attachment's important settings
			$attachment->setSource($source);
			
			if($attachTitle) { $attachment->setTitle($attachTitle); }
			if($attachDesc) { $attachment->setDescription($attachDesc); }
			
			if(isset($this->data['mobile_url']))
			{
				// Set the mobile attachment value
				$attachment->setMobileImage($mobileURL);
			}
			
			// Save the attachment into the database
			$attachment->save();
			
			// Connect the attachment to your post
			$attachmentID = $attachment->id;
			
			// Create the post
			if($postID = AppSocial::createPost($uniID, $posterID, $attachmentID, $message, $source, 0))
			{
				return true;
			}
		}
		
		// If we're publishing a Video
		else if($type == "video")
		{
			// Get the Embed
			if(!$embed = Attachment::getVideoEmbedFromURL($this->data['video_url']))
			{
				return false;
			}
			
			// Create the attachment
			$attachment = new Attachment(Attachment::TYPE_VIDEO, $this->data['video_url']);
			
			// Update the attachment's important settings
			$attachment->setSource($source);
			
			if($attachTitle) { $attachment->setTitle($attachTitle); }
			if($attachDesc) { $attachment->setDescription($attachDesc); }
			
			// Add important data
			$attachment->setEmbed($embed);
			
			// Save the attachment into the database
			$attachment->save();
			
			// Connect the attachment to your post
			$attachmentID = $attachment->id;
			
			// Create the post
			if($postID = AppSocial::createPost($uniID, $posterID, $attachmentID, $message, $source, 0))
			{
				return true;
			}
		}
		
		// If we're publishing a message
		else if($type == "message")
		{
			if(AppSocial::createPost($uniID, $posterID, 0, $message))
			{
				return true;
			}
		}
		
		// If we're publishing an article
		else if($type == "article")
		{
			// Create the attachment
			$attachment = new Attachment(Attachment::TYPE_ARTICLE, $imageURL);
			
			// Update the attachment's important settings
			$attachment->setSource($source);
			
			if($attachTitle) { $attachment->setTitle($attachTitle); }
			if($attachDesc) { $attachment->setDescription($attachDesc); }
			
			if($mobileURL)
			{
				// Set the mobile attachment value
				$attachment->setMobileImage($mobileURL);
			}
			
			// Save the attachment into the database
			$attachment->save();
			
			// Connect the attachment to your post
			$attachmentID = $attachment->id;
			
			// Create the post
			if(AppSocial::createPost($uniID, $posterID, $attachmentID, $message, $source, 0))
			{
				return true;
			}
		}
		
		return false;
	}
	
}
