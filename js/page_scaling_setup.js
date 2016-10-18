$(document).ready(function() {
    var width=$( window ).width()-10;
    var height=$( window ).height()-10;
    var textSize=Math.floor(width/34.65);
    if(textSize>27) {
        textSize=27;
    }
    
    //remove me to make text big again
    textSize=12;
    
    $("body").css("width", width+"px");
    $("body").css("height", height+"px");
    $("body").css("font-size", textSize+"px");
    $("div#tabs").css("height", height-42+"px");
});