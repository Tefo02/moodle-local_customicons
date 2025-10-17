<?php
defined('MOODLE_INTERNAL') || die();

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