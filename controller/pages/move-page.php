<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

if(!isset($_GET['id']))
{
	header("Location: /" . Me::$vals['handle'] . "/pages"); exit;
}
$_GET['id'] = (int) $_GET['id'];

if(!$page = AppPage::getPage(Me::$id, $_GET['id']))
{	
	header("Location: /" . Me::$vals['handle'] . "/pages"); exit;
}

// Page submitted
if(Form::submitted(SITE_HANDLE . '-personal-page'))
{
	if(isset($_POST['leftup']))
	{
		if(AppPage::moveFlat(Me::$id, (int) $page['page_id'], -1))
			$page['page_order']--;
	}
	elseif(isset($_POST['rightdown']))
	{
		if(AppPage::moveFlat(Me::$id, (int) $page['page_id'], 1))
			$page['page_order']++;
	}
	elseif(isset($_POST['parentchild']))
	{
		if(!isset($_POST['parentchildselect']))
			$_POST['parentchildselect'] = 0;
		if(AppPage::moveOther(Me::$id, (int) $page['page_id'], (int) $_POST['parentchildselect']))
			$page['parent_id'] = (int) $_POST['parentchildselect'];
	}
}

// Display list of pages
$list = AppPage::getList(Me::$id);
$insert = '';
if(Me::$id == $userData['uni_id'])
{
	$insert .= '<li class="menu-slot"><a href="/' . Me::$vals['handle'] . '/pages/create-page">Create Page</a></li>';
}
if(!isset($list[0])) $list[0] = array();
foreach($list[0] as $l)
{
	$insert .= '<li class="menu-slot"><a href="/' . $userData['handle'] . '/pages/' . $l['page_id'] . '-' . $l['url_slug'] . '">' . $l['title'] . (isset($list[(int) $l['page_id']]) ? ' <span class="icon-circle-right"></span>' : '') . '</a>';
	if(isset($list[(int) $l['page_id']]))
	{
		$insert .= '<ul>';
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
if($page['parent_id'] == 0)
{
	$count = AppPage::countParents(Me::$id);
}
else
{
	$count = AppPage::countChildren(Me::$id, (int) $page['parent_id']);
}

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<div class="overwrap-box">
		<div class="overwrap-line">
			<div class="overwrap-name">Move Page: ' . $page['title'] . '</div>
		</div>
		<div style="padding:6px;">
			<form class="uniform" method="post" style="padding-right:20px;">' . Form::prepare(SITE_HANDLE . '-personal-page') . '
				<div style="margin-top:10px;">
					<input type="submit" name="leftup" value="Move ' . ($page['parent_id'] ? 'Up' : 'Left') . '"' . ($page['page_order'] > 1 ? '' : ' disabled') . ' />
					<input type="submit" name="rightdown" value="Move ' . ($page['parent_id'] ? 'Down' : 'Right') . '"' . ($page['page_order'] < $count ? '' : ' disabled') . ' />';
if(!$page['parent_id'])
{
	echo '
					<input type="submit" name="parentchild" value="Move to Parent Page:" />
					<select name="parentchildselect">';
	foreach($list[0] as $l)
		echo '
						<option value="' . $l['page_id'] . '">' . $l['title'] . '</option>';
	echo '
					<select>';
}
else
{
	echo '
					<input type="submit" name="parentchild" value="Move to Top" />';
}
echo '
				</div>
			</form>
		</div>
	</div>
</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
