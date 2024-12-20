<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class recommend_form extends moodleform {
    public function definition() {
        global $DB, $PAGE;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Add courseid as hidden field
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Get course context
        $context = context_course::instance($courseid);
        
        // Get all users who are NOT enrolled in the course
        $enrolled_users = get_enrolled_users($context);
        $enrolled_ids = array();
        foreach ($enrolled_users as $user) {
            $enrolled_ids[] = $user->id;
        }
        
        // Get all users who can potentially be enrolled (have a user account)
        $all_users = $DB->get_records('user', array('deleted' => 0, 'suspended' => 0));
        
        $options = array();
        foreach ($all_users as $user) {
            // Skip enrolled users, guest user, and admin
            if (!in_array($user->id, $enrolled_ids) && $user->id != 1 && $user->id != 2) {
                $options[$user->id] = $user->firstname . ' ' . $user->lastname;
            }
        }

        $mform->addElement('header', 'recommendheader', get_string('recommendcourse', 'local_courserecommend'));
        
        // Add search textbox
        $mform->addElement('text', 'searchusers', get_string('searchusers', 'local_courserecommend'));
        $mform->setType('searchusers', PARAM_TEXT);
        
        // Add select element with size attribute for scrolling
        $select = $mform->addElement('select', 'users', get_string('selectusers', 'local_courserecommend'), $options);
        $select->setMultiple(true);
        $select->setSize(10); // Show 10 users at a time
        $mform->addRule('users', get_string('nousersselected', 'local_courserecommend'), 'required', null, 'client');

        // Add JavaScript and CSS
        $PAGE->requires->js_init_call("
            Y.on('domready', function() {
                // Add CSS
                var style = document.createElement('style');
                style.textContent = '#id_users { min-width: 300px; max-width: 100%; } ' +
                                  '#id_searchusers { margin-bottom: 10px; width: 300px; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }';
                document.head.appendChild(style);

                // Add search functionality
                Y.one('#id_searchusers').on('keyup', function(e) {
                    var value = e.target.get('value').toLowerCase();
                    Y.all('#id_users option').each(function(option) {
                        var text = option.get('text').toLowerCase();
                        option.setStyle('display', text.indexOf(value) > -1 ? '' : 'none');
                    });
                });
            });
        ");

        $this->add_action_buttons(true, get_string('recommendbutton', 'local_courserecommend'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if (empty($data['users'])) {
            $errors['users'] = get_string('nousersselected', 'local_courserecommend');
        }
        
        return $errors;
    }
}
