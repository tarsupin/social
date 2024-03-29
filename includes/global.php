<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Logged In Functionality
if(Me::$loggedIn)
{
	$url1 = isset($url[1]) ? $url[1] : '';
	
	// Main Navigation
	WidgetLoader::add("MobilePanel", 10, '
	<div class="panel-box">
		<ul class="panel-slots">
			<li class="nav-slot' . (in_array($url[0], array("", "home")) ? " nav-active" : "") . '"><a href="/">Social Feed<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == Me::$vals['handle'] && !isset($url[1]) ? " nav-active" : "") . '"><a href="/' . Me::$vals['handle'] . '">My Wall<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "post" ? " nav-active" : "") . '"><a href="/post">Advanced Post<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "settings" ? " nav-active" : "") . '"><a href="/settings">Settings<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "friends" and !$url ? " nav-active" : "") . '"><a href="/friends">Friends<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . (($url[0] == "friends" and $url1 == "requests") ? " nav-active" : "") . '"><a href="/friends/requests">Friend Requests<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . (($url[0] == "friends" and $url1 == "followers") ? " nav-active" : "") . '"><a href="/friends/followers">My Followers<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . (($url[0] == "friends" and $url1 == "following") ? " nav-active" : "") . '"><a href="/friends/following">Who I\'m Following<span class="icon-circle-right nav-arrow"></span></a></li>
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
		</ul>
	</div>');
}

// Load the Social Menu
require(SYS_PATH . "/controller/includes/social-menu.php");

// UniFaction Dropdown Menu
$handle = Me::$loggedIn ? Me::$vals['handle'] : '';

if(Me::$loggedIn)
{
	WidgetLoader::add("UniFactionMenu", 10, '
	<div class="menu-wrap hide-600">
		<ul class="menu">
			' . (isset($uniMenu) ? $uniMenu : '') . '
			<li class="menu-slot' . ($url[0] == "" ? " menu-active" : "") . '"><a href="/">Feed</a>
			</li><li class="menu-slot' . ($url[0] == $handle && !isset($url[1]) ? " menu-active" : "") . '"><a href="/' . $handle . '">My Wall</a>
			</li><li class="menu-slot' . ($url[0] == "post" ? " menu-active" : "") . '"><a href="/post">Adv. Post</a>
			</li><li class="menu-slot' . ($url[0] == "settings" ? " menu-active" : "") . '"><a href="/settings">Settings</a>
			</li><li class="menu-slot' . ($url[0] == "friends" ? " menu-active" : "") . '"><a href="/friends">Friends</a><ul><li class="dropdown-slot"><a href="/friends/requests">Friend Requests</a></li><li class="dropdown-slot"><a href="/friends/followers">My Followers</a></li><li class="dropdown-slot"><a href="/friends/following">Who I\'m Following</a></li></ul></li>
		</ul>
	</div>');
}
else
{
	WidgetLoader::add("UniFactionMenu", 10, '
	<div class="menu-wrap hide-600">
		<ul class="menu">
			<li class="dropdown-slot"><a href="/login">Login</a></li>
		</ul>
	</div>');
}	

// Complete page title (if available)
if(isset($config['pageTitle']) and $config['pageTitle'] != "")
{
	$config['pageTitle'] = $config['site-name'] . " > " . $config['pageTitle'];
}
if(You::$id)
{
	// don't display widgets on personal pages
	if(!isset($url[1]) || $url[1] != "pages")
		$config['active-hashtag'] = You::$handle;
}

// Base style sheet for this site
Metadata::addHeader('<link rel="stylesheet" href="' . CDN . '/css/unifaction-2col.css" />');
Metadata::addHeader('<link rel="stylesheet" href="' . CDN . '/css/unifaction-text.css" />');