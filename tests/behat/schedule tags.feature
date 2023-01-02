@mod @mod_cado @javascript
Feature: CADOs can have schedules with tag values totalled
  In order to view extraneous mod info such as numeric attributes like working hours and wordcount
  As a teacher
  I need to add information using tags, and have that information displayed in the body, in the schedule, and totalled

  Scenario: Activities exist with correct tags, schedule appears with correct tags, schedule totals correctly.
    Given the following "courses" exist:
      | fullname | shortname | summary         | category | startdate  | enddate                    | format |
      | Course 1 | C1        | Base course     | 0        |  ##today## | ##last day of next month## | weeks  |
    And the following "tags" exist:
      | name   | isstandard  |
      | Easy   | 1           |
    And the following "users" exist:
      | username | firstname | lastname  | email                |
      | teacher1 | Teacher   | T1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | course | user     | role           |
      | C1     | teacher1 | editingteacher |
    And I log in as "admin"
    Given the following config values are set as admin:
      | config      | value                     | plugin |
      | tagslist    | Hours, Frogs,, Difficulty | cado   |
      | tagschedule | 1                         | cado   |
      | sumschedule | 1                         | cado   |
# May as well check that the logo doesn't cause an issue.
      | showlogo    | 1                         | cado   |
    And the following "activities" exist:
      | activity   | course | idnumber | name   | timeclose                       |
      | quiz       | C1     | quiz1    | Quiz 1 | ##second sunday of next month## |
    And the following "activities" exist:
      | activity | course | idnumber | name     | duedate                        |
      | assign   | C1     | assign1  | Assign 1 | ##first sunday of next month## |
    And the following "activities" exist:
      | activity   | name    | course | idnumber | duedate                        |
      | forum      | Forum 1 | C1     | forum1   | ##third sunday of next month## |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "CADO report" to section "0" and I fill the form with:
      | Name for this CADO report | CADO test 1 |
    And I am on the "Quiz 1" "quiz activity" page logged in as teacher1
    And I set the following fields to these values:
      | Tags | Hours::5, Frogs::Tree, Difficulty::100 |
    And I press "Save and return to course"
    And I click on "Assign 1" "activity"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Tags | Hours::5, Frogs::Green, Difficulty::5 |
    And I press "Save and return to course"
    And I click on "Forum 1" "activity"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Tags | Hours::5, Frogs::Green Tree, Easy |
    And I press "Save and return to course"
    And I click on "CADO test 1" "activity"
# Because "Easy" does not appear in the CADO site admin settings, it should not appear in the CADO at all.
    Then I should not see "Easy"
    And I should see "1" occurrences of "Frogs" in the "#cado-schedule" "css_element"
    And I should see "2" occurrences of "Tree" in the "#cado-schedule" "css_element"
# Because "Difficulty" does not appear in the first three tags, it should not appear in the schedule, but it should appear in the mod info.
    And I should not see "Difficulty" in the "#cado-schedule" "css_element"
    And I should see "1" occurrences of "Difficulty" in the "#cado-assign" "css_element"
    And I should see "1" occurrences of "Difficulty" in the "#cado-quiz" "css_element"
    And I should see "100" in the "#cado-quiz" "css_element"
    And I should see "TOTALS" in the ".cado-sched-total" "css_element"
    And I should see "1" occurrences of "15" in the ".cado-tc1.cado-sched-total" "css_element"
