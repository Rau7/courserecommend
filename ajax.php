<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot.'/local/courserecommend/lib.php');

try {
    // Get parameters
    $action = optional_param('action', '', PARAM_ALPHA);

    if (empty($action)) {
        throw new moodle_exception('Action parameter is required');
    }

    $response = array('success' => false);

    switch ($action) {
        case 'saveexcluded':
            require_login();
            require_capability('local/courserecommend:manage', context_system::instance());
            require_sesskey();
            
            $excludedstr = required_param('excluded', PARAM_TEXT);
            
            // Handle empty string case
            if (empty($excludedstr)) {
                set_config('excluded_courses', '', 'local_courserecommend');
                $response['success'] = true;
                break;
            }
            
            // Convert string to array and validate
            $excludedarray = array_filter(explode(',', $excludedstr));
            
            // Validate that all IDs are valid course IDs
            $validIds = true;
            foreach ($excludedarray as $courseid) {
                if (!$DB->record_exists('course', array('id' => $courseid))) {
                    $validIds = false;
                    break;
                }
            }
            
            if ($validIds) {
                set_config('excluded_courses', $excludedstr, 'local_courserecommend');
                $response['success'] = true;
            } else {
                $response['message'] = 'Invalid course ID(s)';
            }
            break;

        case 'getusers':
        case 'recommend':
            $courseid = required_param('courseid', PARAM_INT);
            if (empty($courseid)) {
                throw new moodle_exception('Course ID parameter is required');
            }

            // Check course exists
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
            $context = context_course::instance($courseid);

            // Check if course is excluded
            if (local_courserecommend_is_course_excluded($courseid)) {
                throw new moodle_exception('courseexcluded', 'local_courserecommend');
            }

            // Check capability
            require_capability('local/courserecommend:recommend', $context);

            if ($action === 'getusers') {
                // Get all users who can be enrolled in courses
                $sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) as name 
                        FROM {user} u 
                        WHERE u.deleted = 0 AND u.suspended = 0 
                        AND u.id != :guestid 
                        ORDER BY u.firstname";
                
                $params = array('guestid' => $CFG->siteguest);
                $users = $DB->get_records_sql($sql, $params);
                
                $userlist = array();
                foreach ($users as $user) {
                    $userlist[] = array(
                        'id' => $user->id,
                        'name' => $user->name
                    );
                }
                
                $response['success'] = true;
                $response['data'] = $userlist;
            } else {
                $users = optional_param_array('users', array(), PARAM_INT);
                
                if (empty($users)) {
                    throw new moodle_exception('No users selected');
                }

                // Test database connection and table structure
                try {
                    // Get table columns
                    $columns = $DB->get_columns('local_courserecommend');
                    //error_log('Table columns: ' . print_r($columns, true));

                    // Check if required columns exist
                    $required_columns = ['id', 'courseid', 'userid', 'recommendedby', 'recommendedto', 'timemodified'];
                    $missing_columns = array();
                    foreach ($required_columns as $column) {
                        if (!isset($columns[$column])) {
                            $missing_columns[] = $column;
                        }
                    }

                    if (!empty($missing_columns)) {
                        throw new moodle_exception('Missing columns in local_courserecommend table: ' . implode(', ', $missing_columns));
                    }

                } catch (dml_exception $e) {
                    //error_log('Database error: ' . $e->getMessage());
                    //error_log('Debug info: ' . $e->debuginfo);
                    throw new moodle_exception('Database error: ' . $e->getMessage());
                }
                
                // Insert recommendations
                $time = time();
                $success = true;
                $errors = array();
                
                foreach ($users as $userid) {
                    try {
                        // Prepare record
                        $record = array(
                            'courseid' => $courseid,
                            'userid' => $USER->id,         // Öneren kişi
                            'recommendedby' => $USER->id,  // Öneren kişi
                            'recommendedto' => $userid,    // Önerilen kişi
                            'timemodified' => $time
                        );
                        
                        // Try to get existing recommendation
                        $existing = $DB->get_record('local_courserecommend', array(
                            'courseid' => $courseid,
                            'userid' => $USER->id,         // Öneren kişi
                            'recommendedby' => $USER->id,  // Öneren kişi
                            'recommendedto' => $userid     // Önerilen kişi
                        ));
                        
                        if ($existing) {
                            continue; // Skip if already exists
                        }
                        
                        if (!$DB->insert_record('local_courserecommend', (object)$record)) {
                            $success = false;
                            $errors[] = "Failed to insert recommendation for user {$userid}";
                        }
                    } catch (dml_exception $e) {
                        //error_log('DML Exception: ' . $e->getMessage());
                        //error_log('Error code: ' . $e->debuginfo);
                        $success = false;
                        $errors[] = $e->getMessage();
                    } catch (Exception $e) {
                        //error_log('General Exception: ' . $e->getMessage());
                        $success = false;
                        $errors[] = $e->getMessage();
                    }
                }
                
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Course recommendations sent successfully!';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Veritabanına yazmada hata: ' . implode(', ', $errors);
                }
            }
            break;
            
        default:
            throw new moodle_exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'message' => $e->getMessage()
    );
}

header('Content-Type: application/json');
echo json_encode($response);
