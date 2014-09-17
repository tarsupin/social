<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Logged In Functionality
if(Me::$loggedIn)
{
	// Notifications (if available)
	WidgetLoader::add("SidePanel", 1, Notifications::sideWidget());
	
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box">
		<ul class="panel-slots">
			<li class="nav-slot' . (in_array($url[0], array("", "home")) ? " nav-active" : "") . '"><a href="/">Social Feed<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == Me::$vals['handle'] ? " nav-active" : "") . '"><a href="/' . Me::$vals['handle'] . '">My Wall<span class="icon-circle-right nav-arrow"></span></a></li>
			<li class="nav-slot' . ($url[0] == "post" ? " nav-active" : "") . '"><a href="/post">Advanced Post<span class="icon-circle-right nav-arrow"></span></a></li>
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

WidgetLoader::add("SidePanel", 70, '
<div class="side-module">
	<div class="side-header">
		<span class="icon-group"></span> Who To Connect With
	</div>
	<div class="side-photo-caption">
		<div><img src="' . CDN . '/images/minecraft.png" /><strong>Minecraft</strong><br />Minecraft is a game where you build stuff and it\'s cool.</div>
	</div>
	<div class="side-photo-caption">
		<div><img src="' . CDN . '/images/minecraft.png" /><strong>Someone Interesting</strong><br />This person likes to throw corn at strangers and share those videos on YouTube.</div>
	</div>
</div>');
