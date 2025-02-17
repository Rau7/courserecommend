<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_courserecommend_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024121999) {
        // Define table local_courserecommend
        $table = new xmldb_table('local_courserecommend');

        // Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recommendedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recommendedto', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('recommendedby', XMLDB_KEY_FOREIGN, array('recommendedby'), 'user', array('id'));
        $table->add_key('recommendedto', XMLDB_KEY_FOREIGN, array('recommendedto'), 'user', array('id'));

        // Adding indexes
        $table->add_index('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Drop table if exists
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Create table
        $dbman->create_table($table);

        // Update plugin savepoint
        upgrade_plugin_savepoint(true, 2024121999, 'local', 'courserecommend');
    }

    return true;
}
