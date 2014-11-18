<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class PublishArticleAPI_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "api";
	public $pluginName = "PublishArticleAPI";
	public $title = "Article Publishing API";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Allows articles to be shared to the social system.";
	
	public $data = array();
	
}