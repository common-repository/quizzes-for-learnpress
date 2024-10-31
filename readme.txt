=== Quizzes for LearnPress ===
Contributors: prasunsen
Tags: learnpress, quiz, test, exam, integration, connector, watu
Requires at least: 3.3
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later

Use the quizzes from powerful third party quiz plugins in LearnPress courses. Currently supports <a href="https://wordpress.org/plugins/watu/">Watu</a>, <a href="http://calendarscripts.info/watupro/">WatuPRO</a> and <a href="https://wordpress.org/plugins/chained-quiz/">Chained Quiz</a>.

== Description ==

***IMPORTANT: To make this work properly the empty quiz you create in LearnPress should really be empty: NO QUESTIONS in it. Passing grade should be 0%. You should create your questions and grades only in the connected plugin!***

This is a quiz connector between the quiz plugins Watu, WatuPRO, and Chained Quiz and the LMS [LearnPress](https://thimpress.com/product/learnpress-wordpress-lms-plugin/ "LearnPress"). 

Use the quizzes from powerful third party quiz plugins in LearnPress courses. LearnPress is a great learning management system but its built-in quiz tool is simple.

What if you could use powerful quiz plugins inside LearnPress? Now you can: not just include a quiz from another plugin and make it work, but actually evaluate the result of the quiz the same way as LP quizzes are evaluated to complete a course.

### Currently Supported Plugins ###

* [Watu](https://wordpress.org/plugins/watu/advanced/ "Watu") (free)
* [WatuPRO](http://calendarscripts.info/watupro/ "WatuPRO") (integration provided "as is", no email support)
* [Chained quiz](https://wordpress.org/plugins/chained-quiz/ "Chained Quiz") 

* Ask for more in the forum

### How Does It Work ###

It's fairly simple:

1. Create a quiz in LearnPress normally. Don't create any questions. This is VERY IMPORTANT.
2. Set the quiz passing grade to 0%.
3. In the quiz description box insert the quiz shortcode from the connecting plugin - for example [watu 1] and save
4. Go to Quiz Connector link under the LearnPress menu (for LP version older than 3) or to Quizzes For Learnpress link in in your main WP menu (for LP versions newer than 3). Select the quiz plugin you want to connect to. You need to install and activate the connected plugin.
5. On the same page create a relation between the LearnPress quiz you created in step 1 above and the real quiz in Watu, WatuPRO, or Chained Quiz. Select desired grade (if any) and/or percent correct answers from the connecting quiz. 
6. When the user visits the LearnPress quiz link they'll get the shortcode processed and the quiz displayed. The connector will evaluate the quiz result as LearnPress requirement and if passed, the user will have passed the LearnPress quiz as well.

See this guide with more details and pictures [here](http://blog.calendarscripts.info/quizzes-for-learnpress "Quizzes for LearnPress")

The integration is work in progress, your suggestions and contributions are most welcome! 

***This is a third party integration by the authors of Watu and not an official release by LearnPress***

***IMPORTANT***

Since this is a third party integration we can not perfectly integrate with the ever changing LearnPress interface.
Some of the quiz stats that LearnPress quiz pages show on screen can not be updated live from Watu or WatuPRO.
This is a free connector, not a complete integration and comes without any guarantees.
If you are planning to use the plugin with WatuPRO please request evaluation instead of outright purchasing.   

== Installation ==

Install and activate like any regular WordPress plugin.

== Frequently Asked Questions ==

None yet, please ask in the forum.

== Screenshots ==


== Changelog ==
= Version 0.8.7 =
- Check referrer post type to avoid nasty errors when the connecting quiz is not submitted via LP
- Added integration for Chained Quiz
- Fixed bug: the drop-down selector for LP quizzes was showing only 5 quizzes
- Updated the JS to hide the wrong LP quiz messages from pages that use connected quizzes. 
- Updated for compatibility with LP 3+. Hide irrelevant / incorrect buttons and values from the display. 
- Moved the menu link as a main WP menu link
- Fixed front-end integration with Watu and Chained quiz for LP 3+
- Fixed latest incompatibility issues with not properly marking grades.

= Version 0.7 =

First public release.