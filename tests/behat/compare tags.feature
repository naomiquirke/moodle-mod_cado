@mod @mod_cado @mod_cado_compare_tags
Feature: Teachers can compare cado activity reports between different CADOs in different courses
  In order to compare cado reports
  As a teacher
  I need to compare two cado reports from one or two courses

  Background: Three CADOs exist in two courses.
    Given the following "courses" exist:
      | fullname | shortname | summary         | category | startdate | enablecompletion |
      | Course 1 | C1        | Base course     | 0        | ##today## | 1                |
      | Course 2 | C2        | Compared course | 0        | ##today## | 1                |
    And the following config values are set as admin:
      | config   | value | plugin |
      | tagslist | Hours | cado   |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | T1       | teacher1@example.com |
    And the following "course enrolments" exist:
      | course | user     | role           |
      | C1     | teacher1 | editingteacher |
      | C2     | teacher1 | editingteacher |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
      | Group 2 | C2     | G3       |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | teacher1 | G2    |
      | teacher1 | G3    |
    And the following "groupings" exist:
      | name       | course | idnumber |
      | Grouping 1 | C1     | GG1      |
      | Grouping 2 | C1     | GG2      |
      | Grouping 2 | C2     | GG3      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
      | GG2      | G2    |
      | GG3      | G3    |
    And the following "activities" exist:
      | activity | course | idnumber | name          | intro                         | completion | groupmode | grouping | duedate   |
      | assign   | C1     | assign1  | Assign 2      | Assign 2 involved description | 1          | 1         | GG1      | ##today## |
      | assign   | C1     | assign2  | Assign 1 temp | Assign 1 description          | 1          | 1         | GG2      | ##today## |
      | assign   | C2     | assign3  | Assign 1      | Assign 1 description          | 1          | 1         | GG3      | ##today## |
    And the following "activities" exist:
      | activity | course | idnumber | name   | intro              | completion | groupmode | grouping |
      | quiz     | C1     | quiz1    | Quiz 1 | Quiz 1 description | 1          | 1         | GG1      |
      | quiz     | C2     | quiz2    | Quiz 1 | Quiz 1 description | 1          | 1         | GG3      |
    And the following "activities" exist:
      | activity | name    | intro       | course | idnumber | groupmode | grouping | completion | duedate      |
      | forum    | Forum 1 | forum intro | C1     | forum1   | 1         | GG1      | 1          | ##tomorrow## |
      | forum    | Forum 2 | forum intro | C1     | forum2   | 1         | GG2      | 1          | ##tomorrow## |
      | forum    | Forum 2 | forum intro | C2     | forum3   | 1         | GG3      | 1          | ##today##    |
    And the following "activities" exist:
      | activity | name        | course | idnumber | groupmode | grouping | cadointro      | cadointroformat |
      | cado     | CADO test 2 | C1     | CAD002   | 1         | GG2      | <h1>Hello</h1> | 1               |
      | cado     | CADO test 3 | C2     | CAD003   | 1         | GG3      | <h1>Hello</h1> | 1               |

  @javascript
  Scenario: See differences between different CADOs in different courses
    When I am on the "Assign 1 temp" "assign activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Tags | Hours::5 |
      | name | Assign 1 |
    And I press "Save and return to course"
    And I am on the "CADO test 2" "mod_cado > view" page
    And I am on the "CADO test 3" "mod_cado > view" page
    And I navigate to "Compare" in current page administration
    And I set the field "Select CADO" to "C1 --- CADO test 2"
    And I press "Save changes"
    Then I should see "Grouping 2"
    And ".cado-different" "css_element" should exist in the "#cado-forum" "css_element"
    And ".cado-othermissing" "css_element" should exist in the "#cado-quiz" "css_element"
    And ".cado-originmissing" "css_element" should exist
    And I should see "Hours" in the "#cado-assign .cado-originmissing" "css_element"
