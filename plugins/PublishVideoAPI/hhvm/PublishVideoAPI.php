<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API allows a user to automatically post a video to his feed from another site.


------------------------------
------ Calling this API ------
------------------------------
	
	// Prepare the Packet
	$packet = array(
		'uni_id'		=> $uniID		// The UniID of the page that you're posting to
	,	'poster_id'		=> $posterID	// The person posting to the page (usually the same as UniID)
	,	'video_url'		=> $videoURL	// Set this value (absolute url) if you're posting a video
	,	'title'			=> $title		// If set, this is the title of the attachment
	,	'description'	=> $desc		// If set, this is the description of the attachment
	,	'source'		=> $source		// The URL source to link to (if someone clicks on it)
	,	'orig_handle'	=> $origHandle	// The handle of the original poster
	);
	
	Connect::to("social", "PublishVideoAPI", $packet);
	
	
[ Possible Responses ]
	TRUE if the post was successful
	FALSE if the post failed

*/

class PublishVideoAPI extends API {
	
	
/****** API Variables ******/
	public bool $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public string $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public array <int, str> $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public int $microCredits = 2;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public int $minClearance = 7;			// <int> The minimum clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure
	
	// $this->runAPI()
	{
		// Make sure the appropriate data was sent
		if(!isset($this->data['uni_id']) or !isset($this->data['poster_id']) or !isset($this->data['video_url']))
		{
			return false;
		}
		
		// Prepare Values
		$videoURL = Sanitize::url($this->data['video_url']);
		
		$title = isset($this->data['title']) ? Sanitize::safeword($this->data['title'], " !?'\"") : "";
		$description = isset($this->data['description']) ? Sanitize::safeword($this->data['description'], " !?'\"") : "";
		$source = isset($this->data['source']) ? Sanitize::url($this->data['source']) : "";
		$origHandle = isset($this->data['orig_handle']) ? Sanitize::variable($this->data['orig_handle']) : '';
		
		// Get the Embed
		if(!$embed = Attachment::getVideoEmbedFromURL($videoURL))
		{
			return false;
		}
		
		// Create the attachment
		$attachment = new Attachment(Attachment::TYPE_VIDEO, $videoURL);
		
		// Update the attachment's important settings
		$attachment->setSource($source);
		
		if($title) { $attachment->setTitle($title); }
		if($description) { $attachment->setDescription($description); }
		
		// Add important data
		$attachment->setEmbed($embed);
		
		// Save the attachment into the database
		$attachment->save();
		
		// Create the post
		return (bool) AppSocial::createPost((int) $this->data['uni_id'], (int) $this->data['poster_id'], 0, $attachment->id, "", $source, 0, array(), $origHandle);
	}
	
}