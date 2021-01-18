@mod @mod_cado @mod_cado_privacy
Feature: Data obtain and delete due to the privacy API
  In order to obtain and delete data for users and meet legal requirements
  As a teacher or manager
  I need to be able to request that my data can be obtained and my name deleted

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | sad      | Sad       | Teacher  |
      | mad      | Mad       | Manager  |
    And the following "courses" exist:
      | fullname | shortname | summary         | category | startdate |
      | Course 1 | C1        | Base course     | 0        | ##today## |
    And the following "course enrolments" exist:
      | course | user | role           |
      | C1     | sad  | editingteacher |
      | C1     | mad  | manager        |
    And the following config values are set as admin:
      | contactdataprotectionofficer | 1  | tool_dataprivacy |
    And the following data privacy "categories" exist:
      | name          |
      | Site category |
    And the following data privacy "purposes" exist:
      | name         | retentionperiod |
      | Site purpose | P10Y           |
    And the following config values are set as admin:
      | contactdataprotectionofficer | 1  | tool_dataprivacy |
      | privacyrequestexpiry         | 55 | tool_dataprivacy |
      | dporoles                     | 1  | tool_dataprivacy |
    And I set the site category and purpose to "Site category" and "Site purpose"
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro                |
      | assign   | C1     | assign1  | Assign 1 | Assign 1 description |
    And I log in as "sad"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "CADO report" to section "1" and I fill the form with:
      | Name for this CADO report | CADO test |
      | Grouping                  | None      |
    And I follow "CADO test"
    And I log out
    When I log in as "mad"
    And I am on "Course 1" course homepage
    And I follow "CADO test"
    And I navigate to "Approve?" in current page administration
    And I set the following fields to these values:
      | Approve CADO | 0 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: As a teacher and as a manager, request deletion of account and data
    Given I log in as "sad"
    And I follow "Profile" in the user menu
    And I follow "Data requests"
    And I follow "New request"
    And I set the field "Type" to "Delete all of my personal data"
    And I press "Save changes"
    And I log out
    And I log in as "mad"
    And I follow "Profile" in the user menu
    And I follow "Data requests"
    And I follow "New request"
    And I set the field "Type" to "Delete all of my personal data"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Users > Privacy and policies > Data requests" in site administration
    And I open the action menu in "Sad Teacher" "table_row"
    And I follow "Approve request"
    And I press "Approve request"
    And I open the action menu in "Mad Manager" "table_row"
    And I follow "Approve request"
    And I press "Approve request"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    When I follow "CADO test"
    Then I should see "This draft CADO was generated by Anonymous"
    And I should see "Approval comment reviewed by Anonymous"