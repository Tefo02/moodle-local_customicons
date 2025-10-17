<?php
defined('MOODLE_INTERNAL') || die();
$observers = [
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_customicons\observer::module_saved',
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_customicons\observer::module_saved',
    ],
];