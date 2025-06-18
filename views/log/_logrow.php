<tr>
    <td>
        <a href="<?= PluginEngine::getLink($this->plugin, array('type' => $logentry['log_type']), "log/view") ?>">
            <?= Icon::create("filter2")->asImg(20, ['class' => 'text-bottom']) ?>
        </a>
        <a href="<?= PluginEngine::getLink($this->plugin, array(), "log/details/".$logentry['log_id']) ?>" data-dialog>
            <?= htmlReady($logentry['log_type']) ?>
        </a>
    </td>
    <td>
        <a href="<?= PluginEngine::getLink($this->plugin, array('text' => $logentry['log_text']), "log/view") ?>">
            <?= htmlReady($logentry['log_text']) ?>
        </a>
    </td>
    <td>
        <a href="<?= PluginEngine::getLink($this->plugin, array('mkdate' => $logentry['mkdate']), "log/view") ?>">
            <?= date("d.m.Y G:i", $logentry['mkdate']) ?>
        </a>
    </td>
    <td>
        <a href="<?= PluginEngine::getLink($this->plugin, array(), "log/details/".$logentry['log_id']) ?>" data-dialog>
            <?= Icon::create("info-circle")->asImg(20) ?>
        </a>
    </td>
</tr>
