<?php

use CRM_Nav_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test A Navision API Read based on the SOAP Connector class as well as the
 * Read Soap Command
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Nav_Data_NavContactTest extends \PHPUnit_Framework_TestCase {

  private $contact_1;
  private $contact_2;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
//    return \Civi\Test::headless()
//      ->installMe(__DIR__)
//      ->apply();
  }

  public function setUp() {
    $nav_contact_file = "resources/dataModel/testData/NavContact1.json";
    $file_content = explode("\n", file_get_contents($nav_contact_file));
    $this->contact_1 = json_decode(array_pop(array_reverse($file_content)), TRUE);

    $nav_contact_file = "resources/dataModel/testData/NavContact2.json";
    $file_content = explode("\n", file_get_contents($nav_contact_file));
    $this->contact_1 = json_decode(array_pop(array_reverse($file_content)), TRUE);
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * test the Navision API Read Command
   * Gets the first record in the Navision Database and outputs the contents
   */
  public function testNavContactData() {
    // TODO for unit test:
    // Add 2 static contact data
    // --> compare in 2 different orders
    // result should be the same
    // implicit data verification in __constuct (verify data)
    // TODO: Add data from resource file?
    // resources/data/test/NavContactData.json
  }

}
