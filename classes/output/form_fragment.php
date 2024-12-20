<?php
namespace local_courserecommend\output;

defined('MOODLE_INTERNAL') || die();

class form_fragment {
    /**
     * Returns the recommend form fragment.
     *
     * @param array $args Arguments from web service.
     * @return string HTML fragment.
     */
    public static function get_form_content($args) {
        global $CFG;
        
        require_once($CFG->dirroot . '/local/courserecommend/classes/form/recommend_form.php');
        
        $courseid = $args['courseid'];
        $form = new \recommend_form(null, array('courseid' => $courseid));
        
        return $form->render();
    }
}
