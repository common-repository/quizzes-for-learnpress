<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WatuLPBridge {
	// save course to quiz relations and whether to use Watu, WatuPRO or another plugin
   static function main() {
   	  global $wpdb;   	    	 
   	     	  
   	  include(WATULP_PATH."/views/main.html.php");
   }

	
	// catches both Watu and WatuPRO
	static function watu_exam_submitted($taking_id) {
		// insert success if successful
		global $wpdb, $user_ID;
		$integration_mode = get_option('watulp_integration');
		if(empty($integration_mode)) return false;
		$table = ($integration_mode == 'watu') ? WATU_TAKINGS : WATUPRO_TAKEN_EXAMS;		
		//echo $table;
		$taking = $wpdb->get_row($wpdb->prepare("SELECT exam_id, grade_id, percent_correct FROM $table WHERE ID=%d", $taking_id));
		$exam_id = $taking->exam_id;
		
		// get item ID of the associated quiz (use the data from the relations table)
		$relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATULP_RELATIONS." 
				WHERE exam_id=%d", $exam_id));
		if(empty($relation->id)) return false;		
		$item_id = $relation->lp_quiz_id;
		
		// find lp course ID as ref_id
		$ref_id = url_to_postid($_SERVER['HTTP_REFERER']);
		
		// if post is not LP lesson, do not go further
		$post_type = get_post_type($ref_id);		
		if($post_type != 'lp_course') return false;
		
		// define passed or failed based on the relation criteria
		$result = 'passed';
		if($relation->grade_id and $taking->grade_id != $relation->grade_id) $result = 'failed';
		if($relation->percent_correct and $taking->percent_correct < $relation->percent_correct) $result = 'failed';
		// echo $relation->grade_id  .' = ' . $relation->percent_correct .' = '. $result;
		
		// delete previous user items and meta related to this quiz for this user
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}learnpress_user_itemmeta 
			WHERE learnpress_user_item_id=(SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items 
				WHERE user_id=%d AND item_type='lp_quiz' AND item_id=%d)", $user_ID, $item_id));
			
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}learnpress_user_items 
			WHERE user_id=%d AND item_type='lp_quiz' AND item_id=%d", $user_ID, $item_id));
		
		// insert item as completed
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}learnpress_user_items SET 
			user_id=%d, start_time=NOW(), end_time=NOW(), status=%s, 
			ref_type='lp_course', parent_id=%d, item_type='lp_quiz', item_id=%d, ref_id=%d",
			$user_ID, 'completed', $user_ID, $item_id, $ref_id));
		$item_id = $wpdb->insert_id;
		
		// add quiz grade passed or failed
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}learnpress_user_itemmeta SET
			learnpress_user_item_id=%d, meta_key='grade', meta_value=%s", $item_id, $result));	
			
		//$user = learn_press_get_current_user();
		//$user->finish_quiz( $item_id, $ref_id );
	}
	
	// catch chained quiz
	static function chained_quiz_completed($taking_id) {
		// insert success if successful
		global $wpdb, $user_ID;
		$integration_mode = get_option('watulp_integration');
		if(empty($integration_mode) or $integration_mode != 'chained_quiz') return false;
		
		$taking = $wpdb->get_row($wpdb->prepare("SELECT quiz_id, result_id, points FROM ".CHAINED_COMPLETED." WHERE id=%d", $taking_id));
		$exam_id = $taking->quiz_id;
		
		// get item ID of the associated quiz (use the data from the relations table)
		$relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATULP_RELATIONS." 
				WHERE exam_id=%d", $exam_id));
		if(empty($relation->id)) return false;		
		$item_id = $relation->lp_quiz_id;
		
		// find lp course ID as ref_id
		$ref_id = url_to_postid($_SERVER['HTTP_REFERER']);
		
		// if post is not LP lesson, do not go further
		$post_type = get_post_type($ref_id);		
		if($post_type != 'lp_course') return false;
				
		// insert item as completed
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}learnpress_user_items SET 
			user_id=%d, start_time=NOW(), end_time=NOW(), status='completed', 
			ref_type='lp_course', parent_id=%d, item_type='lp_quiz', item_id=%d, ref_id=%d",
			$user_ID, $user_ID, $item_id, $ref_id));
		$item_id = $wpdb->insert_id;
		
		// define passed or failed based on the relation criteria
		$result = 'passed';
		if($relation->grade_id and $taking->result_id != $relation->grade_id) $result = 'failed';		
		if($relation->points and $taking->points < $relation->points) $result = 'failed';
		// echo $relation->grade_id  .' = ' . $relation->percent_correct .' = '. $result;
		
		// add quiz grade passed or failed
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}learnpress_user_itemmeta SET
			learnpress_user_item_id=%d, meta_key='_quiz_grade', meta_value=%s", $item_id, $result));
	}
	
	// get the proper % correct accordingly to latest quiz taking
	// apply_filters( 'learn_press_evaluate_quiz_results', $results, $quiz_id, $this->id );
	static function evaluate_quiz_results($results, $quiz_id, $user_id) {
		global $wpdb;
		
		// Watu or WatuPRO or none?
		$integration_mode = get_option('watulp_integration');
		if(empty($integration_mode)) return $results;
		
		switch($integration_mode) {
			case 'watu': $table = WATU_TAKINGS; $field = 'exam_id'; break;
			case 'watupro': $table = WATUPRO_TAKEN_EXAMS; $field = 'exam_id'; break;
			case 'chained_quiz' : $table = CHAINED_COMPLETED; $field = 'quiz_id'; break;
		}
				
		// find the connected quiz ID from the relation
		$relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATULP_RELATIONS." WHERE lp_quiz_id=%d", $quiz_id));
		if(empty($relation->id)) return $results;
				
		// find latest taking
		$in_progress_sql = ($integration_mode != 'watupro') ? '' : " AND in_progress=0 ";
		$taking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE $field=%d AND user_id=%d $in_progress_sql
			ORDER BY ID DESC LIMIT 1", $relation->exam_id, $user_id));
			
		if(empty($taking->ID) and empty($taking->id)) return $results;
		
		if($integration_mode == 'watu' or $integration_mode == 'watupro') {
			$results['correct_percent'] = $results['mark_percent'] = $taking->percent_correct;
			$results['wrong_percent']   = 100 - $taking->percent_correct;
		}
		else {
			// Chained quiz has no percent correct
			$results['correct_percent'] = $results['mark_percent'] = 100;
			$results['wrong_percent'] = 0;
		}
		
		$results['empty_percent']   = 0;
		$results['mark'] = $taking->points;
		
		// properly calculate correct, wrong, empty answers 
		// this is possible only in WatuPRO because Watu does not yet store individual answers
		if($integration_mode == 'watupro') {
			$num_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA 
				JOIN ".WATUPRO_QUESTIONS. " tQ ON tQ.ID = tA.question_id AND tQ.is_survey=0
				WHERE tA.taking_id = %d", $taking->ID));
			$num_correct = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_STUDENT_ANSWERS." WHERE taking_id = %d AND is_correct=1", $taking->ID));
			$num_wrong = $num_answers - $num_correct;
			if($num_wrong < 0) $num_wrong = 0;
			$results['correct'] = $num_correct;
			$results['wrong'] = $num_wrong;
		}
		if($integration_mode == 'watu') {
			// for Watu for now do a little trick - calc num correct & wrong based on the num. questions in the quiz and taking %
			// to be improved in future Watu versions
			$num_questions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATU_QUESTIONS." WHERE exam_id=%d", $taking->exam_id));
			if($num_questions) {
				$results['correct'] = round($num_questions * ($taking->percent_correct / 100));
				$results['wrong'] = $num_questions - $results['correct'];
			}
		}
		
		return $results;					
	} 
	
	// LP class-lp-quiz.php "get_mark()" is the "points" in LP quiz, which is actually just the number of non-survey questions in Watu / WatuPRO
	// apply_filters( 'learn_press_quiz_mark', $this->_mark, $this->id );
	static function filter_quiz_mark($mark, $quiz_id) {
		global $wpdb;
		
		// Watu or WatuPRO or none?
		$integration_mode = get_option('watulp_integration');
		if(empty($integration_mode)) return $mark;
		
		// find the watu/pro quiz ID from the relation
		$relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATULP_RELATIONS." WHERE lp_quiz_id=%d", $quiz_id));
		if(empty($relation->id)) return $mark;
		
		if($integration_mode == 'watu') {
			$table = WATU_QUESTIONS;
			$survey_sql = '';
		} 
		
		if($integration_mode == 'watupro') {
			$table = WATUPRO_QUESTIONS;
			$survey_sql = ' AND is_survey = 0 ';
		}
		
		if($integration_mode == 'watu' or $integration_mode == 'watupro') {
			$mark = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $table WHERE exam_id=%d $survey_sql", $relation->exam_id));
		}
		
		if($integration_mode == 'chained_quiz') {
			$mark = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM ".CHAINED_QUESTIONS." WHERE quiz_id=%d", $relation->exam_id));
		}
		
		return $mark;
	}
 	
 	// filters the post content of posts that contain watu and watupro quizzes
 	static function quiz_content($content) {
 		if(strstr($content, '[watu') or stristr($content, '[watupro') or stristr($content, '[chained-quiz')) {
 			$content .= '<script>
 			jQuery(function(){
 				jQuery(".quiz-intro").hide();
 				jQuery(".learn-press-message").hide();
 				jQuery(".quiz-result.lp-group-content-wrap").hide();
 			});
 			</script>';
 		}
 		
 		return $content;
 	}
}