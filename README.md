# moodle-mod_cado
This Moodle module enables teachers to automatically generate a curriculum report (aka a "CADO" = Course Assessment and Delivery Outline) from a regular Moodle course.  Using an approved CADO, students can easily navigate their course requirements and link to important activities, in either browser or mobile app.  The CADO absorbs information from the forum, assignment and quiz modules, including user grouping, grading rubrics and completion criteria. It uses the tag system for custom activity related information.  It offers three CADO-specific text areas and one site-specific text area for custom information not normally held within Moodle activities.  

In addition, the mod provides an approval workflow system.  Different roles can be set up to generate, compare or approve the CADOs, with alerts and messaging at the completion of each stage in the workflow. Upon approval, students may view the CADO, and further editing is disabled.

Course variants, such as those created by different user groupings, or caused by changes in assessments, are able to be compared using different CADOs, with section differences highlighted visually.

All the terminology within the report, including "CADO", is able to be customized using Moodle's language settings.

Please contact the developer if you need module updated.

Updates
=======
Version 4.0
-----------
Update for Moodle 4.0 etc. New style of logo. Automated testing updates for new Moodle UI.

Version 3.02
-----------
Fix to allow module introductions in varying formats and with pictures. CADO table update to include format information fields.
Code style and automated testing updates.

Version 3.01
-----------
Code style and automated testing updates.
Declaration of availability for Moodle 3.11.

Version 3.0
-----------
1. The mobile CADO display is now more appropriate for a small screen.
2. The compare function has been improved:
* The CADO chooser dialog now updates the list of CADOs dynamically
* The compare function is now more detailed:
   * There is an arrow marker showing where text begins differing
   * Missing information is now added into the compare display with the appropriate highlight colour, and missing text is marked using a strikeout font style
   * Compare now also checks for differences between individual dates, tags and rubric rows
* Style changes in the CADO template will not affect the compare functionality

Mechanism: A new database field containing JSON formatted data is now used to store CADO data rather than storing generated HTML. This JSON data will enable future activity mods to be included more flexibly, and allow the CADO template to be reorganised. Because of this, a number of functions and the mustache templates were revised.

Any view of past-generated CADOs which only have an HTML version will trigger the automatic creation of JSON data from the existing HTML.  The HTML will not be overwritten.

New option: 
* "Store HTML version". The HTML field containing the generated CADO may continue to be loaded and stored for users wishing to access the HTML view in external reports.  By default this functionality will be turned off in plugin admin settings, just to avoid double storage.

Version 2.1
-----------
* To enable approval comments to be dated better, past approval comments are now separated from current approval comments in edit window, and a note is made when past comments are edited.

Version 2.0
-----------
* The Moodle Mobile app will now display an approved CADO, or if CADO is not approved, display a message to say that it is unavailable. (Currently, the app view is not device-neutral; mobile alternatives to the schedule and rubric tables need to be developed. If a reader has a view on this please contact the developer!)
* Viewing of an approved CADO is now logged. This means that access to the CADO may be reported on / evaluated from the standard course participation report, and students who have not viewed the approved CADO may be messaged through that standard process.
* CADO format: missing colon added in forum due date, and consistent spacing after colons. More classes used in schedule for more detailed styling.
