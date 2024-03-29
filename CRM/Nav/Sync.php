<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


/**
 * General class, called from api command
 */
class CRM_Nav_Sync {

  private $size;
  private $entity;
  private $debug;
  private $data_records;
  private $soap_connectors;
  private $number_of_records;

  private $local_debug;

  public function __construct($size, $debug = FALSE, $entity = NULL) {
    $this->local_debug = CRM_Nav_Config::local();
    if (empty($entity)) {
      $this->entity = array ('civiContact', 'civiProcess', 'civiContRelation', 'civiContStatus');
    } else {
      $this->entity = array($entity);
    }
    $this->size   = $size;
    $this->debug = $debug;
    $this->initialize_soap_connectors();
  }

  /**
   * Runner function
   * Starts soap call and migration to CiviCRM
   * @return mixed
   * @throws \Exception
   */
  public function run() {
    $this->get_nav_data();
    CRM_Core_Error::debug_log_message("Debug Data: " . json_encode($this->data_records));
    if (empty($this->data_records)) {
      return 0;
    }

    $this->sort_records();
    $this->handle_Nav_data();
    // FixMe: return actual number of parsed/added records or some sort of statistics here.
    //        For now we just return the number of records

    // for testing purposes don't set to transfered!
    $this->set_consumed_records_transferred('civiContact');
    $this->set_consumed_records_transferred('civiContRelation');
    $this->set_consumed_records_transferred('civiProcess');
    $this->set_consumed_records_transferred('civiContStatus');

    // log errors
    $this->cleanup_handling();

    return $this->number_of_records;
  }

  /**
   * @throws \Exception
   */
  private function initialize_soap_connectors() {
    foreach ($this->entity as $nav_entity) {
      $this->soap_connectors[$nav_entity] = new CRM_Nav_SOAPConnector($nav_entity, $this->debug);
    }
  }

  private function set_consumed_records_transferred($type){
    $navision_records  = $this->get_records($type);
    if (empty($navision_records)) {
      return;
    }
    foreach ($navision_records as $rec) {
      $soap_array["{$type}_List"][$type][] = $rec->get_nav_after_data();
      $tmp_nav_data = $rec->get_nav_before_data();
      if (isset($tmp_nav_data)) {
        $soap_array["{$type}_List"][$type][] = $tmp_nav_data;
      }
    }
    if (CRM_Nav_Config::local()) {
      return; // don't update entires for local testing
    }
    $updateMultipleCommand = new CRM_Nav_SoapCommand_UpdateMultiple($soap_array);
    $soapConnector = $this->soap_connectors[$type];
    if (!isset($soapConnector)) {
      return;
    }
    try{
      $soapConnector->executeCommand($updateMultipleCommand);
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] ERROR " . $e->getMessage());
      throw new Exception("UpdateMultiple Command failed, didn't set DataRecords for type {$type} to Transferred. Message: " . $e->getMessage());
    }
  }

  private function get_records($type) {
    foreach ($this->data_records as $record) {
      if ($record->get_type() == $type) {
        $result[] = $record;
      }
    }
    return $result;
  }

  /**
   * Executes Handlers for all data records
   * @throws \Exception
   */
  private function handle_Nav_data() {
    foreach ($this->data_records as $timestamp => $record) {
      $this->log("DEBUG Record: " . $record->get_summary('json'));
      try {
        $contact_handler      = new CRM_Nav_Handler_ContactHandler($record, $this->debug);
        $process_handler      = new CRM_Nav_Handler_ProcessHandler($record, $this->debug);
        $status_handler       = new CRM_Nav_Handler_StatusHandler($record, $this->debug);
        $relationship_handler = new CRM_Nav_Handler_RelationshipHandler($record, $this->debug);

        $contact_handler->process();
        $process_handler->process();
        $status_handler->process();
        $relationship_handler->process();
      } catch (Exception $e) {
        $exception_type = get_class($e);
        $this->log("Couldn't handle Record with timestamp {$timestamp} of type {$record->get_type()}. ({$exception_type}) Message: " . $e->getMessage());
        $record->set_error_message($e->getMessage());
      }
    }
  }

  /**
   * Sort Navision records based on their timestamp
   * @throws \Exception
   */
  private function sort_records() {
    if (!ksort($this->data_records)) {
      throw new Exception("Failed to sort records to Timestamps");
    }
    $this->number_of_records = count($this->data_records);
  }

  /**
   * debug function - uses manual json files to emulate SOAP call result
   */
  private function get_local_debug_data($entity) {
    $file_name = __DIR__ . "/../../resources/test_data/{$entity}.json";
    return json_decode(file_get_contents($file_name), TRUE);
  }

  /**
   * get data from navision and create records
   * @throws \Exception
   */
  private function get_nav_data() {
    $filter = $this->get_soap_filter();
    $read_command = new CRM_Nav_SoapCommand_ReadMultiple($filter);
    foreach ($this->soap_connectors as $entity => $soap_connector) {
      if ($this->local_debug) {
        $read_result = $this->get_local_debug_data($entity);
      } else {
        try {
          $soap_connector->executeCommand($read_command);
        } catch (Exception $e) {
          throw new Exception ("SOAP Command failed for Entity {$entity}. Error: {$e->getMessage()}");
        }
        $read_result = json_decode(json_encode($read_command->getSoapResult()), TRUE);
      }
        // temporary var to save before variable in case of a change record
      $before = [];
      // if we only have ONE Entry, need to parse differently here
      // there is no 'sub-array' with entries, instead all values are directly in
      // $read_result['ReadMultiple_Result'][$entity]
      if (!is_array(reset($read_result['ReadMultiple_Result'][$entity]))) {
        $single_entry = $read_result['ReadMultiple_Result'][$entity];
        if (empty($single_entry)) {
          continue;
        }
        $record = $this->create_nav_data_record($single_entry, $entity);
        $this->data_records[$single_entry['_TIMESTAMP']] = $record;
        continue;
      }
      foreach ($read_result['ReadMultiple_Result'][$entity] as $nav_entry) {
        // if type is change and we have a before value
        // store and create record with AFTER value next
        // FixMe:
        if ($nav_entry['Change_Type'] == 'Change' && $nav_entry['Version'] == 'BEFORE') {
          $before = [$nav_entry['_TIMESTAMP'] => $nav_entry];
          continue;
        } else {
          if (!empty($before)) {
            // create record with before value as well
            $record = $this->create_nav_data_record($nav_entry, $entity, reset($before));
            $before = [];
          }
          else {
            // TODO: verify/compare timestamps for both records??
            $record = $this->create_nav_data_record($nav_entry, $entity);
          }
          $this->data_records[$nav_entry['_TIMESTAMP']] = $record;
        }
      }
    }
  }

  /**
   *
   * create DataRecord for specified Navision Entity
   *
   * @param      $data
   * @param      $entity
   * @param null $before
   *
   * @return \CRM_Nav_Data_NavContact|\CRM_Nav_Data_NavContactRecord|\CRM_Nav_Data_NavProcess|\CRM_Nav_Data_NavProcessRecord|\CRM_Nav_Data_NavRelationship|\CRM_Nav_Data_NavRelationshipRecord|\CRM_Nav_Data_NavStatus|\CRM_Nav_Data_NavStatusRecord
   * @throws \Exception
   */
  private function create_nav_data_record($data, $entity, $before = NULL) {
    switch ($entity) {
      case 'civiContact':
        $record = new CRM_Nav_Data_NavContactRecord($data, $before, $this->debug);
        break;
      case 'civiProcess':
        $record =  new CRM_Nav_Data_NavProcessRecord($data, $before, $this->debug);
        break;
      case 'civiContRelation':
        $record =  new CRM_Nav_Data_NavRelationshipRecord($data, $before, $this->debug);
        break;
      case 'civiContStatus':
        $record =  new CRM_Nav_Data_NavStatusRecord($data, $before, $this->debug);
        break;
      default:
        throw new Exception("Invalid Navision Entity Type {$entity}. Couldn't create DataRecord.");
    }
    try{
      $record->convert_to_civi_data();
    } catch (Exception $e) {
      $record->set_error_message($e->getMessage());
    }
    return $record;
  }

  /**
   * @return array
   */
  private function get_soap_filter() {
    return ['filter' =>
                    [
                      "Field"     => "Transferred",
                      "Criteria"  => "0",
                    ],
                  'setSize' => $this->size,
    ];
  }

  /**
   * @param $message
   */
  private function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  private function cleanup_handling() {
    foreach ($this->entity as $entity) {
      foreach ($this->get_records($entity) as $record) {
        $error_handler  = new CRM_Nav_Handler_ErrorHandler($record, $this->debug);
        $error_handler->process();
      }
    }
  }

}