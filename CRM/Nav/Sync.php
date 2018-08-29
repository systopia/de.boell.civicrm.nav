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

  public function __construct($size, $debug = FALSE, $entity = NULL) {
    $this->entity = $entity;
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
    $this->sort_records();
    $this->handle_Nav_data();
    $this->mark_records_transferred();
    // FixMe: return actual number of parsed/added records or some sort of statistics here.
    //        For now we just return the number of records
    return $this->number_of_records;
  }

  /**
   * @throws \Exception
   */
  private function initialize_soap_connectors() {
    if (empty($this->entity)) {
      $this->entity = array ('civiContact', 'civiProcess', 'civiContRelation', 'civiContStatus');
    }
    foreach ($this->entity as $nav_entity) {
      $this->soap_connectors[$nav_entity] = new CRM_Nav_SOAPConnector($nav_entity, $this->debug);
    }
  }

  /**
   * Executes Handlers for all data records
   * @throws \Exception
   */
  private function handle_Nav_data() {
    foreach ($this->data_records as $timestamp => $record) {
      try {
        $contact_handler      = new CRM_Nav_Handler_ContactHandler($record);
        $process_handler      = new CRM_Nav_Handler_ProcessHandler($record);
        $status_handler       = new CRM_Nav_Handler_StatusHandler($record);
        $relationship_handler = new CRM_Nav_Handler_RelationshipHandler($record);

        $contact_handler->process();
        $process_handler->process();
        $status_handler->process();
        $relationship_handler->process();
      } catch (Exception $e) {
        throw new Exception ("Couldn't handle Record with timestamp {$timestamp} of type {$record->get_type()}");
      }
    }
  }

  /**
   *  // TODO filter consumed records for each entity, and mark them as transferred after (before AND after)
   */
  private function mark_records_transferred() {
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
   * get data from navision and create records
   * @throws \Exception
   */
  private function get_nav_data() {
    $filter = $this->get_soap_filter();
    $read_command = new CRM_Nav_SoapCommand_ReadMultiple($filter);
    foreach ($this->soap_connectors as $entity => $soap_connector) {
      try {
        $soap_connector->executeCommand($read_command);
      } catch (Exception $e) {
        throw new Exception ("SOAP Command failed for Entityt {$entity}");
      }
      $read_result = json_decode(json_encode($read_command->getSoapResult()), TRUE);
      // temporary var to save before variable in case of a change record
      $before = array();
      foreach ($read_result['ReadMultiple_Result'][$entity] as $nav_entry) {
        // if type is cahnge and we have a before value
        // store and create record with AFTER value next
        if ($nav_entry['Change_Type'] == 'Change' && $nav_entry['Version'] == 'BEFORE') {
          $before = array($nav_entry['_TIMESTAMP'] => $nav_entry);
          continue;
        } else {
          if (!empty($before)) {
            // create record with before value as well
            $record = $this->create_nav_data_record($nav_entry, $entity, reset($before));
            $before = array();
          } else {
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
   * @param      $data
   * @param      $entity
   * @param null $before
   *
   * @return \CRM_Nav_Data_NavContact|\CRM_Nav_Data_NavProcess|\CRM_Nav_Data_NavRelationship|\CRM_Nav_Data_NavStatus
   * @throws \Exception
   */
  private function create_nav_data_record($data, $entity, $before = NULL) {
    switch ($entity) {
      case 'civiContact':
        return new CRM_Nav_Data_NavContactRecord($data, $before);
      case 'civiProcess':
        return new CRM_Nav_Data_NavProcessRecord($data, $before);
      case 'civiContRelation':
        return new CRM_Nav_Data_NavRelationshipRecord($data, $before);
      case 'civiContStatus':
        return new CRM_Nav_Data_NavStatusRecord($data, $before);
      default:
        throw new Exception("Invalid Navision Entity Type {$entity}. Couldn't create DataRecord.");
    }
  }

  private function get_soap_filter() {
    return array( 'filter' =>
                    array(
                      "Field"     => "Transferred",
                      "Criteria"  => "0",
                    ),
                  'setSize' => $this->size,
    );
  }

}