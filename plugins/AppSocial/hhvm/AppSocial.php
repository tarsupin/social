<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppSocial Plugin ------
-----------------------------------------

This plugin provides several social handling tools and allows users to manage their social pages.


-------------------------------
------ Methods Available ------
-------------------------------

$social		= AppSocial::getData($uniID);
$postData	= AppSocial::getPost($uniID, $postID)									// Retrieves post data.
$postID		= AppSocial::createPost($socialID, $uniID, $attachmentID, $message);	// Creates a post.
			  AppSocial::deletePost($uniID, $postID)								// Removes a post from the profile.

$urlPath	= AppSocial::headerPhoto($uniID);		// Returns the URL path for your photo header.

*/

class AppSocial {
	
	
/****** Plugin Variables ******/
	public int $uniID = 0;				// <int> The UniID of the page.
	public array <str, mixed> $data = array();			// <str:mixed> The social data for the user.
	
	public int $clearance = 0;			// <int> The level of clearance you have.
	public bool $canAccess = false;		// <bool> TRUE if the user can view the page.
	public bool $canPost = false;		// <bool> TRUE if the user can post on this page.
	public bool $canComment = false;		// <bool> TRUE if the user can comment on this page.
	
	public static string $linkProtect = "";		// <str> The link protection for any actions you make.
	
	
/****** Construct the user's social data ******/
	public function __construct
	(
		int $uniID			// <int> The UniID of the social page being accessed.
	): void					// RETURNS <void>
	
	// $socialData = new AppSocial($uniID);
	{
		$this->uniID = $uniID;
		$this->data = AppSocial::getData($uniID);
		
		self::$linkProtect = Link::prepare("uni6-social");
		
		$this->setPermissions();
	}
	
	
/****** Sets the permissions for this social page ******/
	public function setPermissions (
	): void					// RETURNS <void>
	
	// $social->setPermissions();
	{
		$clearance = 0;
		
		// If you own the page, all access is granted
		if(Me::$id == $this->uniID)
		{
			$clearance = 10;
		}
		
		// Pull your clearance with this user
		else
		{
			$clearance = AppFriends::getClearance(Me::$id, $this->uniID);
		}
		
		// Check if you're a moderator or staff member
		if(Me::$clearance >= 6)
		{
			$clearance = max(Me::$clearance, $clearance);
		}
		
		// Set the access levels
		$this->clearance = $clearance;
		$this->canAccess = $this->data['perm_access'] <= $clearance ? true : false;
		$this->canPost = $this->data['perm_post'] <= $clearance && $this->uniID > 0 ? true : false;
		$this->canComment = $this->data['perm_comment'] <= $clearance && $this->uniID > 0 ? true : false;
	}
	
	
/****** Get The User's Social Page Data ******/
	public static function getData
	(
		int $uniID			// <int> The ID of the user to recover the social page from.
	): array <str, mixed>					// RETURNS <str:mixed> page requirements and settings, or array() if failed.
	
	// $socialData = AppSocial::getData($uniID);
	{
		// Attempt to get the page data
		if($socialData = Database::selectOne("SELECT * FROM social_data WHERE uni_id=? LIMIT 1", array($uniID)))
		{
			return $socialData;
		}
		
		// Attempt to register the page
		if(!AppSocial::silentGen($uniID))
		{
			return array();
		}
		
		return Database::selectOne("SELECT * FROM social_data WHERE uni_id=? LIMIT 1", array($uniID));
	}
	
	
/****** Generate a user's social data ******/
	public static function silentGen
	(
		int $uniID			// <int> The UniID to silently generate the social data for
	): bool					// RETURNS <bool> TRUE if the silent generation worked, FALSE on failure.
	
	// AppSocial::silentGen($uniID);
	{
		// Gather user's information
		if(!$userData = User::get($uniID, "uni_id"))
		{
			User::silentRegister($uniID);
		
			// If the user still doesn't after a silent registration, end here.
			if(!$userData = User::get($uniID, "uni_id"))
			{
				return false;
			}
		}
		
		// Create the defaul social data
		return Database::query("REPLACE INTO social_data (uni_id, perm_access, perm_post, perm_comment, perm_approval) VALUES (?, ?, ?, ?, ?)", array($userData['uni_id'], 0, 0, 0, 0));
	}
	
	
/****** Get List of Posts from a user ******/
	public static function getUserPosts
	(
		int $uniID			// <int> The UniID that created the post.
	,	int $clearance		// <int> The clearance level that you can view.
	,	int $page = 1		// <int> The page that you're looking at.
	,	int $showNum = 15	// <int> The number of posts to show.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> the data for the list of posts, array() on failure.
	
	// $postList = AppSocial::getUserPosts($uniID, $clearance, [$page], [$showNum]);
	{
		return Database::selectMultiple("SELECT sp.*, u.handle, u.display_name, u.role FROM users_posts spu INNER JOIN social_posts sp ON spu.id=sp.id AND sp.clearance <= ? AND sp.date_posted <= ? INNER JOIN users u ON sp.poster_id=u.uni_id WHERE spu.uni_id=? ORDER BY spu.id DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), array($clearance, time(), $uniID));
	}
	
	
/****** Get Data about the Post ******/
	public static function getPost
	(
		int $uniID			// <int> The UniID that created the post.
	,	int $postID			// <int> The ID of the post.
	): array <str, mixed>					// RETURNS <str:mixed> the sql data of the post, array() on failure.
	
	// $postData = AppSocial::getPost($uniID, $postID);
	{
		return Database::selectOne("SELECT sp.* FROM users_posts spu INNER JOIN social_posts sp ON spu.id=sp.id WHERE spu.uni_id=? AND spu.id=? LIMIT 1", array($uniID, $postID));
	}
	
	
/****** Get the post directly, without knowing the UniID that posted it ******/
	public static function getPostDirect
	(
		int $postID			// <int> The ID of the post.
	): array <str, mixed>					// RETURNS <str:mixed> the sql data of the post, array() on failure.
	
	// $postData = AppSocial::getPostDirect($postID);
	{
		return Database::selectOne("SELECT sp.*, u.handle, u.display_name, u.role FROM social_posts sp INNER JOIN users u ON sp.poster_id=u.uni_id WHERE id=? LIMIT 1", array($postID));
	}
	
	
/****** Create a post on your wall ******/
	public static function createPost
	(
		int $socialID			// <int> The UniID of the social page.
	,	int $posterID			// <int> The UniID of the user posting content.
	,	int $clearance			// <int> The level of clearance required to view the post.
	,	int $attachmentID		// <int> The ID of the attachment to include.
	,	string $message = ""		// <str> The message to add to the post.
	,	string $link = ""			// <str> The link to return to.
	,	int $whenToPost = 0		// <int> The timestamp of when to post (default is now).
	,	array <str, mixed> $hashData = array()	// <str:mixed> The data that the hashtag system will need to know.
	,	string $origHandle = ""	// <str> The handle of the original poster, if applicable.
	): int						// RETURNS <int> The ID of the post, 0 on failure.
	
	// $postID = AppSocial::createPost($socialID, $posterID, $clearance, $attachmentID, $message, [$link], [$whenToPost], [$hashData], [$origHandle]);
	{
		// Prepare Values
		$message = (string) substr($message, 0, 600);
		$whenToPost = ($whenToPost == 0 ? time() : $whenToPost + 0);
		
		// Create the Post
		Database::startTransaction();
		
		if($pass = Database::query("INSERT INTO `social_posts` (`poster_id`, `post`, `orig_handle`, `clearance`, `attachment_id`, `date_posted`) VALUES (?, ?, ?, ?, ?, ?)", array($posterID, $message, $origHandle, $clearance, $attachmentID, $whenToPost)))
		{
			$postID = Database::$lastID;
			
			$link .= '#p' . $postID;
			
			if($pass = Database::query("INSERT INTO `users_posts` (uni_id, id) VALUES (?, ?)", array($socialID, $postID)))
			{
				$pass = Database::query("UPDATE social_data SET posts=posts+1 WHERE uni_id=?", array($posterID));
			}
		}
		
		if(Database::endTransaction(($pass and $postID)))
		{
			// Process the Comment (Hashtag, Credits, Notifications, etc) for public posts
			if($clearance == 0)
			{
				//Comment::process($posterID, $message, $link, $socialID, $hashData);
			}
			
			if($posterID != Me::$id)	{ $userData['handle'] = Me::$vals['handle']; }
			else 						{ $userData = User::get($posterID, "handle");}
			
			// Post a notification to someone's wall you're posting on
			if($socialID != $posterID)
			{				
				Notifications::create($socialID, $link, "@" . $userData['handle'] . " has posted on your wall.");
			}
			// Post notifications to friends
			else
			{
				$socialData = Database::selectOne("SELECT * FROM social_data WHERE uni_id=? LIMIT 1", array($posterID));
				
				// Search through the list of friends and followers
				$notifyList = AppFriends::getNotificationList($posterID);
				
				$uniIDList = array();
				foreach($notifyList as $notify)
				{
					if($clearance <= $notify['clearance'])
					{
						$uniIDList[] = (int) $notify['friend_id'];
					}
				}
				
				Notifications::createMultiple($uniIDList, $link, "@" . $userData['handle'] . " has posted a status.");
			}
			
			return $postID;
		}
		
		return 0;
	}
	
	
/****** Delete a Post ******/
	public function deletePost
	(
		int $uniID		// <int> The UniID of the person who owns the post.
	,	int $postID		// <int> The ID of the post.
	): bool				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $social->deletePost($uniID, $postID);
	{
		// Make sure you have clearance to delete this post
		if($this->clearance < 6 && Me::$clearance < 6)
		{
			return false;
		}
		
		// Make sure the post actually belongs to the user unless a mod is deleting
		if(Me::$clearance < 6)
		{
			if(!$check = Database::selectValue("SELECT uni_id FROM users_posts WHERE uni_id=? AND id=?", array($uniID, $postID)))
			{
				return false;
			}
		}
		
		// Delete the post
		Database::startTransaction();
		
		if($pass = Database::query("DELETE FROM users_posts WHERE uni_id=? AND id=? LIMIT 1", array($uniID, $postID)))
		{
			if($pass = Database::query("DELETE FROM social_posts WHERE id=? LIMIT 1", array($postID)))
			{
				$pass = Database::query("DELETE cp, c FROM comments_posts cp INNER JOIN comments c ON cp.id=c.id WHERE cp.post_id=?", array($postID));
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Repost someone else's post ******/
	public static function repost
	(
		int $postID			// <int> The ID of the post
	,	int $uniID			// <int> The UniID of the user reposting it.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $postID = AppSocial::repost($postID, $uniID);
	{
		// Get the original post data
		if($postData = AppSocial::getPostDirect($postID))
		{
			// Make sure the poster is not the same as the original poster
			if($uniID == $postData['poster_id'])
			{
				return false;
			}
			
			// Get the attachment of the original post
			if($attachment = Attachment::get($postData['attachment_id']))
			{
				// Create the Repost
				if(self::createPost($uniID, $uniID, 0, $attachment['id'], "", "", 0))
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	
/****** Return the URL for your header photo ******/
	public static function headerPhoto
	(
		int $uniID		// <int> The uniID of the person whose header photo you want to retrieve.
	,	string $handle		// <str> The user's handle.
	): array <str, mixed>				// RETURNS <str:mixed> the necessary url path (relative), FALSE on failure.
	
	// $urlPath = AppSocial::headerPhoto($uniID, $handle);
	{
		return array(
			"imageDir"		=> '/assets/pagePhotos'
		,	"mainDir"		=> ceil($uniID / 2000)
		,	"filename"		=> $handle
		,	"ext"			=> 'jpg'
		);
	}
	
	
/****** Display a Social Wall Feed ******/
	public static function displayFeed 
	(
		array <int, array<str, mixed>> $postList		// <int:[str:mixed]> A list of post entries.
	,	int $clearance = 0	// <int> The level of clearance you have to these posts.
	): void					// RETURNS <void> OUTPUTS the wall feed.
	
	// echo AppSocial::displayFeed($postList, [$clearance]);
	{
		foreach($postList as $post)
		{
			// Recognize Integers
			$post['id'] = (int) $post['id'];
			$post['poster_id'] = (int) $post['poster_id'];
			$post['date_posted'] = (int) $post['date_posted'];
			$post['attachment_id'] = (int) $post['attachment_id'];
			
			echo '
			<span class="post-anchor" id="p' . $post['id'] . '" style="display:block; position:relative; top:-60px; height:0px;"></span>
			<div class="comment">
				<div class="comment-left"><a href="/' . $post['handle'] . '"><img class="circimg" src="' . ProfilePic::image($post['poster_id'], "medium") . '"></a></div>
				<div class="comment-right">
					<div class="comment-top">
						<div class="comment-data"><span class="hide-600">' . (lcfirst($post['display_name']) != lcfirst($post['handle']) ? $post['display_name'] . ' ' : '') . '</span> <a ' . ($post['role'] != '' ? 'class="role-' . $post['role'] . '" ' : '') . 'href="/' . $post['handle'] . '">@' . $post['handle'] . '</a></div>
						<div class="comment-time-post">' . Time::fuzzy($post['date_posted']) . '</div>
					</div>
					<div class="comment-message">';
			
			// Display the person that was responsible for the original content, if applicable
			if($post['orig_handle'] != "")
			{
				echo '<div class="comment-repost">Source Content by <a href="/' . $post['orig_handle'] . '">@' . $post['orig_handle'] . '</a></div>';
			}
			
			// If there is an attachment included here
			if($post['attachment_id'] != 0)
			{
				if($attach = Attachment::get($post['attachment_id']))
				{
					// Show Image Attachment
					if($attach['type'] == Attachment::TYPE_IMAGE)
					{
						$class = ($attach['mobile-url'] != "" ? "post-image" : "post-image-mini");
						
						echo '
						<div>
							' . ($attach['source_url'] != "" ? '<a href="' . $attach['source_url'] . '">' : '') . Photo::responsive($attach['asset_url'], $attach['mobile-url'], 450, "", 450, $class) . ($attach['source_url'] != '' ? '</a>' : '');
						
						// Display the title, if provided
						if($attach['title'] != '')
						{
							echo '<div><strong>' . $attach['title'] . '</strong></div>';
						}
						
						// Display the description, if provided
						if($attach['description'] != '')
						{
							echo '<div>' . $attach['description'] . '</div>';
						}
						
						echo '
						</div>';
					}
					
					// Show Video Attachment
					else if($attach['type'] == Attachment::TYPE_VIDEO)
					{
						echo '<div>' . $attach['embed'] . '</div>';
					}
					
					// Show Article or Blog Attachment
					else if(in_array($attach['type'], array(Attachment::TYPE_ARTICLE, Attachment::TYPE_BLOG)))
					{
						$class = ($attach['mobile-url'] != "" ? "post-image" : "post-image-mini");
						
						echo '
						<div>
							<div style="float:left; width:45%; margin-right:20px;">
							' . ($attach['source_url'] != "" ? '<a href="' . $attach['source_url'] . '">' : '') . Photo::responsive($attach['asset_url'], $attach['mobile-url'], 950, "", 950, $class) . ($attach['source_url'] != '' ? '</a>' : '') . '
							</div>';
						
						// Display the title, if provided
						if($attach['title'] != '')
						{
							echo '<div><strong>' . $attach['title'] . '</strong></div>';
						}
						
						// Display the description, if provided
						if($attach['description'] != '')
						{
							echo '<div>' . $attach['description'] . '</div>';
						}
						
						echo '
						<p style="margin-bottom:0px;"><a href="' . $attach['source_url'] . '">... Read Full Article</a></p>
						</div>';
					}
				}
			}
			
			//$post['post'] = Comment::showSyntax($post['post']);
			echo html_entity_decode(nl2br(UniMarkup::parse($post['post']))) . '</div>
				</div>
				<div class="comment-wrap"><div class="extralinks"><a href="javascript:positionReplyBox(\'' . $post['handle'] . '\', ' . $post['id'] . ');"><span class="icon-comments"></span> Comments (' . $post['has_comments'] . ')</a>';
			
			if($clearance >= 6)
			{
				echo '<a href="/' . You::$handle . '?delete=' . $post['id'] . '&' . self::$linkProtect . '" onclick="return confirm(\'Are you sure you want to delete this?\');"><span class="icon-circle-close"></span> Delete</a>';
			}
			
			echo '</div></div>';
			
			// Reply Box
			echo '
				<div id="replies-' . $post['id'] . '" style="display:none; margin-bottom:4px;"></div>';
			
			echo '
			</div>';
		}
	}
}