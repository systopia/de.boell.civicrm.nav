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
 * Class CRM_Nav_Data_NavDataRecordBase
 */
abstract class CRM_Nav_Data_NavDataRecordBase {

  private $nav_data_before;
  // in case of a new record, after is used as the ONLY record type internally
  private $nav_data_after;
  private $consumed;

  private $error_message;

  // overwritten by subclasses
  protected $type;
  protected $change_type;

  protected $timestamp;
  protected $civi_data_after;
  protected $civi_data_before;
  protected $changed_data;
  protected $delete_data;

  protected $debug;
  protected $navision_custom_field;

  /**
   * CRM_Nav_Data_NavDataRepresentationBase constructor.
   *
   * @param $navision_data
   *
   * @throws \Exception if data is not valid
   */
  public function __construct($nav_data_after, $nav_data_before = NULL, $debug = FALSE) {
    $this->navision_custom_field = CRM_Nav_Config::get('navision_custom_field');
    $this->nav_data_before = $nav_data_before;
    $this->nav_data_after = $nav_data_after;
    $this->consumed = FALSE;
    $this->set_timestamp();
    $this->change_type = $this->get_nav_value_if_exist($this->nav_data_after, 'Change_Type');
    $this->compare_data();
//    $this->convert_to_civi_data();
    $this->debug = $debug;
  }

  /**
   * set_timestamp
   */
  private function set_timestamp() {
    if ($this->nav_data_after['Change_Type'] == 'New') {
      $this->timestamp = $this->nav_data_after['_TIMESTAMP'];
      return;
    }
    if (($this->nav_data_before['_TIMESTAMP'] != $this->nav_data_after['_TIMESTAMP'])){
      // FixME: Timestamps differ a bit, throw a warning for now, implement a threshold later
      $this->log("Timestamps of before and after don't match. '{$this->nav_data_before['_TIMESTAMP']}' != '{$this->nav_data_after['_TIMESTAMP']}'");
    }
    $this->timestamp = $this->nav_data_before['_TIMESTAMP'];
  }

  /**
   * Compares before and after data, and saves changes in $changed_data
   */
  protected function compare_data() {
    $this->changed_data = $this->check_changed_data($this->nav_data_before, $this->nav_data_after);
    $this->delete_data  = $this->check_delete_data($this->nav_data_before, $this->nav_data_after);
//    // FixME: is this needed?
//    unset($this->changed_data['Key']);
  }

  /**
   * Compare after to before data,
   * checks if key exists in before, and if value is different
   * @param $before
   * @param $after
   *
   * @return array
   */
  private function check_changed_data($before, $after) {
    $changed_data = [];
    foreach ($after as $key => $value) {
      if (!isset($before[$key])) {
        $changed_data[$key] = $value;
        continue;
      }
      if ($value != $before[$key]) {
        $changed_data[$key] = $value;
      }
    }
    return $changed_data;
  }

  /**
   * Checks for elements in before that aren't available in after.
   * @param $before
   * @param $after
   *
   * @return array
   */
  private function check_delete_data($before, $after) {
    $delete_data = [];
    if (empty($before)) {
      return $delete_data;
    }
    foreach ($before as $key => $value) {
      if (!isset($after[$key])) {
        $delete_data[$key] = $value;
      }
    }
    return $delete_data;
  }

  /**
   * Checks if the record is consumed
   * @return mixed
   */
    public function is_consumed() {
    return $this->consumed;
  }

  /**
   * Sets the Record to consumed
   */
  public function set_consumed(){
    $this->consumed = TRUE;
    $this->nav_data_after['Transferred'] = 1;
    if (isset($this->nav_data_before)) {
      $this->nav_data_before['Transferred'] = 1;
    }
  }

  /**
   * @return mixed
   */
  public function get_nav_after_data(){
    return $this->nav_data_after;
  }

  /**
   * @return mixed
   */
  public function get_nav_before_data() {
    return $this->nav_data_before;
  }

  /**
   * get value from the provided array. Returns "" if no value is set (check via isset())
   * @param $nav_data
   * @param $index
   *
   * @return string
   */
  protected function get_nav_value_if_exist(&$nav_data, $index) {
    if (isset($nav_data[$index])) {
      // Fix MS Date; 0001-01-01 --> means empty/zero date
      if (in_array($nav_data[$index], CRM_Nav_Config::$filter)) {
        return "";
      }
      // remove possible leading zeroes in option values (civiProcess)
      // TODO: this necessary for all values, or maybe filter for just the option group values?
      if ($this->type == 'civiProcess') {
        return ltrim($nav_data[$index], '0');
      }
      return $nav_data[$index];
    }
//    $this->log("Value not set for {$index}");
    return "";
  }

  /**
   * @param $message
   */
  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  /**
   * @return mixed
   * @throws \Exception
   */
  public function get_individual_navision_id() {
    if (!empty($this->nav_data_after['Contact_No'])) {
      return $this->nav_data_after['Contact_No'];
    } else if (!empty($this->nav_data_after['No']) && $this->get_type() == "civiContact") {
      return $this->nav_data_after['No'];
     } else {
      $this->log("Couldn't determine Navision Id. Aborting.");
      throw new Exception("Couldn't determine Navision Id. Aborting Process.");
    }
  }

  public function dump_record() {
    $dump['timestamp'] = $this->timestamp;
    $dump['nav_before'] = $this->nav_data_before;
    $dump['civi_extra_data']  = $this->civi_data_after;
    $dump['changed_data']  = $this->changed_data;
    CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] Record: ". json_encode($dump));
  }

  /**
   * @return mixed
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * @return string
   */
  public function get_change_type() {
    return $this->change_type;
  }

  public function set_error_message($message) {
    $this->error_message = $message;
    $this->set_consumed();
  }

  /**
   * @return mixed
   */
  public function get_error_message() {
    return $this->error_message;
  }

  /**
   * @param string $type
   *
   * @return false|string
   */
  public function get_summary($type = 'array') {
    $dump['timestamp'] = $this->timestamp;
    $dump['nav_before'] = $this->nav_data_before;
    $dump['civi_extra_data']  = $this->civi_data_after;
    $dump['changed_data']  = $this->changed_data;
    switch ($type) {
      case 'json':
        return json_encode($dump);
      case 'array':
        return $dump;
      default:
      return $dump;
    }
  }

  abstract public function convert_to_civi_data();
}
