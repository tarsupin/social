<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(!isset($userData))
{
	header("Location: /"); exit;
}

// Get page ID
if(isset($url[2]))
{
	$pageID = explode("-", $url[2]);
	$pageID = (int) $pageID[0];
}

// Delete page
if(isset($url[2]) && $link = Link::clicked())
{
	if($link = "page-delete")
	{
		if(AppPage::deletePage(Me::$id, $pageID))
		{
			header("Location: /" . Me::$vals['handle'] . "/pages"); exit;
		}
		else
		{
			Alert::error("Not Deleted", "The page could not be deleted.");
		}
	}
}

// Display list of pages
$list = AppPage::getList((int) $userData['uni_id']);
$insert = '';
if(Me::$id == $userData['uni_id'])
{
	$insert .= '<li class="menu-slot"><a href="/' . Me::$vals['handle'] . '/pages/create-page">Create Page</a></li>';
}
if(!isset($list[0])) $list[0] = array();
foreach($list[0] as $l)
{
	$insert .= '<li class="menu-slot"><a href="/' . $userData['handle'] . '/pages/' . $l['page_id'] . '-' . $l['url_slug'] . '">' . $l['title'] . '</a>';
	if(isset($list[(int) $l['page_id']]))
	{
		$insert .= ' <span class="icon-circle-right"></span><ul>';
		foreach($list[(int) $l['page_id']] as $s)
			$insert .= '<li class="dropdown-slot"><a href="/' . $userData['handle'] . '/pages/' . $s['page_id'] . '-' . $s['url_slug'] . '">' . $s['title'] . '</a></li>';
		$insert .= '</ul>';
	}
	$insert .= '</li>';
}
if($insert)
{
	WidgetLoader::add("UniFactionMenu", 20, '
		<div class="menu-wrap hide-600">
			<ul class="menu">
				' . $insert . '
			</ul>
		</div>');
}

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

// Display specific page if chosen
if(isset($url[2]))
{
	if($page = AppPage::getPage((int) $userData['uni_id'], $pageID))
	{
		echo '
	<style>
		.inner-box img { max-width:100%; }
	</style>
	<div class="overwrap-box">
		<div class="overwrap-line" style="margin-bottom:10px;">
			<div class="overwrap-name">' . $page['title'] . (Me::$id == $userData['uni_id'] ? ' &nbsp;<a href="/' . Me::$vals['handle'] . '/pages/' . $url[2] . '?delete&' . Link::prepare("page-delete") . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this page?\');"><span class="icon-trash"></span></a> &nbsp;<a href="/' . Me::$vals['handle'] . '/pages/move-page?id=' . $page['page_id'] . '" title="Move"><span class="icon-wand"></span></a> &nbsp; <a href="/' . Me::$vals['handle'] . '/pages/edit-page?id=' . $page['page_id'] . '" title="Edit"><span class="icon-pencil"></span></a>' : '') . '</div>
		</div>
		<div class="inner-box">
			<div style="display:inline-block; text-align:center;">
			' . html_entity_decode(nl2br(UniMarkup::parse($page['body']))) . '
			</div>
		</div>
	</div>';
	}
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
