<? foreach ($entries as $sem_tree_id => $entry) : ?>
    <li class="sem_tree_entry">
        <? $checkbox_id = md5(uniqid()) ?>
        <div class="name">

            <div style="float: right;">
                <? if ($entry['studip_object_id']) : ?>
                    <a href="<?= URLHelper::getLink("dispatch.php/institute/overview", array('auswahl' => $entry['studip_object_id'])) ?>">
                        <?= Assets::img("icons/16/blue/institute", array('class' => "text-bottom")) ?>
                    </a>
                <? endif ?>
                <input type="<?= $type === "single" ? "radio" : "checkbox" ?>"
                       name="<?= htmlReady($name) ?><?= $type === "single" ? "" : "[".$sem_tree_id."]" ?>"
                       value="<?= htmlReady($type === "single" ? $sem_tree_id : 1) ?>"
                       style="vertical-align: text-bottom;"
                       <?= in_array($sem_tree_id, (array) $defaults) ? " checked" : "" ?>>
            </div>

            <label for="<?= $checkbox_id ?>">
                <?= htmlReady($entry['studip_object_id'] ? Institute::find($entry['studip_object_id'])->name : $entry['name']) ?>
            </label>

        </div>
        <input type="checkbox"
               class="opener"
               id="<?= $checkbox_id ?>"
               data-sem_tree_id="<?= htmlReady($sem_tree_id) ?>"
               value="1"
               <?= isset($necessary[$sem_tree_id]) && count($necessary[$sem_tree_id]['children']) ? " checked" : ""?>>
        <ol><? if (isset($necessary[$sem_tree_id]) && count($necessary[$sem_tree_id]['children'])) : ?>
            <? $subentries = array();
               foreach ($necessary[$sem_tree_id]['children'] as $entry_id) {
                    $subentries[$entry_id] = $necessary[$entry_id];
               } ?>
            <?= $this->render_partial("study_area_selector/_children", array(
                    "id" => $id,
                    "entry" => $entry,
                    "name" => $name,
                    "entries" => $subentries,
                    "defaults" => $defaults,
                    "neccesary" => $necessary)) ?>
        <? endif ?></ol>

    </li>
<? endforeach ?>