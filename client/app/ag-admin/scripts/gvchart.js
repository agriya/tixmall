/*
 * jQuery gvChart plugin
 * This plugin was created to simplify things when using Google Visualisation Charts.
 * All examples you will find on http://www.ivellios.toron.pl/technikalia/demos/gvChart/
 * @name jquery.gvChart.min.js
 * @author Janusz Kamieński - http://www.ivellios.toron.pl/technikalia
 * @category jQuery plugin google charts
 * @copyright (c) 2010 Janusz Kamieński (www.ivellios.toron.pl)
 * @license CC Attribution Works 3.0 Poland - http://creativecommons.org/licenses/by/3.0/pl/deed.en_US
 * @example Visit http://www.ivellios.toron.pl/technikalia/demos/gvChart/ for more informations about this jQuery plugin
 * @June 2012 Added swapping of tables columns and rows by Glenn Wilton
 * @March 2013 Added asynchronous loading with callback by Jason Gill
 * Use googleLoaded.done(function(){ //charts here }); for deferred usage.
 */
var googleLoaded;
(function(a) {
    googleLoaded = $.Deferred();
    a.getScript("http://www.google.com/jsapi", function() {
        var b = 0;
        google.load("visualization", "1", {
            packages: ["corechart"],
            callback: function() {
                window.googleLoaded.resolve();
            }
        });
        a.fn.gvChart = function(e) {
            var j = a(this),
                f = {
                    hideTable: true,
                    chartType: "AreaChart",
                    chartDivID: "gvChartDiv",
                    gvSettings: null,
                    swap: false
                },
                c = a("<div>"),
                g = f.chartDivID + b++,
                h, d, k;
            c.attr("id", g);
            c.addClass("gvChart");
            c.attr("aria-hidden", "true");
            j.before(c);
            a.extend(f, e);
            if (f.hideTable) {
                j.hide();
                j.attr("aria-hidden", "false");
            }
            h = new google.visualization.DataTable();
            h.addColumn("string", "X labels");
            var d = j.find("thead th");
            var k = j.find("tbody tr");
            if (f.swap) {
                d.each(function(l) {
                    if (l) {
                        h.addColumn("number", a(this)
                            .text());
                    }
                });
                h.addRows(k.length);
                k.each(function(l) {
                    h.setCell(l, 0, a(this)
                        .find("th")
                        .text());
                });
                k.each(function(l) {
                    a(this)
                        .find("td")
                        .each(function(m) {
                            h.setCell(l, m + 1, parseFloat(a(this)
                                .text(), 10));
                        });
                });
            } else {
                k.each(function(l) {
                    h.addColumn("number", a(this)
                        .find("th")
                        .text());
                });
                h.addRows(d.length - 1);
                d.each(function(l) {
                    if (l) {
                        h.setCell(l - 1, 0, a(this)
                            .text());
                    }
                });
                k.each(function(l) {
                    a(this)
                        .find("td")
                        .each(function(m) {
                            h.setCell(m, l + 1, parseFloat(a(this)
                                .text(), 10));
                        });
                });
            }
            f.gvSettings.title = a(this)
                .find("caption")
                .text();
            var i = new google.visualization[f.chartType](document.getElementById(g));
            i.draw(h, f.gvSettings);
        };
    });
})(jQuery);