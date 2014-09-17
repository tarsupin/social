<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

Database::initRoot();




exit;

echo '
<div id="ajaxDivID">This is a test.</div>

<a href="javascript:void(0)" onclick="loadAjax(\'http://search.test\', \'response\', \'ajaxDivID\', \'search=green bay lions\')">Edit me</a>';


echo '
<script src="http://cdn.test/scripts/ajax.js" async></script>
<script src="http://cdn.test/scripts/unifaction.js" async></script>';

exit;
