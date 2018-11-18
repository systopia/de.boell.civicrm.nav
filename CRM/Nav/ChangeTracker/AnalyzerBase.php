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

abstract class CRM_Nav_ChangeTracker_AnalyzerBase {

  protected $_record_ids;
  protected $_timestamp;

  protected $debug;
  protected $error_counter;

  protected $_select_fields;
  protected $_lookupfields;
  protected $type;

  // format: id -> value array
  // last value before timestamp
  protected $last_before_values;
  // last value after timestamp
  protected $last_after_values;

  public function __construct($timestamp, $debug = FALSE) {
    $this->_timestamp = $timestamp;
    $this->debug = $debug;
    $this->error_counter = 0;
    $this->_record_ids = [];

    if (!isset($this->_select_fields) || !isset($this->_lookupfields) || !isset($this->type)) {
      $class_name = get_called_class();
      throw new Exception("Invalid Analyzer initialization. Aborting Log Runner for {$class_name}");
    }
  }


  public function run() {
    $this->get_changed_navision_contacts(strtolower($this->type), $this->_select_fields);
    $this->parse_log_data_before(strtolower($this->type));
    $this->parse_log_data_after(strtolower($this->type));
  }

  private function is_nav_contact($contact_id) {
    if (isset(CRM_Nav_ChangeTracker_LogAnalyzRunner::$nav_id_cache[$contact_id])) {
      return CRM_Nav_ChangeTracker_LogAnalyzRunner::$nav_id_cache[$contact_id];
    }
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => array(CRM_Nav_Config::get('navision_custom_field')),
      'id' => $contact_id,
    ));
    if ($result['count'] != '1' || $result['is_error'] == '1') {
      $this->log("[ERROR] Didn't find Contact for Contact ID in {$contact_id}.");
      $this->error_counter += 1;
      return FALSE;
    }
    if (!empty($result['values']['0'][CRM_Nav_Config::get('navision_custom_field')])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $entity
   * @param $fields
   * @param $contact_lookup_field
   *
   * @return array
   */
  protected function get_changed_navision_contacts($entity, $fields) {
    $select_fields = implode(",", $fields);
    $sql = "select {$select_fields} FROM log_civicrm_{$entity} WHERE log_date > '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);
    while($query->fetch()) {
      if (!isset($this->_record_ids[$query->id])) {
        if ($this->is_nav_contact($query->id)) {
          $this->_record_ids[$query->id] = $query->id;
          CRM_Nav_ChangeTracker_LogAnalyzRunner::$nav_id_cache[$query->id] = $query->id;
        }
      }
    }
  }

  protected function parse_log_data_before($entity) {
    $sql = "select * FROM log_civicrm_{$entity} WHERE log_date <= '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);

    while($query->fetch()) {
      if (!isset($this->_record_ids[$query->id])) {
        continue;
      }
      $values = [];
      foreach ($this->get_table_descriptions() as $field_name) {
        $values[$field_name] = $query->{$field_name};
      }
      $this->last_before_values[$query->id] = $values;
    }
  }

  protected function parse_log_data_after($entity) {
    $sql = "select * FROM log_civicrm_{$entity} WHERE log_date > '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);

    while($query->fetch()) {
      if (!isset($this->_record_ids[$query->id])) {
        continue;
      }
      $values = [];
      foreach ($this->get_table_descriptions() as $field_name) {
        $values[$field_name] = $query->{$field_name};
      }
      $this->last_after_values[$query->id] = $values;
    }
  }

  protected function log($message)  {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav - {$this->get_my_class_name()}] " . $message);
    }
  }

  abstract protected function get_my_class_name();

  abstract protected function eval_data();

  abstract protected function get_table_descriptions();
}