<?php
require_once('../../config.php');
global $CFG, $DB, $USER;

// Use the correct path for your actual plugin
// For example, if your plugin is in /local/myplugin/
require_once($CFG->dirroot . '/local/myplugin/classes/form/myform.php');

$mform = new \local_myplugin\form\myform();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot);
    
} else if ($fromform = $mform->get_data()) {
    // Process form data
    redirect($CFG->wwwroot, 'Form submitted successfully!');
    
} else {
    $mform->display();
}
?>