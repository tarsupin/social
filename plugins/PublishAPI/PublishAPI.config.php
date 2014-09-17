<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class PublishAPI_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "api";
	public $pluginName = "PublishAPI";
	public $title = "Social Publishing API";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Allows content to be posted to a social page from an external site.";
	
	public $data = array();
	
}