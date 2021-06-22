@mod @mod_cado @mod_cado_approve
Feature: Students can only access approved CADOs
  In order for students to access only approved cado reports
  As a manager
  I need to approve a cado report

  Background: CADO Exists.
    Given the following "courses" exist:
      | fullname | shortname | summary                  | category |
      | Course 1 | C1        | Course with working CADO | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | M1       | manager1@example.com |
      | student1 | Sam       | S1       | student1@example.com |
    And the following "course enrolments" exist:
      | course | user     | role    |
      | C1     | manager1 | manager |
      | C1     | student1 | student |
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro                |
      | assign   | C1     | assign1  | Assign 1 | Assign 1 description |
    And the following "activities" exist:
      | activity | name       | course | idnumber |
      | cado     | CADO test2 | C1     | CAD001   |
    And I log in as "manager1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "CADO test2"
    And I log out

  Scenario: See not approved message as as a student
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "CADO test2"
    Then I should see "Sorry"

  Scenario: Approve the CADO as a manager and then view CADO as a student
    When I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "CADO test2"
    And I navigate to "Approve?" in current page administration
    And I set the following fields to these values:
      | Approve CADO | 1 |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "CADO test2"
    Then I should see "Course Assessment and Delivery Outline"
    And I should see "Assign 1"
