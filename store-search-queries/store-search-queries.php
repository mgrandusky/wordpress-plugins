<?php

/*
Plugin Name: Store Search Queries
Description: Store Website search queries to the Database and export this data in CSV format
Author: Mason Grandusky
Version: 1.0
*/

define('SSQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSQ_PLUGIN_FILE', __FILE__);

require_once SSQ_PLUGIN_DIR . 'inc/class.ssq.php';

