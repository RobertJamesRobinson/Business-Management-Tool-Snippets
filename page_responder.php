<?php
include_once("connect.php");
include_once("features.php");
if (array_key_exists('page', $_REQUEST)) {
    $db=new Connect();
    $page=$db->clean($_REQUEST['page']);
    $features=new Features($db);
    print $features->load_page($page);
}
else {
    print "<p>No Page By That Name...</p>";
}

?>