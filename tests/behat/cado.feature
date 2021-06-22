@mod @mod_cado @mod_cado_create
Feature: Cado activity reports contain correctly grouped, completion enabled, activities
  In order to create cado reports
  As a teacher
  I need to create a cado report from existing activities that have completion enabled, taking account their groupings

  Scenario: Create CADO activity.
    Given the following "courses" exist:
      | fullname | shortname | summary                       | category | enablecompletion | startdate |
      | Course 1 | C1        | Prove the CADO activity works | 0        | 1                | ##today## |
    And the following "users" exist:
      | username | firstname | lastname  | email                |
      | teacher1 | Teacher   | T1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | course | user     | role           |
      | C1     | teacher1 | editingteacher |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | teacher1 | G2    |
    And the following "groupings" exist:
      | name        | course | idnumber |
      | Grouping 1  | C1     | GG1      |
      | Grouping 2  | C1     | GG2      |
      | Grouping 3  | C1     | GG3      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
      | GG2      | G2    |
      | GG3      | G1    |
      | GG3      | G2    |
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro                | completion |
      | assign   | C1     | assign1  | Assign 1 | Assign 1 description | 1          |
    And the following "activities" exist:
      | activity   | course | idnumber | name   | intro              | completion |
      | quiz       | C1     | quiz1    | Quiz 1 | Quiz 1 description | 2          |
      | quiz       | C1     | quiz2    | Quiz 2 | Quiz 2 description | 0          |
    And the following "activities" exist:
      | activity   | name                   | intro             | course | idnumber     | groupmode | grouping | completion |
      | forum      | No group forum         | Test0 forum name  | C1     | forum0       | 0         |          | 2          |
      | forum      | Groupings G1 forum     | Test1 forum name  | C1     | forum1       | 1         | GG1      | 1          |
      | forum      | Groupings G2 forum     | Test2 forum name  | C1     | forum2       | 1         | GG2      | 1          |
      | forum      | Groupings G3 forum     | Test3 forum name  | C1     | forum3       | 1         | GG3      | 1          |
    And the following "activities" exist:
      | activity | name        | course | idnumber | groupmode | grouping |
      | cado     | CADO test 1 | C1     | CAD001   | 1         | GG1      |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "CADO test 1"
    Then I should see "This draft CADO was generated by Teacher T1"
    And I should see "Grouping 1"
    And I should see "Course Assessment and Delivery Outline"
    And I should see "Assign 1"
    And I should see "Quiz 1"
    And I should not see "Quiz 2"
    And I should see "No group forum"
    And I should see "Groupings G1 forum"
    And I should not see "Groupings G2 forum"
    And I should see "Groupings G3 forum"
