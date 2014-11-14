<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Logged In Functionality
if(Me::$loggedIn)
{
	// UniFaction Dropdown Menu
	$extraColumns = '
	<li class="menu-slot' . ($url[0] == Me::$vals['handle'] ? " menu-active" : "") . '"><a href="/' . Me::$vals['handle'] . '">My Wall</a>
	
	</li><li class="menu-slot' . ($url[0] == "" ? " menu-active" : "") . '"><a href="/">Feed</a>
	
	</li><li class="menu-slot' . ($url[0] == "post" ? " menu-active" : "") . '"><a href="/post">Adv. Post</a>
	
	</li><li class="menu-slot' . ($url[0] == "settings" ? " menu-active" : "") . '"><a href="/settings">Settings</a>
	
	</li><li class="menu-slot' . ($url[0] == "friends" ? " menu-active" : "") . '"><a href="/friends">Friends</a><ul><li class="dropdown-slot"><a href="/friends/requests">Friend Requests</a></li></ul></li>';
	
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box">
		<ul class="panel-slots">
			<li class="nav-slot' . (in_array($url[0], array("", "home")) ? " nav-active" : "") . '"><a href="/">Social Feed<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == Me::$vals['handle'] ? " nav-active" : "") . '"><a href="/' . Me::$vals['handle'] . '">My Wall<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "post" ? " nav-active" : "") . '"><a href="/post">Advanced Post<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "settings" ? " nav-active" : "") . '"><a href="/settings">Settings<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "friends" ? " nav-active" : "") . '"><a href="/friends">Friends<span class="icon-circle-right nav-arrow"></span></a></li>
		</ul>
	</div>');
}
else
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box">
		<ul class="panel-slots">
			<li class="nav-slot"><a href="/login">Login<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot"><a href="/">Find Friends<span class="icon-circle-right nav-arrow"></span></a></li>
		</ul>
	</div>');
}


// Load the Social Menu
require(SYS_PATH . "/controller/includes/social-menu.php");