(function() {
//    less.watch();

    var cssEls = document.getElementsByTagName('link');
    var init = false;

    for (var i in cssEls)
    {
        if (cssEls[i].href && cssEls[i].href.substr(-5) == '.less')
        {
            init = true;
            less.sheets.push(cssEls[i]);
        }
    }

    if (init)
    {
//        less.env = "development";
        less.refresh();
    }
})();
