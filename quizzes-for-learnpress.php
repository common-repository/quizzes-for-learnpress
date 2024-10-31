<?php
/*
Plugin Name: Quizzes for LearnPress
Plugin URI: http://blog.calendarscripts.info/quizzes-for-learnpress/ 
Description: Use the quizzes from powerful third party quiz plugins in LearnPress courses. Currently supports <a href="https://wordpress.org/plugins/watu/">Watu</a> and <a href="http://calendarscripts.info/watupro/">WatuPRO</a>.
Author: Kiboko Labs
Version: 0.8.7
Author URI: http://kibokolabs.com
License: GPLv2 or later
Text-domain: watulp
*/

define( 'WATULP_PATH', dirname( __FILE__ ) );
define( 'WATULP_RELATIVE_PATH', dirname( plugin_basename( __FILE__ )));
define( 'WATULP_URL', plugin_dir_url( __FILE__ ));

// require controllers and models
require_once(WATULP_PATH.'/models/basic.php');
require_once(WATULP_PATH.'/controllers/bridge.php');

add_action('init', array("WatuLP", "init"));

register_activation_hook(__FILE__, array("WatuLP", "install"));