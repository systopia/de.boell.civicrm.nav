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
class CRM_Nav_SoapCommand_UpdateTest extends \PHPUnit_Framework_TestCase {

  private $soapConnector;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->soapConnector = new CRM_Nav_SOAPConnector();
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * test the Navision API Update Command
   * Gets the first record in the Navision Database and outputs the contents
   */
  public function testSoapUpdateCommand() {
    $params = array('civiContact' =>
      array(
        'Key'         => '12;xtkAAACHAQ==10;13199485500',
        'Transferred' => '1',
      ),
    );
    $testUpdateCommand = new CRM_Nav_SoapCommand_Update($params);
    try{
      $this->soapConnector->executeCommand($testUpdateCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("Update Command failed. Message: " . $e->getMessage());
    }

    print "API call successful! Result: \n";
    print_r($testUpdateCommand->getSoapResult());
  }

}
