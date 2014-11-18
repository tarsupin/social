<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppComment Plugin ------
-----------------------------------------

This plugin handles comments on the social site.


-------------------------------
------ Methods Available ------
-------------------------------

$comments = AppComment::getList($postID, $startNum, $showNum, "DESC");

$commentID = AppComment::create($threadID, $uniID, $objectID, "Wow! Awesome!", $replyThread, $linkToComment, $toUniID);

AppComment::edit($commentID, $comment);		// Edits a comment
AppComment::delete($commentID);				// Deletes a comment

*/

abstract class AppComment {
	
	
/****** Get Comments of a Post ******/
	public static function getList
	(
		$postID				// <int> The ID of the post.
	,	$page = 1			// <int> The starting number to get comments from.
	,	$showNum = 10		// <int> The number of comments to return.
	,	$order = "ASC"		// <str> The direction to sort by "ASC" or "DESC"
	)						// RETURNS <int:[str:mixed]> comments for the object, FALSE on failure.
	
	// $comments = AppComment::getList($postID, $startNum, $showNum, "DESC");
	{
		return Database::selectMultiple("SELECT c.id, c.uni_id, c.comment, c.date_posted FROM comments_posts cp INNER JOIN comments c ON c.id=cp.id WHERE cp.post_id=? ORDER BY cp.id " . ($order == "ASC" ? "ASC" : "DESC") . " LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), array($postID));
	}
	
	
/****** Get Comments of a Post through AJAX ******/
	public static function getListAJAX
	(
		$postID				// <int> The ID of the post.
	,	$page = 1			// <int> The starting number to get comments from.
	,	$showNum = 10		// <int> The number of comments to return.
	,	$order = "ASC"		// <str> The direction to sort by "ASC" or "DESC"
	)						// RETURNS <int:[str:mixed]> comments for the object, FALSE on failure.
	
	// $comments = AppComment::getListAJAX($postID, $startNum, $showNum, "DESC");
	{
		return Database::selectMultiple("SELECT c.id, c.uni_id, c.comment, c.date_posted, u.handle, u.display_name FROM comments_posts cp INNER JOIN comments c ON c.id=cp.id INNER JOIN users u ON c.uni_id=u.uni_id WHERE cp.post_id=? ORDER BY cp.id " . ($order == "ASC" ? "ASC" : "DESC") . " LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), array($postID));
	}
	
	
/****** Create a Comment ******/
	public static function create
	(
		$postID					// <int> The thread ID that you're posting in.
	,	$uniID					// <int> The Uni-Account of the user that is commenting.
	,	$comment				// <str> The comment to post.
	,	$link = ""				// <str> The link to this particular comment.
	,	$toUniID = 0			// <int> The UniID of the target being commented to.
	,	$isPublic = true		// <bool> TRUE if this is a public post.
	)							// RETURNS <int> ID of the new comment, 0 if failed.
	
	// $commentID = AppComment::create($postID, $uniID, "Wow! Awesome!", $linkToComment, $toUniID, [$isPublic]);
	{
		Database::startTransaction();
		
		// Insert the comment and structure
		if(!$pass = Database::query("INSERT INTO `comments` (`uni_id`, `comment`, `date_posted`) VALUES (?, ?, ?)", array($uniID, $comment, time())))
		{
			Database::endTransaction(false);
			
			return 0;
		}
		
		$commentID = Database::$lastID;
		
		// Update the comment count of a thread
		Database::query("UPDATE social_posts SET has_comments=has_comments+1 WHERE id=? LIMIT 1", array($postID));
		
		$pass = Database::query("INSERT INTO comments_posts (post_id, id) VALUES (?, ?)", array($postID, $commentID));
		
		if(Database::endTransaction($pass))
		{
			if($isPublic)
			{
				// Process the Comment (Hashtag, Credits, Notifications, etc)
				Comment::process($uniID, $comment, $link, $toUniID);
			}
			
			return $commentID;
		}
		
		return 0;
	}
	
	
/****** Edit a Comment ******/
	public static function edit
	(
		$commentID		// <int> The ID of the comment to edit.
	,	$comment		// <str> The new comment that you're switching it to.
	)					// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::edit($commentID, "Thanks guys! Edit: Sorry, fixed grammar.");
	{
		return Database::query("UPDATE `comments` SET `comment`=? WHERE id=? LIMIT 1", array($comment, $commentID));
	}
	
	
/****** Delete a Comment ******/
	public static function delete
	(
		$postID			// <int> The post ID that the comment was added to.
	,	$commentID		// <int> The ID of the comment to delete.
	)					// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::delete($postID, $commentID);
	{
		Database::startTransaction();
		
		if($pass = Database::query("DELETE FROM `comments` WHERE id=? LIMIT 1", array($commentID)))
		{
			$pass = Database::query("DELETE FROM comments_posts WHERE post_id=? AND id=? LIMIT 1", array($postID, $commentID));
		}
		
		return Database::endTransaction($pass);
	}
	
}
