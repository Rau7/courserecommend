<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_courserecommend_submit_recommendation' => array(
        'classname'   => 'local_courserecommend_external',
        'methodname'  => 'submit_recommendation',
        'classpath'   => 'local/courserecommend/classes/external.php',
        'description' => 'Submit course recommendation',
        'type'        => 'write',
        'ajax'        => true
    )
);
