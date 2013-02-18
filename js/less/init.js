document.observe("dom:loaded", function() {

    // Destroys the localStorage copy of CSS that less.js creates
    localStorage.clear();

    var cssEls = document.getElementsByTagName('link');
    var init = false;

    for (var i in cssEls) {
        if (cssEls[i].href && cssEls[i].href.substr(-5) == '.less') {
            init = true;
            less.dumpLineNumbers = "all";
            less.sheets.push(cssEls[i]);
        }
    }

    if (init)
    {
        less.watch();
        less.refresh();
    }
});
