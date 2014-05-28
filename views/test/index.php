<h2><?= _("Ergebnisse der Unit-Tests") ?></h2>

<style>
div#errors > div {
  margin: 10px;
  border: 1px dotted #555555;
  padding: 5px;
  background-color: #ffffdd;
}

</style>

<pre id="result">
    <?= $testergebnis ?>
</pre>

<textarea style="display: none;" id="output" aria-label="<?= htmlReady(_("Zwischentestergebnis - wird noch ausgewertet.")) ?>"><?= $testergebnis ?></textarea>

<div id="errors"></div>

<script>
    jQuery(function () {
        var output = jQuery('#output').text();
        //var errors = output.match(/^[.\d\w]*[!\)]\s([.\n\w\s\[\]\(\)=><!:\\\/-]*)_test\.php/);
        output = output.split(/\n/);

        var result = "";

        result += output.pop();
        result += output.pop();
        output.pop();
        output.shift();

        var error = "";
        var start = true;
        jQuery.each(output, function (index, line) {
            if (line[0] !== "\t" && (line.search(/\d+\)/) === 0 || line.search(/Exception /) === 0)) {
                if (error !== "") {
                    jQuery('<div></div>').html(error).appendTo("#errors");
                    error = "";
                    start = true;
                }
            }
            if (start) {
                error += "<b>" + jQuery("<div/>").text(line).html() + "</b>";
            } else {
                error += '<p style="margin: 4px;">' + jQuery("<div/>").text(line).html() + "</p>";
            }
            
            start = false;
        });
        if (error !== "") {
            jQuery('<div></div>').html(error).appendTo("#errors");
        } else {
            if (jQuery('#output').text().search("OK") !== -1) {
                jQuery('<div>' + "Alles bestens!" + "</div>")
                        .appendTo("#errors");
            }
        }
        jQuery("#result").text(result);
    });
</script>