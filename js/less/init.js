(function() {

    var cssEls = document.getElementsByTagName('link');
    var init = false;
    for (var i in cssEls)
    {
        if (cssEls[i].href && cssEls[i].href.substr(-5) == '.less')
        {
            init = true;
            cssEls[i].rel = 'stylesheet/less';
        }
    }

    if (init)
    {
        var scriptEls = document.getElementsByTagName('script');
        var thisScriptEl = scriptEls[scriptEls.length - 1];
        var scriptPath = thisScriptEl.src;
        var scriptFolder = scriptPath.substr(0, scriptPath.lastIndexOf( '/' )+1 );

        window.less || document.write('<script src="'+scriptFolder+'less.js"><\/script>');
    }
})();
