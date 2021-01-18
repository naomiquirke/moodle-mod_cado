# moodle-mod_cado
This Moodle module enables teachers to automatically generate a curriculum report (aka a "CADO" = Course Assessment and Delivery Outline) from a regular Moodle course.  Using an approved CADO, students can easily navigate their course requirements and link to important activities, in either browser or mobile app.  The CADO absorbs information from the forum, assignment and quiz modules, including user grouping, grading rubrics and completion criteria. It uses the tag system for custom activity related information.  It offers three CADO-specific text areas and one site-specific text area for custom information not normally held within Moodle activities.  

In addition, the mod provides an approval workflow system.  Different roles can be set up to generate, compare or approve the CADOs, with alerts and messaging at the completion of each stage in the workflow. Upon approval, students may view the CADO, and further editing is disabled.

Course variants, such as those created by different user groupings, or caused by changes in assessments, are able to be compared using different CADOs, with section differences highlighted visually.

All the terminology within the report, including "CADO", is able to be customized using Moodle's language settings.

Updates
=======

Version 2.1
-----------
* To enable approval comments to be dated better, past approval comments are now separated from current approval comments in edit window, and a note is made when past comments are edited.

Version 2.0
-----------
* The Moodle Mobile app will now display an approved CADO, or if CADO is not approved, display a message to say that it is unavailable. (Currently, the app view is not device-neutral; mobile alternatives to the schedule and rubric tables need to be developed. If a reader has a view on this please contact the developer!)
* Viewing of an approved CADO is now logged. This means that access to the CADO may be reported on / evaluated from the standard course participation report, and students who have not viewed the approved CADO may be messaged through that standard process.
* CADO format: missing colon added in forum due date, and consistent spacing after colons. More classes used in schedule for more detailed styling.