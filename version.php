<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_courserecommend';
$plugin->version = 2024121999;  // YYYYMMDDXX formatında
$plugin->requires = 2022041900; // Moodle 4.0
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.78';    // recommendedby alanı eklendi

$plugin->has_config = true;
