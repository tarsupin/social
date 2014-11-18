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
	
	public string $linkProtect = "";		// <str> The link protection for any actions you make.
	
	
/****** Construct the user's social data ******/
	public function __construct
	(
		int $uniID			// <int> The UniID of the social page being accessed.
	): void					// RETURNS <void>
	
	// $socialData = new AppSocial($uniID);
	{
		$this->uniID = $uniID;
		$this->data = AppSocial::getData($uniID);
		$this->linkProtect = Link::prepare("uni6-social");
		
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
		
		// Check if you're a moderator or staff member
		else if(Me::$clearance >= 6)
		{
			$clearance = Me::$clearance;
		}
		
		// Check if you're a friend of the user
		else if(AppFriends::isFriend(Me::$id, $this->uniID))
		{
			$clearance = 4;
		}
		
		// Set the access levels
		$this->clearance = $clearance;
		$this->canAccess = $this->data['perm_access'] <= $clearance ? true : false;
		$this->canPost = $this->data['perm_post'] <= $clearance ? true : false;
		$this->canComment = $this->data['perm_comment'] <= $clearance ? true : false;
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
		return Database::query("REPLACE INTO social_data (uni_id, perm_access, perm_post, perm_comment, perm_approval) VALUES (?, ?, ?, ?, ?)", array($userData['uni_id'], 0, 2, 2, 0));
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
		return Database::selectMultiple("SELECT sp.*, u.handle, u.display_name FROM users_posts spu INNER JOIN social_posts sp ON spu.id=sp.id AND sp.clearance <= ? AND sp.date_posted <= ? INNER JOIN users u ON sp.poster_id=u.uni_id WHERE spu.uni_id=? ORDER BY spu.id DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), array($clearance, time(), $uniID));
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
		return Database::selectOne("SELECT * FROM social_posts WHERE id=? LIMIT 1", array($postID));
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
		$message = (string) substr($message, 0, 1000);
		$whenToPost = ($whenToPost == 0 ? time() : $whenToPost + 0);
		
		// Create the Post
		Database::startTransaction();
		
		if($pass = Database::query("INSERT INTO `social_posts` (`poster_id`, `post`, `orig_handle`, `clearance`, `attachment_id`, `date_posted`) VALUES (?, ?, ?, ?, ?, ?)", array($posterID, $message, $origHandle, $clearance, $attachmentID, $whenToPost)))
		{
			$postID = Database::$lastID;
			
			$pass = Database::query("INSERT INTO `users_posts` (uni_id, id) VALUES (?, ?)", array($socialID, $postID,));
		}
		
		if(Database::endTransaction(($pass and $postID)))
		{
			// Process the Comment (Hashtag, Credits, Notifications, etc) for public posts
			if($clearance == 0)
			{
				Comment::process($posterID, $message, $link, $socialID, $hashData);
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
		if($this->clearance < 6)
		{
			return false;
		}
		
		// Make sure the post actually belongs to the user
		if(!$check = Database::selectValue("SELECT uni_id FROM users_posts WHERE uni_id=? AND id=?", array($uniID, $postID)))
		{
			return false;
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
	public function displayFeed 
	(
		int $page = 1		// <int> The page to begin posting feeds on.
	,	int $showNum = 15	// <int> The number of results to return.
	): void					// RETURNS <void> OUTPUTS the wall feed.
	
	// echo AppSocial::displayFeed($page, $showNum);
	{
		$postList = $this->getUserPosts($this->uniID, $this->clearance, $page, $showNum);
		
		foreach($postList as $post)
		{
			// Recognize Integers
			$post['id'] = (int) $post['id'];
			$post['poster_id'] = (int) $post['poster_id'];
			$post['date_posted'] = (int) $post['date_posted'];
			$post['attachment_id'] = (int) $post['attachment_id'];
			
			echo '
			<div class="comment">
				<div class="comment-left"><a href="/' . $post['handle'] . '"><img class="circimg" src="' . ProfilePic::image($post['poster_id'], "medium") . '"></a></div>
				<div class="comment-right">
					<div class="comment-top">
						<div class="comment-data"><span>' . $post['display_name'] . '</span> <a class="handle" href="/' . $post['handle'] . '">@' . $post['handle'] . '</a></div>
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
			
			echo nl2br(Comment::showSyntax($post['post'])) . '</div>
				</div>
				<div class="comment-wrap"><div class="extralinks"><a href="javascript:positionReplyBox(\'' . You::$handle . '\', ' . $post['id'] . ');"><span class="icon-comments"></span> Show Comments (' . $post['has_comments'] . ')</a>';
			
			if($this->clearance >= 6)
			{
				echo '<a href="/' . You::$handle . '?delete=' . $post['id'] . '&' . $this->linkProtect . '"><span class="icon-circle-close"></span> Delete</a>';
			}
			
			echo '<a href="#">. . . More</a></div></div>';
			
			// Reply Box
			echo '
				<div id="replies-' . $post['id'] . '" style="display:none; margin-bottom:4px;"></div>';
			
			echo '
			</div>';
		}
	}
	
	
/****** Display a Social Wall Feed ******/
	public static function showFeed
	(
		array <int, array<str, mixed>> $socialPosts	// <int:[str:mixed]> The data that contains all of the social posts.
	,	array <str, bool> $clearance		// <str:bool> The clearance levels for the page.
	): void					// RETURNS <void> OUTPUTS the wall feed.
	
	// echo AppSocial::showFeed($socialPosts);
	{
		// Comment replies
		echo '
		<div class="post-footer">';
		
		// Display the Post Message
		if($post['post'] != "")
		{
			echo '
			<div>
				<a href="/' . User::$cache[$pID]['handle'] . '"><img class="circimg" src="' . ProfilePic::image($pID, "large") . '" /></a>
				<p class="post-message">' . nl2br(Comment::showSyntax($post['post'])) . '</p>
			</div>';
		}
		
		echo '
			<div class="extralinks">
				<a href="/share?id=' . $post['id'] . '">Share</a>
				<a href="/' . You::$handle . "?" . Link::prepareData("send-tip-social", You::$id) . '">Tip ' . You::$name . '</a>';
				
				if($clearance['admin'])
				{
					echo '
					<a href="/' . You::$handle . '?' . Link::prepareData("delete-post", $post['id']) . '" onclick="return confirm(\'Are you sure you want to delete this?\');">Delete</a>';
				}
		
		echo '
			</div>';
		
		// Show Comments
		if($post['has_comments'] > 0)
		{
			// Get Comments
			$comments = AppComment::getList($post['id'], 0, 3, "DESC");
			$comLen = count($comments);
			
			// Reverse the order (since you're providing the last three)
			if($comLen > 1)
			{
				$comments = array_reverse($comments);
			}
			
			// Provide option to show all comments
			if($post['has_comments'] > $comLen)
			{
				echo '
				<div class="block-group block-interior">
					View all comments
				</div>';
			}
			
			// Display Last Three Comments
			foreach($comments as $comment)
			{
				// Recognize Integers
				$cpID = (int) $comment['uni_id'];
				$comment['id'] = (int) $comment['id'];
				$comment['date_posted'] = (int) $comment['date_posted'];
				
				if(!isset(User::$cache[$cpID]))
				{
					User::$cache[$cpID] = Database::selectOne("SELECT handle, display_name FROM users WHERE uni_id=? LIMIT 1", array($pID));
				}
				
				// Display the Comment
				echo '
				<div>
					<div style="float:left; margin-left:12px;"><a href="/' . User::$cache[$cpID]['handle'] . '"><img class="circimg-small" src="' . ProfilePic::image($cpID, "small") . '" /></a></div>
					<p class="post-message">' . nl2br(Comment::showSyntax($comment['comment'])) . '
						<br /><span style="font-size:0.8em;">' . User::$cache[$cpID]['display_name'] . ' &bull; ' . Time::fuzzy($comment['date_posted']) . '</span>
					</p>
				</div>';
			}
		}
		
		// Provide the ability to post a comment (if allowed)
		if(isset($clearance['comment']))
		{
			echo '
				<div>
					<form class="uniform" id="comment_' . $post['id'] . '"  action="/' . You::$handle . '" method="post">' . Form::prepare("social-post") . '
						<img class="circimg-small post-avi" src="' . ProfilePic::image(Me::$id) . '" />
						<p class="comment-box-wrap">
						<textarea class="comment-box" name="commentBox[' . $post['id'] . ']" value="" placeholder="Add a Comment . . ." onkeypress="return commentPost(event, ' . $post['id'] . ');"></textarea>
						<br /><input class="comment-box-input" type="submit" name="subCom_' . $post['id'] . '" value="Post Comment" hidefocus="true" tabindex="-1" />
						</p>
					</form>
				</div>';
		}
		
		echo '
		</div>';
	}
}