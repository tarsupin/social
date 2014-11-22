<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// The Main Display
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

/*
$fullList = Database::selectMultiple("SELECT uni_id, friend_id FROM old_friends_list", array());

Database::startTransaction();

foreach($fullList as $entry)
{
	Database::query("REPLACE INTO friends_list (uni_id, friend_id, clearance) VALUES (?, ?, ?)", array($entry['uni_id'], $entry['friend_id'], 4));
}

Database::endTransaction();
*/

echo'
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
