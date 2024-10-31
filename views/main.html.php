<div class="wrap">
	<h1><?php _e('Quiz Connector for LearnPress', 'watulp')?></h1>
	
	<p><?php _e('This connector will allow you to use more powerful third-party quiz plugins for your LearnPress based LMS. Obtain and install some of the supported plugins and enjoy the experience:', 'watulp');?></p>
	
	<h3><?php _e('Integrate With Quiz Plugin:', 'watulp');?></h3>
	<form method="post">
	<ul>
		<li><input type="radio" name="integration_mode" value="" <?php if(empty($integration_mode)) echo 'checked'?>> <?php _e("Don't integrate (use the built-in quiz plugin)", 'watulp');?></li>
		<li><input type="radio" name="integration_mode" value="watu" <?php if($integration_mode == 'watu') echo 'checked'?>> <?php printf(__('Integrate with <a href="%s" target="_blank">Watu</a>', 'watulp'), 'https://wordpress.org/plugins/watu/');?></li>
		<li><input type="radio" name="integration_mode" value="watupro" <?php if($integration_mode == 'watupro') echo 'checked'?>> <?php printf(__('Integrate with <a href="%s" target="_blank">WatuPRO</a>', 'watulp'), 'https://calendarscripts.info/watupro/');?></li>
		<li><input type="radio" name="integration_mode" value="chained_quiz" <?php if($integration_mode == 'chained_quiz') echo 'checked'?>> <?php printf(__('Integrate with <a href="%s" target="_blank">Chained Quiz</a>', 'watulp'), 'https://wordpress.org/plugins/chained-quiz/');?></li>
	</ul>
	
	<p><?php printf(__('Not sure how to integrate? See <a href="%s" target="_blank">here</a>.', 'watulp'), 'http://blog.calendarscripts.info/quizzes-for-learnpress');?></p>
	
	<p><input type="submit" name="save_options" value="<?php _e('Save Options', 'watulp');?>"></p>
	<?php wp_nonce_field('watulp_options');?>
	</form>
	
	<?php if(!empty($integration_mode)):?>
		<h3><?php printf(__('LearnPress Quiz to %s Quiz Relations', 'watulp'), $plugin_name);?></h3>
		
		<p><?php printf(__('In addition to inserting the quiz shortcode of the connected plugin inside the "Description" box of the LearnPress quiz you need to specify the relation between quizzes and success criteria on this page. See examples and more information <a href="%s" target="_blank">here</a>.', 'watulp'), 'http://blog.calendarscripts.info/quizzes-for-learnpress');?></p>
		
		<form method="post" onsubmit="return WatuLPValidateRelation(this);">
			<p><?php _e('LP Quiz:', 'watulp');?> <select name="lp_quiz_id">
				<?php foreach($lp_quizzes as $quiz):?>
					<option value="<?php echo $quiz->ID?>"><?php echo stripslashes($quiz->post_title);?></option>
				<?php endforeach;?>
			</select>
			<?php _e('connects to:', 'watulp');?> <select name="exam_id" onchange="watuLPFillGrades(this.value, 'gradeID');">
				<option value=""><?php _e('- please select -', 'watulp');?></option>
				<?php foreach($exams as $exam):
					if(empty($exam->name)) $exam->name = $exam->title;
					if(empty($exam->ID)) $exam->ID = $exam->id; ?>
					<option value="<?php echo $exam->ID?>"><?php echo stripslashes($exam->name);?></option>
				<?php endforeach;?>
			</select>
			<?php _e('required grade:', 'watulp');?>
			<select name="grade_id" id="gradeID">
				<option value="0"><?php _e('- select connecting quiz -', 'watulp');?></option>
			</select>
			<?php if($integration_mode == 'chained_quiz'):?>
				<?php _e('required points:', 'watulp');?>
				<input type="text" name="points" size="4">
			<?php else:?>
				<?php _e('required % correct answers:', 'watulp');?>
				<input type="text" name="percent_correct" size="4">
			<?php endif;?> 
			<input type="submit" name="add_relation" value="<?php _e('Add relation', 'watulp');?>"></p>
			<?php wp_nonce_field('watulp_relation');?>
		</form>
		
		<?php foreach($relations as $relation):?>
			<form method="post" onsubmit="return WatuLPValidateRelation(this);">
				<p><?php _e('LP Quiz:', 'watulp');?> <select name="lp_quiz_id">
					<?php foreach($lp_quizzes as $quiz):?>
						<option value="<?php echo $quiz->ID?>" <?php if($relation->lp_quiz_id == $quiz->ID) echo 'selected'?>><?php echo stripslashes($quiz->post_title);?></option>
					<?php endforeach;?>
				</select>
				<?php _e('connects to:', 'watulp');?> <select name="exam_id" onchange="watuLPFillGrades(this.value, 'gradeID<?php echo $relation->id?>');">
					<option value=""><?php _e('- please select -', 'watulp');?></option>
					<?php $relation_exam = null; 
					foreach($exams as $exam):
						if(empty($exam->name)) $exam->name = $exam->title; 
						if(empty($exam->ID)) $exam->ID = $exam->id;
						if($relation->exam_id == $exam->ID) $relation_exam = $exam;?>
						<option value="<?php echo $exam->ID?>" <?php if($relation->exam_id == $exam->ID) echo 'selected'?>><?php echo stripslashes($exam->name);?></option>
					<?php endforeach;?>
				</select>
				<?php _e('required grade/result:', 'watulp');?>
				<select name="grade_id" id="gradeID<?php echo $relation->id?>">
					<option value=""><?php _e('- Any grade -', 'watulp');?></option>
					<?php foreach($relation_exam->grades as $grade):
						if(empty($grade->ID)) $grade->ID = $grade->id;
						if(empty($grade->gtitle)) $grade->gtitle = $grade->title; ?>
						<option value="<?php echo $grade->ID?>" <?php if($grade->ID == $relation->grade_id) echo 'selected'?>><?php echo stripslashes($grade->gtitle);?></option>
					<?php endforeach;?>					
				</select>
				<?php if($integration_mode == 'chained_quiz'):?>
					<?php _e('required points:', 'watulp');?>
					<input type="text" name="points" size="4" value="<?php echo $relation->points;?>">
				<?php else:?>
					<?php _e('required % correct answers:', 'watulp');?>
					<input type="text" name="percent_correct" size="4" value="<?php echo $relation->percent_correct;?>"> 
				<?php endif;?>	
				<input type="submit" name="save_relation" value="<?php _e('Save', 'watulp');?>">
				<input type="button" value="<?php _e('Delete', 'watulp');?>" onclick="confirmDelRelation(this.form);"></p>
				<input type="hidden" name="del_relation" value="0">
				<input type="hidden" name="id" value="<?php echo $relation->id?>">
				<?php wp_nonce_field('watulp_relation');?>
			</form>
		<?php endforeach;?>
	<?php endif;?>
</div>

<script type="text/javascript" >
function WatuLPValidateRelation(frm) {
	if(frm.exam_id.value == 0) {
		alert("<?php _e('Please select connecting quiz.', 'watulp');?>");
		frm.exam_id.focus();
		return false;
	}
	
	return true;
}

function watuLPFillGrades(examID, eltID) {
	var grades = [];
	<?php foreach($exams as $exam):
		if(empty($exam->ID)) $exam->ID = $exam->id; // to handle Chained quiz?>
	grades[<?php echo $exam->ID?>] = [<?php foreach($exam->grades as $grade):
		if(empty($grade->gtitle)) $grade->gtitle = $grade->title;
		if(empty($grade->ID)) $grade->ID = $grade->id;?>
		{"ID" : <?php echo $grade->ID?>, "title" : "<?php echo $grade->gtitle;?>"},
		<?php endforeach;?>];	
	<?php endforeach;?>
	
	var examGrades = grades[examID];
	
	var html = '<option value=""><?php _e('- Any grade -', 'watulp');?></option>';
	jQuery(examGrades).each(function(i, grade){
		html += '<option value="'+ grade.ID +'">' + grade.title + '</option>';
	});
	
	jQuery('#'+eltID).html(html);
}

function confirmDelRelation(frm) {
	if(confirm("<?php _e('Are you sure?', 'watulp');?>")) {
		frm.del_relation.value=1;
		frm.submit();
	}
}
</script>