<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

if(isset($userData))
{
	if(Me::$id != $userData['uni_id'])
	{
		header("Location: /" . Me::$vals['handle'] . "/pages/create-page"); exit;
	}
}

// Prepare Values
if(!isset($_POST['body'])) $_POST['body'] = "";
if(!isset($_POST['title'])) $_POST['title'] = "";

// Page submitted
if(Form::submitted(SITE_HANDLE . '-personal-page'))
{
	$page = AppPage::createPage(Me::$id, $_POST['title'], $_POST['body']);
	if($page)
	{
		if($_POST['parentchildselect'])
		{
			AppPage::moveOther(Me::$id, (int) $page[0], (int) $_POST['parentchildselect']);
		}
		header("Location: /" . Me::$vals['handle'] . "/pages/" . $page[0] . '-' . $page[1]); exit;
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
echo '
<style>
	#preview img { max-width:100%; }
</style>
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<div class="overwrap-box">
		<div class="overwrap-line">
			<div class="overwrap-name">Create Page</div>
		</div>
		<div style="padding:6px;">
			<form class="uniform" method="post" style="padding-right:20px;">' . Form::prepare(SITE_HANDLE . '-personal-page');
			
echo '
				<select name="parentchildselect" style="width:100%;margin-bottom:10px;">
					<option value="0">no parent page</option>';
foreach($list[0] as $l)
	echo '
					<option value="' . $l['page_id'] . '">' . $l['title'] . '</option>';
echo '
				<select>';

echo '
				<input type="text" name="title" value="' . $_POST['title'] . '" tabindex="10" placeholder="Title . . ." style="width:100%;margin-bottom:10px;" autocomplete="off" maxlength="48" autofocus />
				' . UniMarkup::buttonLine() . '
				<textarea id="core_text_box" name="body" tabindex="20" placeholder="Enter your page content here . . ." style="resize:vertical;width:100%;height:300px;" tabindex="20">' . $_POST['body'] . '</textarea>
				<div style="margin-top:10px;"><input type="button" value="Preview" onclick="previewPost();"/> <input type="submit" name="submit" value="Create Page" /></div>
				<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
			</form>
		</div>
	</div>
</div>
<script>
function previewPost()
{
	var text = encodeURIComponent(document.getElementById("core_text_box").value);
	getAjax("", "preview-post", "parse", "body=" + text);
}
function parse(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("preview").style.display = "block";
	document.getElementById("preview").innerHTML = response;
}
</script>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
