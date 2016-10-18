<?php
date_default_timezone_set('Australia/Victoria');
include_once("connect.php");
include_once("features.php");
include_once("utilities.php");

//setup objects we need
$db=new Connect();
$utils=new Utilities($db);
$features=new Features($db);
//setup the page properly, with css binding as well as javascript bindings
$output='
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Home Care 2 You</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link type="text/css" href="css/jquery-ui.min.css" rel="stylesheet" />
        <link type="text/css" href="css/jquery-ui.structure.min.css" rel="stylesheet" />
        <link type="text/css" href="css/jquery-ui.theme.min.css" rel="stylesheet" />
        <link type="text/css" href="css/datatables.css" rel="stylesheet" />
        <link type="text/css" href="css/custom.css" rel="stylesheet" />
        <link type="text/css" href="css/dynamic_menu_styles.css" rel="stylesheet" />
        
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/jquery-migrate-3.0.0.js"></script>
        <script type="text/javascript" src="js/datatables.js"></script>
        <script type="text/javascript" src="js/page_scaling_setup.js"></script>
        <script type="text/javascript" src="js/dynamic_menu_script.js"></script>
        <script type="text/javascript" src="js/tab_handling_script.js"></script>
    </head>
    <body>';

//put in the nav menu
$output.='<div id="nav_menu_div">';
$menuArray=$features->get_menus();
$output.=$utils->render_menu($menuArray);
$output.='</div>';

//put in the tabs 
$output.='
    <div id="tabs">
        <ul>
            <li><a href="#about">Home Care 2 You</a></li>
        </ul>
        <div id="about"><h2>Event Log</h2>'.$db->get_last_events(10).'</div>
    </div>';
    
//background company logo
$output.='<div id="CompanyLogoImageDiv"><img src="css/images/CompanyLogo.png" /></div>';

//$output.='<div id="dialog" title="Dialog Title"></div>';
    
//finalise page
$output.='
    </body>
    </html>';
    
print $output;
?>