<?php
// Contém todos os hooks necessários para o plugin funcionar.
defined('MOODLE_INTERNAL') || die();

/**
 * Hook para adicionar elementos ao formulário padrão de qualquer atividade.
 */
function local_customicons_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB, $CFG;

    $cm = $formwrapper->get_coursemodule();
    $icons_dir = $CFG->dirroot . '/local/customicons/pix/activity_icons/';
    if (!is_dir($icons_dir) || !($icon_files = array_diff(scandir($icons_dir), ['.', '..']))) {
        return;
    }
    
    $mform->addElement('header', 'local_customicons_header', get_string('customfieldset', 'local_customicons'));

    $current_value = 'none';
    if (!empty($cm->id) && ($current = $DB->get_field('local_customicons_data', 'icon_name', ['cmid' => $cm->id]))) {
        $current_value = $current;
    }

    $html = '';
    
    $html .= '<style>
        .icon-select-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        .icon-select-item {
            position: relative;
        }
        .icon-select-item label {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s;
            text-align: center;
            height: 100%;
        }
        .icon-select-item .icon-filename-label {
            font-size: 0.85em;
            margin-top: 5px;
            word-break: break-all;
            color: #555;
        }
        .icon-select-item input[type="radio"] {
            display: none;
        }
        .icon-select-item input[type="radio"]:checked + label {
            border-color: #007bff;
            background-color: #e7f1ff;
        }
    </style>';

    $html .= '<div class="icon-select-grid">';
    
    $id = 'custom_icon_none';
    $checked = ($current_value === 'none') ? 'checked' : '';
    $html .= '<div class="icon-select-item">';
    $html .= '<input type="radio" name="custom_icon" value="none" id="' . $id . '" ' . $checked . '>';
    $html .= '<label for="' . $id . '" title="' . get_string('nodefaulticon', 'local_customicons') . '">';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="padding: 5px;"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
    $html .= $svg;
    $html .= html_writer::tag('span', get_string('nodefaulticon', 'local_customicons'), ['class' => 'icon-filename-label']);
    $html .= '</label>';
    $html .= '</div>';
    
    foreach ($icon_files as $file) {
        if (preg_match('/\.(svg|png|jpg|jpeg)$/i', $file)) {
            $filename_only = pathinfo($file, PATHINFO_FILENAME);
            $clean_name = ucwords(str_replace(['-', '_'], ' ', $filename_only));

            $icon_url = (new moodle_url('/local/customicons/pix/activity_icons/' . $file))->out(false);
            $id = 'custom_icon_' . s(pathinfo($file, PATHINFO_FILENAME));
            $checked = ($current_value === $file) ? 'checked' : '';
            
            $html .= '<div class="icon-select-item">';
            $html .= '<input type="radio" name="custom_icon" value="' . s($file) . '" id="' . $id . '" ' . $checked . '>';
            $html .= '<label for="' . $id . '" title="' . s($clean_name) . '">';
            $html .= html_writer::tag('img', '', ['src' => $icon_url, 'class' => 'custom-icon-preview', 'width' => '32', 'height' => '32', 'alt' => $clean_name, 'style' => 'padding: 5px;']);
            $html .= html_writer::tag('span', s($clean_name), ['class' => 'icon-filename-label']);
            $html .= '</label>';
            $html .= '</div>';
        }
    }
    $html .= '</div>';

    $mform->addElement('html', $html);
}

function local_customicons_coursemodule_updated($cm, $mform) {
    global $DB;
    $icon_name = optional_param('custom_icon', 'none', PARAM_FILE);
    $existing = $DB->get_record('local_customicons_data', ['cmid' => $cm->id]);
    if ($icon_name === 'none') {
        if ($existing) {
            $DB->delete_records('local_customicons_data', ['id' => $existing->id]);
        }
        return;
    }
    if ($existing) {
        $existing->icon_name = $icon_name;
        $existing->timemodified = time();
        $DB->update_record('local_customicons_data', $existing);
    } else {
        $record = new stdClass();
        $record->cmid = $cm->id;
        $record->icon_name = $icon_name;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $DB->insert_record('local_customicons_data', $record);
    }
}
function local_customicons_extend_navigation(global_navigation $nav) {
    global $PAGE, $DB, $COURSE, $CFG;
    if ($PAGE->pagelayout !== 'course' || empty($COURSE->id)) {
        return;
    }
    $modinfo = get_fast_modinfo($COURSE);
    if (empty($modinfo->cms)) {
        return;
    }
    $allcmids = array_keys($modinfo->cms);
    list($sql, $params) = $DB->get_in_or_equal($allcmids);
    $sql = "SELECT cmid, icon_name FROM {local_customicons_data} WHERE cmid " . $sql;
    $customicons = $DB->get_records_sql($sql, $params);
    if (empty($customicons)) {
        return;
    }
    $cssrules = [];
    foreach ($customicons as $customicon) {
        $iconurl = (new moodle_url('/local/customicons/pix/activity_icons/' . $customicon->icon_name))->out(false);
        $safecssurl = addslashes($iconurl);
        $cssrules[] = "#module-{$customicon->cmid} .activityicon { content: url('{$safecssurl}'); }";
        $cssrules[] = "#module-{$customicon->cmid} .tile-icon img { content: url('{$safecssurl}'); }";
    }
    if (!empty($cssrules)) {
        $css = implode("\n", $cssrules);
        $styleblock = "<style>\n" . $css . "\n</style>";
        $CFG->additionalhtmlhead .= $styleblock;
    }
}