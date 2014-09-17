<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Prepare the Featured Widget Data
$hashtag = "";
$categories = array("articles", "people");

// Create a new featured content widget
$featuredWidget = new FeaturedWidget($hashtag, $categories);

// If you want to display the FeaturedWidget by itself:
echo $featuredWidget->get();