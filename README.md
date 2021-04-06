# Moodle Request List Block 

This block allows to display the list of all requests by users with the number of people who have already requested the same course.
User can then choose to add oneself to this number of people, create a new request, or can remove one's request.

Installation is like all other plugins. 
Manually, you can simply unzip it in moodle/blocks/

Tweaking
--------
It's preferably to display it as a center block (which would need to tweak a little bit your theme).

I've modified some already existing in the table 'course_request' field to my need:
* "summary" is converted to the title "link" (to display the possible material which can be used for a new requested course), even if in the table 'course_request', it's still called 'summary'.
* I don't use the field "reason", "shortname" so I fill them with dummy data like '-' in the table 'summary'.

Author
------
This block was written by studyingroup.com 2021.
Released Under the GNU General Public Licence http://www.gnu.org/copyleft/gpl.html

Support
-------
For any issue with the plugin, please log in the github repository [here](https://github.com/studyingroup-com/moodle-block_requestlist/issues).

If you wish to learn a new way of studying, you can visit [studyingroup.com](https://studyingroup.com), the website to study any subject by creating study group!
