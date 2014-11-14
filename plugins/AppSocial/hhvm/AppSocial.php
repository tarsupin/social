<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppSocial Plugin ------
-----------------------------------------

This plugin provides several social handling tools and allows users to manage their social pages.


-------------------------------
------ Methods Available ------
-------------------------------

$social		= AppSocial::getPage($uniID);
$perm		= AppSocial::clearance($uniID, $socialID)			// Returns your clearance for a profile.

$postData	= AppSocial::getPost($uniID, $postID)									// Retrieves post data.
$postID		= AppSocial::createPost($socialID, $uniID, $attachmentID, $message);	// Creates a post.
			  AppSocial::deletePost($uniID, $postID)								// Removes a post from the profile.

$urlPath	= AppSocial::headerPhoto($uniID);		// Returns the URL path for your photo header.

*/

abstract class AppSocial {
	
	
/****** Get The User's Social Page Data ******/
	public static function getPage
	(
		int $uniID			// <int> The ID of the user to recover the social page from.
	): array <str, mixed>					// RETURNS <str:mixed> page requirements and settings, or array() if failed.
	
	// $socialPage = AppSocial::getPage($uniID);
	{
		$permissions = Database::selectOne("SELECT has_headerPhoto, description, perm_access, perm_post, perm_comment, perm_approval FROM social_page WHERE uni_id=? LIMIT 1", array($uniID));
		
		if(!$permissions)
		{
			// If the social page doesn't exist, lets create it
			
			// Gather user's information
			if($userData = User::get($uniID, "uni_id"))
			{
				// Create the page
				if(Database::query("INSERT INTO social_page (uni_id, perm_access, perm_post, perm_comment, perm_approval) VALUES (?, ?, ?, ?, ?)", array($userData['uni_id'], 5, 9, 5, 0)))
				{
					$permissions = Database::selectOne("SELECT has_headerPhoto, description, perm_access, perm_post, perm_comment, perm_approval FROM social_page WHERE uni_id=? LIMIT 1", array($uniID));
				}
			}
		}
		
		// Recognize Integers
		$permissions['has_headerPhoto'] = (int) $permissions['has_headerPhoto'];
		$permissions['perm_access'] = (int) $permissions['perm_access'];
		$permissions['perm_post'] = (int) $permissions['perm_post'];
		$permissions['perm_comment'] = (int) $permissions['perm_comment'];
		$permissions['perm_approval'] = (int) $permissions['perm_approval'];
		
		return $permissions;
	}
	
	
/****** Get The User's Clearance for the Social Page ******/
	public static function clearance
	(
		int $uniID				// <int> The ID of the user to check clearance for.
	,	int $viewClearance		// <int> The clearance level for viewing: 2=follower, 6=friend
	,	int $interactClearance	// <int> The clearance level for interaction: 6=friend
	,	array $permissions		// <array> An array of permissions granted by the social page.
	): array <str, bool>						// RETURNS <str:bool> the clearance the user has on this page.
	
	// $clearance = AppSocial::clearance($uniID, $viewClearance, $interactClearance, $permissions);
	{
		/*
			Permissions:
			'access'		// 0 = guests, 5 = friends
			'post'			// 5 = friends, 7 = mods, 8 = admin, 9 = superadmin
			'comment'		// 0 = guests, 5 = friends, 7 = mods, 8 = admins, 9 = superadmin
			'approval'		// 0 = nobody, 1 = guests, 6 = friends, 8 = mods
		*/
		
		// Prepare default permissions
		if(!$permissions)
		{
			$permissions['perm_access'] = 0;		// Guests can view
			$permissions['perm_post'] = 8;			// Mods can post (or users with uber-access)
			$permissions['perm_comment'] = 6;		// Friends can comment
			$permissions['perm_approval'] = 0;		// Nobody requires special approval
		}
		
		$clearance = array();
		
		// If you own the page, or if the user is a moderator
		$clearance['admin'] = (Me::$id == You::$id or Me::$clearance >= 6) ? true : false;
		
		if($viewClearance >= $permissions['perm_access']) { $clearance['access'] = true; }
		if($interactClearance >= $permissions['perm_post']) { $clearance['post'] = true; }
		if($interactClearance >= $permissions['perm_comment']) { $clearance['comment'] = true; }
		if($interactClearance < $permissions['perm_approval']) { $clearance['approval'] = true; }
		
		if($clearance['admin'])
		{
			$clearance['perm_access'] = true;
		}
		
		return $clearance;
	}
	
	
/****** Get List of Posts from a user ******/
	public static function getPostList
	(
		int $uniID			// <int> The UniID that created the post.
	,	int $page = 1		// <int> The page that you're looking at.
	,	int $showNum = 15	// <int> The number of posts to show.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> the data for the list of posts, array() on failure.
	
	// $postList = AppSocial::getPostList($uniID, [$page], [$showNum]);
	{
		return Database::selectMultiple("SELECT sp.* FROM social_posts_user spu INNER JOIN social_posts sp ON spu.id=sp.id WHERE spu.uni_id=? ORDER BY spu.id DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), array($uniID));
	}
	
	
/****** Get Data about the Post ******/
	public static function getPost
	(
		int $uniID			// <int> The UniID that created the post.
	,	int $postID			// <int> The ID of the post.
	): array <str, mixed>					// RETURNS <str:mixed> the sql data of the post, array() on failure.
	
	// $postData = AppSocial::getPost($uniID, $postID);
	{
		return Database::selectOne("SELECT sp.* FROM social_posts_user spu INNER JOIN social_posts sp ON spu.id=sp.id WHERE spu.uni_id=? AND spu.id=? LIMIT 1", array($uniID, $postID));
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
	,	int $attachmentID		// <int> The ID of the attachment to include.
	,	string $message = ""		// <str> The message to add to the post.
	,	string $link = ""			// <str> The link to return to.
	,	int $whenToPost = 0		// <int> The timestamp of when to post (default is now).
	,	array <str, mixed> $hashData = array()	// <str:mixed> The data that the hashtag system will need to know.
	): int						// RETURNS <int> The ID of the post, 0 on failure.
	
	// $postID = AppSocial::createPost($socialID, $posterID, $attachmentID, $message, [$link], [$whenToPost], [$hashData]);
	{
		// Prepare Values
		$message = (string) substr($message, 0, 1000);
		$whenToPost = ($whenToPost == 0 ? time() : $whenToPost + 0);
		
		// Create the Post
		Database::startTransaction();
		
		if($pass = Database::query("INSERT INTO `social_posts` (`poster_id`, `post`, `attachment_id`, `date_posted`) VALUES (?, ?, ?, ?)", array($posterID, $message, $attachmentID, $whenToPost)))
		{
			$postID = Database::$lastID;
			
			$pass = Database::query("INSERT INTO `social_posts_user` (uni_id, id) VALUES (?, ?)", array($socialID, $postID));
		}
		
		if(Database::endTransaction(($pass and $postID)))
		{
			// Process the Comment (Hashtag, Credits, Notifications, etc)
			Comment::process($posterID, $message, $link, $socialID, $hashData);
			
			return $postID;
		}
		
		return 0;
	}
	
	
/****** Delete a Post ******/
	public static function deletePost
	(
		int $uniID		// <int> The UniID of the person who owns the post.
	,	int $postID		// <int> The ID of the post.
	): bool				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppSocial::deletePost($uniID, $postID);
	{
		Database::startTransaction();
		
		if($pass = Database::query("DELETE FROM social_posts_user WHERE uni_id=? AND id=? LIMIT 1", array($uniID, $postID)))
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
				if(self::createPost($uniID, $uniID, $attachment['id'], "", "", 0))
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
	
	// $urlPath = AppSocial::headerPhoto($uniID);
	{
		return array(
			"imageDir"		=> '/assets/headerPhotos'
		,	"mainDir"		=> ceil($uniID / 25000)
		,	"secondDir"		=> substr($handle, 0, 1)
		,	"filename"		=> $handle
		,	"ext"			=> 'jpg'
		);
	}
	
	
/****** Display a Social Wall Feed ******/
	public static function displayFeed
	(
		array <int, array<str, mixed>> $socialPosts	// <int:[str:mixed]> The data that contains all of the social posts.
	,	array <str, bool> $clearance		// <str:bool> The clearance levels for the page.
	): void					// RETURNS <void> OUTPUTS the wall feed.
	
	// echo AppSocial::displayFeed($socialPosts);
	{
		// Prepare Values
		$timestamp = time();
		
		// Cycle through the page's posts
		foreach($socialPosts as $post)
		{
			// Recognize Integers
			$post['id'] = (int) $post['id'];
			$post['date_posted'] = (int) $post['date_posted'];
			$post['attachment_id'] = (int) $post['attachment_id'];
			
			// Don't show posts that aren't indicated as being relevant yet
			if($post['date_posted'] > $timestamp)
			{
				continue;
			}
			
			// Get the user's display name if we don't have it already (reduces times caled)
			$pID = (int) $post['poster_id'];
			
			if(!isset(User::$cache[$pID]))
			{
				User::$cache[$pID] = Database::selectOne("SELECT handle, display_name FROM users WHERE uni_id=? LIMIT 1", array($pID));
			}
			
			echo '
			<div class="post">
				<div class="post-header">
					<div><span class="post-icon icon-comment"></span> &nbsp; <a href="/' . User::$cache[$pID]['handle'] . '">' . User::$cache[$pID]['display_name'] . '</a> Posted</div>
					<div><span class="post-icon icon-calendar"></span>  &nbsp; ' . Time::fuzzy($post['date_posted']) . '</div>
				</div>
				<div style="overflow:hidden;">';
			
			// Display an attachment (if available)
			if($post['attachment_id'] != 0)
			{
				if($attach = Attachment::get($post['attachment_id']))
				{
					// Display the person that was responsible for the original content, if applicable
					if(isset($attach['params']['poster_handle']) and $attach['params']['poster_handle'] != Me::$vals['handle'])
					{
						echo '<div style="color:gray; padding:2px;">Reposted from <a href="/' . $attach['params']['poster_handle'] . '">' . $attach['params']['poster_handle'] . '</a></div>';
					}
					
					// Show Image Attachment
					if($attach['type'] == Attachment::TYPE_IMAGE)
					{
						$class = ($attach['mobile-url'] != "" ? "post-image" : "post-image-mini");
						
						echo '
						<div class="post-image-wrap">
							' . ($attach['source_url'] != "" ? '<a href="' . $attach['source_url'] . '">' : '') . Photo::responsive($attach['asset_url'], $attach['mobile-url'], 450, "", 450, $class) . ($attach['source_url'] != '' ? '</a>' : '') . '
							<div style="margin:10px 22px 10px 22px;">';
							
							// Display the title, if provided
							if($attach['title'] != '')
							{
								echo '<div style="clear:both;"><strong>' . $attach['title'] . '</strong></div>';
							}
							
							// Display the description, if provided
							if($attach['description'] != '')
							{
								echo '<div style="clear:both;">' . $attach['description'] . '</div>';
							}
							
							echo '
							</div>
						</div>';
					}
					
					// Show Video Attachment
					else if($attach['type'] == Attachment::TYPE_VIDEO)
					{
						echo '<div style="padding-bottom:14px;">' . $attach['embed'] . '</div>';
					}
					
					// Show Article or Blog Attachment
					else if(in_array($attach['type'], array(Attachment::TYPE_ARTICLE, Attachment::TYPE_BLOG)))
					{
						$class = ($attach['mobile-url'] != "" ? "post-image" : "post-image-mini");
						
						echo '
						<div class="post-content-wrap">
							<div class="post-content-left">
							' . ($attach['source_url'] != "" ? '<a href="' . $attach['source_url'] . '">' : '') . Photo::responsive($attach['asset_url'], $attach['mobile-url'], 950, "", 950, $class) . ($attach['source_url'] != '' ? '</a>' : '') . '
							</div>
							<div>';
							
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
							<p style="margin:10px 0px 0px 0px;"><a href="' . $attach['source_url'] . '">... Read Full Article</a></p>
							</div>
						</div>';
					}
				}
			}
			
			echo '
			</div>
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
				</div>
			</div>';
		}
	}
}