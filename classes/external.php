<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_courserecommend_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function recommend_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'users' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID')
                )
            )
        );
    }

    /**
     * Recommend course
     * @param int $courseid
     * @param array $users
     * @return array
     */
    public static function recommend_course($courseid, $users) {
        global $DB, $USER;

        // Parameters validation
        $params = self::validate_parameters(self::recommend_course_parameters(),
            array('courseid' => $courseid, 'users' => $users));

        // Context validation
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Capability checking
        require_capability('local/courserecommend:recommend', $context);

        // Get course
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);

        $success = true;
        $errors = array();

        foreach ($params['users'] as $userid) {
            $recommendationdata = new stdClass();
            $recommendationdata->courseid = $course->id;
            $recommendationdata->userid = $USER->id;
            $recommendationdata->recommendedto = $userid;
            $recommendationdata->timemodified = time();

            try {
                if (!$DB->insert_record('local_courserecommend', $recommendationdata)) {
                    $success = false;
                    $errors[] = "Failed to insert recommendation for user $userid";
                }
            } catch (Exception $e) {
                $success = false;
                $errors[] = $e->getMessage();
            }
        }

        return array(
            'success' => $success,
            'errors' => $errors
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function recommend_course_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
                'errors' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Error message')
                )
            )
        );
    }
}
