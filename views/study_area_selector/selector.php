<style>
    .study_area_selector ol {
        display: none;
        list-style-type: none;
        padding-left: 0px;
    }
    .study_area_selector .opener {
        display: none;
    }
    .study_area_selector .opener:checked + ol {
        display: block;
        -webkit-animation: fadeIn 1s;
        animation: fadeIn 1s;
    }
    .study_area_selector ol > li {
        margin-left: 16px;
        clear: both;
    }

    @-webkit-keyframes fadeIn {
        from { opacity: 0; display: block; }
        to { opacity: 1; display: block; }
    }
    @keyframes fadeIn {
        from { opacity: 0; display: block; }
        to { opacity: 1; display: block; }
    }

</style>
<script>
jQuery(".study_area_selector").on("change", "input[type=checkbox].opener", function () {
    if (this.checked && jQuery(this).siblings("ol").children().length === 0) {
        var sem_tree_id = jQuery(this).data("sem_tree_id");
        var type = jQuery(this).closest(".study_area_selector").data("type");
        var name = jQuery(this).closest(".study_area_selector").data("name");
        var id = jQuery(this).closest(".study_area_selector").data("id");
        var ol = jQuery(this).siblings("ol")[0];
        jQuery.ajax({
            "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/campusconnect/study_area_selector/stage/" + sem_tree_id,
            "data": {
                "type": type,
                "id": id,
                "name": name
            },
            "dataType": "json",
            "success": function (json) {
                jQuery(ol).html(json.html);
            }
        });
    }
});
</script>

<div class="study_area_selector"
     style="<?= implode("; ", array_map(function ($i, $v) { return $i.": ".$v; }, array_keys($style), array_values($style))) ?>"
     data-id="<?= htmlReady($id) ?>"
     data-type="<?= htmlReady($type) ?>"
     data-name="<?= htmlReady($name) ?>">
    <input type="checkbox" checked class="opener">

    <ol>
        <?= $this->render_partial("study_area_selector/_children", [
            'id' => $id,
            'entry' => $entry ?? null,
            'name' => $name,
            'entries' => $entries,
            'defaults' => $defaults,
            'neccesary' => $neccesary ?? []
        ]) ?>
    </ol>
</div>
