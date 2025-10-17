<?php
namespace local_customicons;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function module_saved(\core\event\base $event) {
        global $DB;
        
        $cmid = $event->objectid;
        $icon_name = optional_param('custom_icon', 'none', PARAM_FILE);

        $existing = $DB->get_record('local_customicons_data', ['cmid' => $cmid]);

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
            $record = new \stdClass();
            $record->cmid = $cmid;
            $record->icon_name = $icon_name;
            $record->timecreated = time();
            $record->timemodified = $record->timecreated;
            $DB->insert_record('local_customicons_data', $record);
        }
    }
}