<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings category
    $ADMIN->add('localplugins', new admin_category('local_courserecommend', get_string('pluginname', 'local_courserecommend')));

    $settings = new admin_settingpage('local_courserecommend_settings', get_string('pluginname', 'local_courserecommend'));

    if ($ADMIN->fulltree) {
        global $PAGE, $OUTPUT;

        // Get current excluded courses
        $excludedcourses = get_config('local_courserecommend', 'excluded_courses');
        $excludedarray = !empty($excludedcourses) ? explode(',', $excludedcourses) : array();

        // Get all courses
        $courses = $DB->get_records('course', array(), 'fullname ASC', 'id, fullname');
        
        // Prepare HTML
        $html = html_writer::start_div('course-selector-container');
        
        // Main selector container
        $html .= html_writer::start_div('selector-main');
        
        // Available courses list
        $html .= html_writer::start_div('available-courses-section');
        $html .= html_writer::tag('h4', get_string('available_courses', 'local_courserecommend'));
        $html .= html_writer::tag('input', '', array(
            'type' => 'text',
            'id' => 'available-search',
            'placeholder' => get_string('search', 'local_courserecommend')
        ));
        $html .= html_writer::start_tag('ul', array('id' => 'available-courses'));
        foreach ($courses as $course) {
            if ($course->id == 1 || in_array($course->id, $excludedarray)) continue;
            $html .= html_writer::tag('li', $course->fullname, array('data-id' => $course->id));
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();

        // Buttons
        $html .= html_writer::start_div('selector-buttons');
        $html .= html_writer::tag('button', '>', array('id' => 'add-to-excluded', 'type' => 'button'));
        $html .= html_writer::tag('button', '<', array('id' => 'remove-from-excluded', 'type' => 'button'));
        $html .= html_writer::end_div();

        // Excluded courses list
        $html .= html_writer::start_div('excluded-courses-section');
        $html .= html_writer::tag('h4', get_string('excluded_courses', 'local_courserecommend'));
        $html .= html_writer::tag('input', '', array(
            'type' => 'text',
            'id' => 'excluded-search',
            'placeholder' => get_string('search', 'local_courserecommend')
        ));
        $html .= html_writer::start_tag('ul', array('id' => 'excluded-courses'));
        foreach ($courses as $course) {
            if ($course->id == 1) continue;
            if (in_array($course->id, $excludedarray)) {
                $html .= html_writer::tag('li', $course->fullname, array('data-id' => $course->id));
            }
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        
        $html .= html_writer::end_div(); // End selector-main
        
        // Save button
        $html .= html_writer::start_div('selector-footer');
        $html .= html_writer::tag('button', get_string('savechanges'), array('id' => 'save-changes', 'type' => 'button'));
        $html .= html_writer::end_div();
        
        $html .= html_writer::end_div(); // End course-selector-container

        $settings->add(new admin_setting_heading('local_courserecommend/excluded_courses',
            get_string('excluded_courses', 'local_courserecommend'),
            $html
        ));

        // Add required JavaScript
        $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            // Liste öğelerine tıklama
            $('#available-courses li, #excluded-courses li').on('click', function() {
                $(this).toggleClass('selected');
            });

            // Arama fonksiyonu
            function filterList(searchTerm, listId) {
                $(listId + ' li').each(function() {
                    var text = $(this).text().toLowerCase();
                    var match = text.indexOf(searchTerm.toLowerCase()) > -1;
                    $(this).toggle(match);
                });
            }

            // Arama kutularına yazıldığında filtreleme yap
            $('#available-search').on('keyup', function() {
                filterList($(this).val(), '#available-courses');
            });

            $('#excluded-search').on('keyup', function() {
                filterList($(this).val(), '#excluded-courses');
            });

            // Sağa taşıma butonu
            $('#add-to-excluded').on('click', function(e) {
                e.preventDefault();
                $('#available-courses li.selected').each(function() {
                    var item = $(this).clone();
                    item.removeClass('selected');
                    $('#excluded-courses').append(item);
                    $(this).remove();
                });
            });

            // Sola taşıma butonu
            $('#remove-from-excluded').on('click', function(e) {
                e.preventDefault();
                $('#excluded-courses li.selected').each(function() {
                    var item = $(this).clone();
                    item.removeClass('selected');
                    $('#available-courses').append(item);
                    $(this).remove();
                });
            });

            // Kaydet butonu
            $('#save-changes').on('click', function(e) {
                e.preventDefault(); // Form submit'i engelle
                e.stopPropagation(); // Event'in yukarı yayılmasını engelle
                
                var excludedIds = [];
                $('#excluded-courses li').each(function() {
                    var id = $(this).attr('data-id');
                    if (id) {
                        excludedIds.push(id);
                    }
                });

                $.ajax({
                    url: M.cfg.wwwroot + '/local/courserecommend/ajax.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'saveexcluded',
                        sesskey: M.cfg.sesskey,
                        excluded: excludedIds.join(',')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Başarılı olduğunda kullanıcıya bildir ve sayfayı yenile
                            alert('Değişiklikler başarıyla kaydedildi.');
                            window.location.reload();
                        } else {
                            console.error('Error:', response.message);
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Hata: ' + error);
                    }
                });
                
                return false; // Form submit'i engelle
            });
        });");
    }

    // Add settings page to the category
    $ADMIN->add('local_courserecommend', $settings);
}
