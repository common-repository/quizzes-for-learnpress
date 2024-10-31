jQuery(function(){
	// hook function to hide the not-updated quiz-result area box
	// when Watu/PRO exam is submitted. Maybe in the next version we can work on ajax call to get the info and properly update?
	jQuery('#action-button').click(function(){
		jQuery('.quiz-result').hide();
	});
	
	// hide the Start quiz button from LP when page is loaded
	// needs fixes for Watu and Chained quiz, NYI
	if(jQuery('#watupro_quiz').length != 0 || jQuery('#watu_quiz').length != 0 || jQuery('.chained-quiz').length != 0) {
		jQuery('.item-meta.count-questions').hide();
		jQuery('.start-quiz').hide();
		jQuery('ul.quiz-intro').hide();
	}
});