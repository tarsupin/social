<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Run the Repost Function
if(isset($_GET['id']))
{
	AppSocial::repost((int) $_GET['id'], Me::$id);
}

// Return to the page you were previously on
header("Location: /" . Me::$vals['handle']); exit;
