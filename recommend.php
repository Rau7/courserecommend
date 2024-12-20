<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/local/courserecommend/lib.php');
require_once($CFG->dirroot.'/local/courserecommend/classes/form/recommend_form.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Check if course is excluded
if (local_courserecommend_is_course_excluded($courseid)) {
    throw new moodle_exception('courseexcluded', 'local_courserecommend');
}

require_login($course);

// Check if user is enrolled
if (!is_enrolled($context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'recommend course');
}

$PAGE->set_url(new moodle_url('/local/courserecommend/recommend.php', array('courseid' => $courseid)));
$PAGE->set_context($context);
$PAGE->set_title(get_string('recommend_course', 'local_courserecommend'));
$PAGE->set_heading($course->fullname);

// Get recommendations made by current user
$sql = "SELECT cr.*, c.fullname as coursename, u.firstname, u.lastname
        FROM {local_courserecommend} cr
        JOIN {course} c ON c.id = cr.courseid
        JOIN {user} u ON u.id = cr.userid
        WHERE cr.userid = :userid
        ORDER BY cr.timemodified DESC
        LIMIT 10";

$params = array('userid' => $USER->id);
$recommendations = $DB->get_records_sql($sql, $params);

$form = new recommend_form(null, array('courseid' => $course->id));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
} else if ($fromform = $form->get_data()) {
    // Process form data
    $recommendationdata = new stdClass();
    $recommendationdata->courseid = $course->id;
    $recommendationdata->userid = $USER->id;
    $recommendationdata->timemodified = time();
    
    foreach ($fromform->users as $userid) {
        $recommendationdata->recommendedto = $userid;
        $DB->insert_record('local_courserecommend', $recommendationdata);
    }
    
    redirect(
        new moodle_url('/course/view.php', array('id' => $courseid)),
        get_string('recommendation_sent', 'local_courserecommend'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

// Display recommendations if any exist
if ($recommendations) {
    echo '<h3>'.get_string('your_recommendations', 'local_courserecommend').'</h3>';
    echo '<ul>';
    foreach ($recommendations as $rec) {
        echo '<li>' . $rec->coursename . ' - ' . $rec->firstname . ' ' . $rec->lastname . '</li>';
    }
    echo '</ul>';
}

$form->display();
echo $OUTPUT->footer();
