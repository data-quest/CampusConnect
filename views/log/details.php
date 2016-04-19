<table class="default nohover">
    <tbody>
        <tr>
            <td><?= _("log_id") ?></td>
            <td><?= htmlReady($entry['log_id']) ?></td>
        </tr>
        <tr>
            <td><?= _("Typ") ?></td>
            <td><?= htmlReady($entry['log_type']) ?></td>
        </tr>
        <tr>
            <td><?= _("Text") ?></td>
            <td><?= htmlReady($entry['log_text']) ?></td>
        </tr>
        <tr>
            <td><?= _("Zeitpunkt") ?></td>
            <td><?= date("d.m.Y G:i", $entry['mkdate']) ?></td>
        </tr>
    </tbody>
</table>

<?

function isAssoc($arr) {
    return array_keys($arr) !== range(0, count($arr) - 1);
}


function display_array($arr) {
    $output = "";
    if (isAssoc($arr)) {
        $output .= "<table><tbody>";
        foreach ($arr as $key => $value) {
            $output .= "<tr data-key=\"".htmlReady($key)."\">";
            $output .= '<td class="key">'.htmlReady($key).'</td>';
            if (is_array($value)) {
                $output .= '<td class="structure">'.display_array($value).'</td>';
            } else {
                $output .= '<td class="value">'.htmlReady($value).'</td>';
            }
            $output .= "</tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<ol>";
        foreach ($arr as $key => $value) {
            $output .= "<li>";
            if (is_array($value)) {
                $output .= display_array($value);
            } else {
                $output .= htmlReady($value);
            }
            $output .= "</li>";
        }
        $output .= "</ol>";
    }
    return $output;
}

?>

<div class="json_object_list">
    <?= display_array((json_decode($entry['log_json'], true))) ?>
</div>