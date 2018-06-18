var colorpickers = document.getElementsByClassName('colorpicker');
initListeners(colorpickers);

function changeColor() {
    var color = this.id;
    var oldcolor = color == "white" ? "black" : "white";
    document.body.id = color;
    var curloc = window.location.href;
    console.log(curloc.indexOf("bground"));
    if(curloc.indexOf("bground") == -1) {
        curloc = curloc + "&bground=" + color;
    } else {
        curloc = curloc.replace(oldcolor, color);
    }
    window.location.replace(curloc);
}

function initListeners(cps) {
    for(var i = 0; i < cps.length; i++) {
        cps[i].addEventListener('click', changeColor);
    }
}