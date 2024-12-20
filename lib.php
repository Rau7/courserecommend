<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Check if course is excluded
 */
function local_courserecommend_is_course_excluded($courseid) {
    $excluded_courses = get_config('local_courserecommend', 'excluded_courses');
    if (empty($excluded_courses)) {
        return false;
    }
    $excluded_array = explode(',', $excluded_courses);
    return in_array($courseid, $excluded_array);
}

/**
 * Add recommend button to course settings
 */
function local_courserecommend_extend_settings_navigation($settingsnav, $context) {
    global $PAGE, $COURSE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('local/courserecommend:recommend', context_course::instance($PAGE->course->id))) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $strfoo = get_string('recommendcourse', 'local_courserecommend');
        $url = new moodle_url('#');
        $foonode = navigation_node::create(
            $strfoo,
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'recommend-course-btn',
            new pix_icon('i/users', '')
        );
        $settingnode->add_node($foonode);
    }
}

/**
 * Add recommend button to course content area
 */
function local_courserecommend_before_footer() {
    global $PAGE, $COURSE;

    // Only add button on course pages
    if ($PAGE->context->contextlevel == CONTEXT_COURSE && $PAGE->course->id != SITEID) {
        // Don't show if course is excluded
        if (local_courserecommend_is_course_excluded($COURSE->id)) {
            return;
        }

        if (has_capability('local/courserecommend:recommend', $PAGE->context)) {
            $PAGE->requires->js(new moodle_url('/local/courserecommend/js/popup.js'));
            
            // Add button to course content area with course ID
            $button = html_writer::link(
                '#',
                html_writer::tag('i', '', array('class' => 'fa fa-users')) . ' ' . 
                get_string('recommendcourse', 'local_courserecommend'),
                array(
                    'class' => 'recommend-course-btn', 
                    'id' => 'recommend-course-btn',
                    'data-courseid' => $COURSE->id
                )
            );
            
            // Inline CSS
            $css = "
                <style>
                    .recommend-button-container {
                        margin: 10px 0;
                        text-align: right;
                        padding: 10px;
                        background-color: #f8f9fa;
                        border-radius: 4px;
                    }
                    
                    .recommend-course-btn {
                        display: inline-block;
                        padding: 8px 16px;
                        margin-bottom: 0;
                        font-size: 14px;
                        font-weight: 500;
                        line-height: 1.42857143;
                        text-align: center;
                        white-space: nowrap;
                        vertical-align: middle;
                        cursor: pointer;
                        background-image: none;
                        border: 1px solid transparent;
                        border-radius: 4px;
                        color: #fff;
                        background-color: #0f6fc5;
                        border-color: #0e63b0;
                        text-decoration: none;
                        transition: all 0.3s ease;
                    }
                    
                    .recommend-course-btn:hover {
                        color: #fff;
                        background-color: #0d5ca3;
                        border-color: #0b4d8a;
                        text-decoration: none;
                        transform: translateY(-1px);
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    .recommend-course-btn i {
                        margin-right: 5px;
                    }
                </style>
            ";
            
            echo $css . html_writer::div($button, 'recommend-button-container');
        }
    }
}

function local_courserecommend_get_form($courseid) {
    global $CFG;
    require_once($CFG->dirroot . '/local/courserecommend/classes/form/recommend_form.php');
    $form = new recommend_form(null, array('courseid' => $courseid));
    return $form->render();
}

function local_courserecommend_output_fragment_get_form_content($args) {
    return local_courserecommend_get_form($args['courseid']);
}
