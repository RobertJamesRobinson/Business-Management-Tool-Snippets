//nav menu open and closing functions and state
var menu_focus=false;
var current_menu=null;

//open the currently set current menu
function openTopMenu() {
    $("#"+current_menu+">ul").show();
    //alert (menuId);
}
//close the currently set current menu
function closeTopMenu() {
    //alert ("close".menuId);
    $("#"+current_menu+">ul").hide();
}
//main jquery document ready clause
$(document).ready(function() {
    //check if user clicked anywhere other than the menu and close the menu if they did
    $(document).on('click',function(e) {
        if (e.target.id.substr(0,4)!=="menu") {
            closeTopMenu()
            current_menu=null;
            menu_focus=false;
        }
        
    });
    //look for clicks specifically on the menu
    $("body").delegate("li.nav_menu_top","click",function(e) {
        var clicked_item=e.target.id;
        if (menu_focus) {
            //clicked on the same menu item, closing the sub menu
            if (clicked_item===current_menu) {
                closeTopMenu()
                current_menu=null;
                menu_focus=false;
            }
            //clicked on another menu item, close the first sub menu, open the second
            else {
                closeTopMenu()
                current_menu=clicked_item;
                openTopMenu();
            }
        }
        //no menu focus, so we must be opening one
        else {
            current_menu=clicked_item;
            menu_focus=true;
            openTopMenu();
        }
    });
    $("body").delegate("li.nav_menu_bot","click",function(e) {
        var tab_label=e.target.getAttribute('name');
        var tab_name=e.target.id;

        //check if there is already a window open with the associated tag name and switch to it, or make a new tab and switch to it
        var tabs=$("div#tabs ul").children();
        var exists=false;
        var exists_index=0;
        for (var i=0; i<tabs.length; i++) {
            var href=$("div#tabs ul").children()[i].getAttribute("aria-controls");
            if (href==="content_" + tab_name) {
                exists=true;
                exists_index=i;
            }
        }
        if(exists) {
            $("div#tabs").tabs( "option", "active", exists_index );
        }
        else{
            $("div#tabs ul").append("<li><a href='#content_"+tab_name+"'>"+tab_label+"</a><span class='ui-icon ui-icon-circle-close ui-closeable-tab'></li>");
            $("div#tabs").append("<div id='content_" + tab_name + "'></div>");
            $("div#tabs").tabs("refresh");
            $( "div#tabs" ).tabs( "option", "active", -1 );
            $.ajax(
            {
                type: "POST",
                url: "page_responder.php",
                data:
                {
                    page:tab_name,
                }
            })
            .done(function(e2)
            {
                $('#content_'+tab_name).html(e2);
            });
        }
        
        
    });
});


//   	<ul>
//   		<li><a href="#tabs-1">Expenses</a><span class="ui-icon ui-icon-circle-close ui-closeable-tab"></span> </li>
//   		<li><a href="#tabs-2">Incomes</a><span class="ui-icon ui-icon-circle-close ui-closeable-tab"></span></li>
//   		<li><a href="#tabs-3">Report</a><span class="ui-icon ui-icon-circle-close ui-closeable-tab"></span></li>
//   	</ul>
//   	<div id="tabs-1">wtf</div>
//   	<div id="tabs-2">somethingelse</div>
//   	<div id="tabs-3">another tab</div>