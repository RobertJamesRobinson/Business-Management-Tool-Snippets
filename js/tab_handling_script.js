$(document).ready(function() {
    $("#tabs").tabs({heightStyle:"fill"});
    
    $(document).on("click",".ui-icon-circle-close",function(e){
        var href=e.target.parentElement.childNodes[0].getAttribute("href").substr(1);
        var current_tab=$( "div#tabs" ).tabs( "option", "active");
        $("div#"+href).remove();
        var tabnames=$("div#tabs ul").children();
        var tab_to_delete=null;
        for (var i=0; i<tabnames.length; i++) {
            if (tabnames[i].getAttribute("aria-controls")===href) {
                tab_to_delete=i;
            }
        }
        tabnames[tab_to_delete].remove();
        $("div#tabs").tabs("refresh");
        //select a new tab if we just closed the current tab
        if (tabnames.length>1 && current_tab===tab_to_delete) {
            $( "div#tabs" ).tabs( "option", "active", tab_to_delete );
        }
    });
    
    
});
function close_current_tab() {
    var currentTabIndex=$("div#tabs").tabs("option","active");
    var href=$("div#tabs ul").children()[currentTabIndex].getAttribute("aria-controls");
    $("div#"+href).remove();
    var tabs=$("div#tabs ul").children();
    tabs[currentTabIndex].remove();
    $("div#tabs").tabs("refresh");
    $("div#tabs").tabs( "option", "active", currentTabIndex );
}