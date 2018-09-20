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

abstract class CRM_Nav_Handler_HandlerBase {

  protected $record;
  protected $hbs_contact_id = "4";
  private $debug;
// local
  protected $navision_custom_field = 'custom_41';
  // hbs
//  protected $navision_custom_field = 'custom_147';

  public function __construct($record) {
    // Fixme: make configurable, probably extension wide config
    $this->debug = TRUE;
    $this->record = $record;
  }

  abstract protected function check_record_type();

  protected function get_contact_id_from_nav_id($navId) {
    $this->log("PBADEBUG-NavID: " . $navId);
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      $this->navision_custom_field => $navId,
    ));
    if ($result['count'] != 1) {
      return "";
    }
    $this->log("PBADEBUG: " . json_encode($result));
    return $result['id'];
  }

  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  protected function check_delete_record() {
    return $this->record->get_change_type() == 'Delete';
  }

  protected function check_new_record() {
    return $this->record->get_change_type() == 'New';
  }

  /**
   * @param $entity_id
   * @param $entity
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function delete_entity($entity_id, $entity) {
    $result = civicrm_api3($entity, 'delete', array(
      'sequential' => 1,
      'id' => $entity_id,
    ));
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't delete {$entity} with Id {$entity_id}");
    }
  }

  protected function disable_relationship($relationship_id) {
    $result = civicrm_api3('Relationship', 'create', array(
      'sequential' => 1,
      'id' => $relationship_id,  // Relationship ID
      'is_active' => 0,
      'description' => "deactivated by Navision Interface",
    ));
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't disable relationshipId {$relationship_id}. Message {$result['error_message']}");
    }
  }

  protected function get_civi_relationship_id($contact_a, $contact_b, $type = NULL, $type_value = NULL) {
    $values = [
      'contact_id_a' => $contact_a,
      'contact_id_b' => $contact_b,
    ];
    if (isset($type)) {
      $values[$type] = $type_value;
    }
    $result = civicrm_api3('Relationship', 'get', $values);
    if ($result['is_error'] == '1' || $result['count'] != '1') {
      return "";
    }
    return $result['id'];
  }

  protected function get_entity_id($values, $contact_id, $entity) {
    if (empty($values)) {
      // nothing to do here, but no error either. values need to be added/filled up
      return "";
    }
    $values['contact_id'] = $contact_id;
    $result = civicrm_api3($entity, 'get', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Error occured while getting {$entity}-Id for Contact {$contact_id} with values " . json_encode($values) . ". Error Message: {$result['error_message']}");
    }
    if ($result['count'] >'1') {
      throw new Exception("Couldn't get {$entity}-Id for Contact {$contact_id} with values " . json_encode($values));
    }
    if ($result['count'] == '0') {
      return "";
    }
    return $result['id'];
  }

  protected function create_civi_entity($values, $entity) {
    $result = civicrm_api3($entity, 'create', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't create Civi Entity {$entity}. Error Message: " . $result['error_message']. ". Values: " . json_encode($values));
    }
    return $result['id'];
  }

  abstract public function process();
}