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
  protected $_log_table;
  protected $type;

  // format: id -> value array
  // last value before timestamp
  protected $last_before_values;
  // last value after timestamp
  protected $last_after_values;

  protected $changed_values;

  public function __construct($timestamp, $debug = FALSE) {
    $this->_timestamp = $timestamp;
    $this->debug = $debug;
    $this->error_counter = 0;
    $this->_record_ids = [];
    $this->changed_values = [];

    if (!isset($this->_select_fields) || !isset($this->_log_table) || !isset($this->type)) {
      $class_name = get_called_class();
      throw new Exception("Invalid Analyzer initialization. Aborting Log Runner for {$class_name}");
    }
  }


  public function run() {
    $this->get_changed_navision_contacts($this->_select_fields);
    $this->parse_log_data_before();
    $this->parse_log_data_after();
    $this->eval_data();
  }

  public function get_changed_data() {
    return $this->changed_values;
  }

  protected function is_nav_contact($contact_id) {
    if (isset(CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id])) {
      return TRUE;
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
  protected function get_changed_navision_contacts($fields) {
    $select_fields = implode(",", $fields);
    $sql = "select {$select_fields} FROM {$this->_log_table} WHERE log_date > '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);
    while($query->fetch()) {
      $this->eval_query($query);
    }
  }

  protected function parse_log_data_before() {
    $sql = "select * FROM {$this->_log_table} WHERE log_date <= '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);

    while($query->fetch()) {
      if (!isset($this->_record_ids[$query->id])) {
        continue;
      }
      $this->last_before_values[$query->id] = $query->toArray();
    }
  }

  protected function parse_log_data_after() {
    $sql = "select * FROM {$this->_log_table} WHERE log_date > '{$this->_timestamp}'";
    $query = CRM_Core_DAO::executeQuery($sql);

    while($query->fetch()) {
      if (!isset($this->_record_ids[$query->id])) {
        continue;
      }
      $this->last_after_values[$query->id] = $query->toArray();
    }
  }

  protected function log($message)  {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav - {$this->get_my_class_name()}] " . $message);
    }
  }

  /**
   * Evaluates changes between before log entries and after log Entries.
   * Results are stored in $this->changed_values
   * Format:
   * $this->changed_values[CONTACT_ID => [FIELD_NAME => [['new' => NEW_VALUE], ['old' => OLD_VALUE]]]
   */
  protected function eval_data() {
    //    iterate over all after values; $key = EntityId, Value = value array
    foreach ($this->last_after_values as $key => $value) {
      // if no before value exists: New entry --> log all after values and skip rest
      if (!isset($this->last_before_values[$key])) {
        // new contact creation with Nav Id, we have to provide the whole thing
        foreach ($value as $k => $v) {
          $this->changed_values[$key][$k]['new'] = $v;
        }
        continue;
      }
      // Value array: $k => $v
      foreach ($value as $k => $v) {
        if ($v != $this->last_before_values[$key][$k] || in_array($k, CRM_Nav_Config::$always_log_fields)) {
          // resulting array should be keyed by contact id ( mapping via $this->_record_ids[$key])
          $this->changed_values[$this->_record_ids[$key]][$k]['new'] = $v;
          $this->changed_values[$this->_record_ids[$key]][$k]['old'] = $this->last_before_values[$key][$k];
        }
      }
    }
  }

  abstract protected function get_my_class_name();

  abstract protected function eval_query(&$query);

  public function get_entity_type() {
    return $this->type;
  }
}
