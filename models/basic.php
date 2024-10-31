<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// main model containing general config and UI functions
class WatuLP {
   static function install($update = false) {
   	global $wpdb;	
   	$wpdb -> show_errors();
   	
   	if(!$update) self::init();
	   
    // relations bewteen completed exams and mailing lists. 
    // For now not depending on exam result but place the field for later use
    if($wpdb->get_var("SHOW TABLES LIKE '".WATULP_RELATIONS."'") != WATULP_RELATIONS) {  
        $sql = "CREATE TABLE `".WATULP_RELATIONS."` (
				id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
				exam_id int(11) unsigned NOT NULL default '0',
				lp_quiz_id int(11) unsigned NOT NULL default '0',
				grade_id int(11) unsigned NOT NULL default '0',
				percent_correct INT UNSIGNED NOT NULL DEFAULT '0'
			) CHARACTER SET utf8;";
        $wpdb->query($sql);         
    	}
    	
	    self :: add_db_fields(array(
		  	  array("name" => 'points', 'type' => 'INT UNSIGNED NOT NULL DEFAULT 0'),
		 ), WATULP_RELATIONS);
		 
		update_option('watulp_version', 0.8);
	} // end install	   
	
	// initialization
	static function init() {
		global $wpdb;
		load_plugin_textdomain( 'watulp' );
		define('WATULP_RELATIONS', $wpdb->prefix.'watulp_relations');
		
		add_action('wp_enqueue_scripts', array(__CLASS__, 'scripts'));
	
		add_filter('the_content', array('WatuLPBridge', 'quiz_content'));
		add_action('watu_exam_submitted', array('WatuLPBridge', 'watu_exam_submitted'));
		add_action('watupro_completed_exam', array('WatuLPBridge', 'watu_exam_submitted'));
		add_action('chained_quiz_completed', array('WatuLPBridge', 'chained_quiz_completed'));
		add_filter('learn_press_evaluate_quiz_results', array('WatuLPBridge', 'evaluate_quiz_results'), 10, 3);
		add_filter('learn_press_quiz_mark', array('WatuLPBridge', 'filter_quiz_mark'), 10, 2);
		add_filter('learn_press_menu_items', array(__CLASS__, 'admin_menu_filter'));
		
		add_action('admin_menu', array(__CLASS__, "menu")); // NEW for LP version 3+
		
		$version = get_option('watulp_version');
		if($version < 0.8) self ::install(true);
	}	
	
	// CSS and JS
	static function scripts() {   
   	wp_enqueue_script('jquery');
   	wp_enqueue_script(
			'watulp-script',
			WATULP_URL.'js/watu-learnpress.js',
			array(),
			'4.9');
	}
	
	// adds the connector menu to LearnPress
	// DEPRECATED - works with version 2 and below
	static function admin_menu_filter($menu_items) {		
		$menu_items['lp_quiz_connector']	= array(
			'learn_press',
			__( 'Quiz Connector', 'learnpress' ),
			__( 'Quiz Connector', 'learnpress' ),
				'manage_options',
			'watulp-quiz-connector',
			array(__CLASS__, 'options'),
		);	
		
		return $menu_items;
	}		
	
	static function menu() {
		add_menu_page(__('Quizzes for LearnPress', 'learnpress'), __('Quizzes for LearnPress', 'learnpress'), 'manage_options', 
   		'watulp-quiz-connector', array(__CLASS__, 'options'));	
	}
	
	static function options() {
		global $wpdb;
		
		if(!empty($_POST['save_options']) and check_admin_referer('watulp_options')) {
			$integration_mode = $_POST['integration_mode'];
			if(!in_array($integration_mode, array('watu', 'watupro', 'chained_quiz'))) $integration_mode = '';
			
			update_option('watulp_integration', $integration_mode);
		}
		
		$integration_mode = get_option('watulp_integration');
		switch($integration_mode) {
			case 'watu': $plugin_name = 'Watu'; break;
			case 'watupro': $plugin_name = 'WatuPRO'; break;
			case 'chained_quiz': $plugin_name = 'Chained Quiz'; break;
		}
		
		// add/save/delete relations
		if(!empty($_POST['add_relation']) and check_admin_referer('watulp_relation')) {
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATULP_RELATIONS." SET
				exam_id=%d, lp_quiz_id=%d, grade_id=%d, percent_correct=%d, points=%d",
				intval($_POST['exam_id']), intval($_POST['lp_quiz_id']), intval($_POST['grade_id']), 
				intval(@$_POST['percent_correct']), intval(@$_POST['points']) ));
		}
		
		if(!empty($_POST['save_relation']) and check_admin_referer('watulp_relation')) {
			$wpdb->query($wpdb->prepare("UPDATE ".WATULP_RELATIONS." SET
				exam_id=%d, lp_quiz_id=%d, grade_id=%d, percent_correct=%d, points=%d WHERE id=%d",
				intval($_POST['exam_id']), intval($_POST['lp_quiz_id']), intval($_POST['grade_id']), 
				intval(@$_POST['percent_correct']), intval(@$_POST['points']), intval($_POST['id']) ));
		}
		
		if(!empty($_POST['del_relation']) and check_admin_referer('watulp_relation')) {
			$wpdb->query($wpdb->prepare("DELETE FROM " . WATULP_RELATIONS." WHERE id=%d ", intval($_POST['id']) ));
		}
		
		if($integration_mode) {
			// select relations
			$relations = $wpdb->get_results("SELECT * FROM ".WATULP_RELATIONS." ORDER BY id");
		
			// select LP quizzes
			$lp_quizzes = get_posts(array('post_type' => 'lp_quiz', 'posts_per_page' => -1));
			
			// select Watu/PRO quizzes if mode is selected 
			switch($integration_mode) {
				case 'watu': $table = WATU_EXAMS; $field = 'name'; break;
				case 'watupro': $table = WATUPRO_EXAMS; $field = 'name'; break;
				case 'chained_quiz' : $table = CHAINED_QUIZZES; $field = 'title'; break;
			}
			
			$exams = $wpdb->get_results("SELECT * FROM $table ORDER BY $field");
			
			// for each exam get grades
			foreach($exams as $cnt => $exam) {
				if($integration_mode == 'watu') {
					$grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATU_GRADES." WHERE exam_id=%d ORDER BY gtitle", $exam->ID));
				}
				
				if($integration_mode == 'watupro') {
					$grades = WTPGrade :: get_grades($exam);
				}
				
				if($integration_mode == 'chained_quiz') {
					$grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".CHAINED_RESULTS." 
						WHERE quiz_id = %d ORDER BY points_bottom DESC", $exam->id));
				}
				
				$exams[$cnt]->grades = $grades;
			} // end filling grades
		}
				
		include(WATULP_PATH . '/views/main.html.php');
	} // end options()
	
	// function to conditionally add DB fields
	static function add_db_fields($fields, $table) {
		global $wpdb;
		
		// check fields
		$table_fields = $wpdb->get_results("SHOW COLUMNS FROM `$table`");
		$table_field_names = array();
		foreach($table_fields as $f) $table_field_names[] = $f->Field;		
		$fields_to_add=array();
		
		foreach($fields as $field) {
			 if(!in_array($field['name'], $table_field_names)) {
			 	  $fields_to_add[] = $field;
			 } 
		}
		
		// now if there are fields to add, run the query
		if(!empty($fields_to_add)) {
			 $sql = "ALTER TABLE `$table` ";
			 
			 foreach($fields_to_add as $cnt => $field) {
			 	 if($cnt > 0) $sql .= ", ";
			 	 $sql .= "ADD $field[name] $field[type]";
			 } 
			 
			 $wpdb->query($sql);
		}
}
}