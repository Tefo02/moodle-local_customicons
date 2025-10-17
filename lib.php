<?php
// Contém os hooks para exibir o formulário, salvar os dados e injetar o CSS.
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

    $radioarray = [];
    $radioarray[] = $mform->createElement('radio', 'custom_icon', '', get_string('nodefaulticon', 'local_customicons'), 'none');
    foreach ($icon_files as $file) {
        if (preg_match('/\.(svg|png|jpg|jpeg)$/i', $file)) {
            $icon_url = (new moodle_url('/local/customicons/pix/activity_icons/' . $file))->out(false);
            $label = html_writer::tag('img', '', ['src' => $icon_url, 'class' => 'custom-icon-preview', 'width' => '24', 'height' => '24']) . ' ' . $file;
            $radioarray[] = $mform->createElement('radio', 'custom_icon', '', $label, $file);
        }
    }
    $mform->addElement('group', 'custom_icon_group', get_string('customfieldlabel', 'local_customicons'), $radioarray, null, false);

    if (!empty($cm->id) && ($current = $DB->get_field('local_customicons_data', 'icon_name', ['cmid' => $cm->id]))) {
        $mform->setDefault('custom_icon', $current);
    } else {
        $mform->setDefault('custom_icon', 'none');
    }
}

/**
 * Hook chamado após a criação ou atualização de um módulo de curso.
 */
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

/**
 * Hook chamado em cada página para estender a navegação.
 * Usaremos este ponto de entrada para injetar nosso CSS na página do curso.
 */
function local_customicons_extend_navigation(global_navigation $nav) {
    global $PAGE, $DB, $COURSE, $CFG; // Adicionamos $CFG ao escopo global

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
    }

    if (!empty($cssrules)) {
        $css = implode("\n", $cssrules);
        $styleblock = "<style>\n" . $css . "\n</style>";

        $CFG->additionalhtmlhead .= $styleblock;
    }
}