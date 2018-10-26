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
class CRM_Nav_SoapCommand_UpdateMultipleTest extends \PHPUnit_Framework_TestCase {

  private $soapConnectorContact;
  private $soapConnectorRelationship;
  private $soapConnectorContactProcess;
  private $soapConnectorContactStatus;


  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
//    return \Civi\Test::headless()
//      ->installMe(__DIR__)
//      ->apply();
  }

  public function setUp() {
    $this->soapConnectorContact = new CRM_Nav_SOAPConnector("civiContact");
    $this->soapConnectorRelationship = new CRM_Nav_SOAPConnector("civiContRelation");
    $this->soapConnectorContactProcess = new CRM_Nav_SOAPConnector("civiProcess");
    $this->soapConnectorContactStatus = new CRM_Nav_SOAPConnector("civiContStatus");
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * test the Navision API UpdateMultiple Contacts Command
   */
  public function testSoapUpdateMultipleContactsCommand() {
    // get contact #1
    $r_params = array( 'filter' =>
      array(
        "Field"     => "Transferred",
        "Criteria"  => "0",
      ),
      'setSize' => '2',
    );
    $readCommand = new CRM_Nav_SoapCommand_ReadMultiple($r_params);
    try{
      $this->soapConnectorContact->executeCommand($readCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("ReadMultiple Command failed. Message: " . $e->getMessage());
    }
    $read_result = json_decode(json_encode($readCommand->getSoapResult()), TRUE);
    foreach ($read_result['ReadMultiple_Result']['civiContact'] as &$read_entry) {
      $read_entry['Transferred'] = 1;
    }
    $update_params['civiContact_List'] = $read_result['ReadMultiple_Result'];

    $testUpdateMultipleCommand = new CRM_Nav_SoapCommand_UpdateMultiple($update_params);
    try{
      $this->soapConnectorContact->executeCommand($testUpdateMultipleCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("UpdateMultipleContacts Command failed. Message: " . $e->getMessage());
    }

    print "UpdateMultiple Contacts API call successful! \n";
    print_r($testUpdateMultipleCommand->getSoapResult());
  }

  /**
   * test the Navision API UpdateMultiple Relation Command
   */
  public function testSoapUpdateMultipleRelationCommand() {
    // get contact #1
    $r_params = array( 'filter' =>
      array(
        "Field"     => "Transferred",
        "Criteria"  => "0",
      ),
      'setSize' => '2',
    );
    $readCommand = new CRM_Nav_SoapCommand_ReadMultiple($r_params);
    try{
      $this->soapConnectorRelationship->executeCommand($readCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("ReadMultiple CiviContRelation Command failed. Message: " . $e->getMessage());
    }
    $read_result = json_decode(json_encode($readCommand->getSoapResult()), TRUE);
    foreach ($read_result['ReadMultiple_Result']['civiContRelation'] as &$read_entry) {
      $read_entry['Transferred'] = 1;
    }
    $update_params['civiContRelation_List'] = $read_result['ReadMultiple_Result'];

    $testUpdateMultipleCommand = new CRM_Nav_SoapCommand_UpdateMultiple($update_params);
    try{
      $this->soapConnectorRelationship->executeCommand($testUpdateMultipleCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("UpdateMultiple CiviContRelation Command failed. Message: " . $e->getMessage());
    }

    print "UpdateMultiple Relation API call successful! \n";
    print_r($testUpdateMultipleCommand->getSoapResult());
  }

  /**
   * test the Navision API UpdateMultiple Process Command
   */
  public function testSoapUpdateMultipleProcessCommand() {
    // get contact #1
    $r_params = array( 'filter' =>
      array(
        "Field"     => "Transferred",
        "Criteria"  => "0",
      ),
      'setSize' => '2',
    );
    $readCommand = new CRM_Nav_SoapCommand_ReadMultiple($r_params);
    try{
      $this->soapConnectorContactProcess->executeCommand($readCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("ReadMultiple civiProcess Command failed. Message: " . $e->getMessage());
    }
    $read_result = json_decode(json_encode($readCommand->getSoapResult()), TRUE);
    foreach ($read_result['ReadMultiple_Result']['civiProcess'] as &$read_entry) {
      $read_entry['Transferred'] = 1;
    }
    $update_params['civiProcess_List'] = $read_result['ReadMultiple_Result'];

    $testUpdateMultipleCommand = new CRM_Nav_SoapCommand_UpdateMultiple($update_params);
    try{
      $this->soapConnectorContactProcess->executeCommand($testUpdateMultipleCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("UpdateMultiple civiProcess Command failed. Message: " . $e->getMessage());
    }

    print "UpdateMultiple Process API call successful! \n";
    print_r($testUpdateMultipleCommand->getSoapResult());
  }

  /**
   * test the Navision API UpdateMultiple Status Command
   */
  public function testSoapUpdateMultipleStatusCommand() {
    // get contact #1
    $r_params = array( 'filter' =>
      array(
        "Field"     => "Transferred",
        "Criteria"  => "0",
      ),
      'setSize' => '2',
    );
    $readCommand = new CRM_Nav_SoapCommand_ReadMultiple($r_params);
    try{
      $this->soapConnectorContactStatus->executeCommand($readCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("ReadMultiple civiContStatus Command failed. Message: " . $e->getMessage());
    }
    $read_result = json_decode(json_encode($readCommand->getSoapResult()), TRUE);
    foreach ($read_result['ReadMultiple_Result']['civiContStatus'] as &$read_entry) {
      $read_entry['Transferred'] = 1;
    }
    $update_params['civiContStatus_List'] = $read_result['ReadMultiple_Result'];

    $testUpdateMultipleCommand = new CRM_Nav_SoapCommand_UpdateMultiple($update_params);
    try{
      $this->soapConnectorContactStatus->executeCommand($testUpdateMultipleCommand);
    } catch (Exception $e) {
      error_log($e->getMessage());
      throw new Exception("UpdateMultiple civiContStatus Command failed. Message: " . $e->getMessage());
    }

    print "UpdateMultiple Status Status API call successful! \n";
    print_r($testUpdateMultipleCommand->getSoapResult());
  }

}
