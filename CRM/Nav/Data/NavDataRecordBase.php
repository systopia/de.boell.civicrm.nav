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

abstract class CRM_Nav_Data_NavDataRecordBase {

  private $nav_data_before;
  // in case of a new record, after is used as the ONLY record type internally
  private $nav_data_after;
  private $consumed;

  // overwritten by subclasses
  protected $type;
  protected $change_type;

  protected $timestamp;
  protected $civi_data_after;
  protected $civi_data_before;
  protected $changed_data;

  protected $debug;
  protected $navision_custom_field;

  /**
   * CRM_Nav_Data_NavDataRepresentationBase constructor.
   *
   * @param $navision_data
   *
   * @throws \Exception if data is not valid
   */
  public function __construct($nav_data_after, $nav_data_before = NULL) {
    $this->navision_custom_field = CRM_Nav_Config::get('navision_custom_field');
    $this->nav_data_before = $nav_data_before;
    $this->nav_data_after = $nav_data_after;
    $this->consumed = FALSE;
    $this->set_timestamp();
    $this->change_type = $this->get_nav_value_if_exist($this->nav_data_after, 'Change_Type');
    $this->compare_data();
    $this->convert_to_civi_data();

    // FixMe:
    $this->debug = TRUE; // for now always true
  }

  /**
   * Microsofot fills out date format when zero as '0001-01-01'. In CiviCRM this
   * is interpreted as 01/01/2001. Therfore we need to replace it with "" in the
   * values
   *
   * Fixed fields are 'start_date' and 'end_date'
   *
   * @param &$data
   */
  protected function fix_microsoft_dates(&$data) {
    if(isset($data['start_date'])) {
      if ($data['start_date'] == '0001-01-01') {
        $data['start_date'] = "";
      }
    }
    if(isset($data['end_date'])) {
      if ($data['end_date'] == '0001-01-01') {
        $data['end_date'] = "";
      }
    }
  }

  /**
   * @throws \Exception
   */
  private function set_timestamp() {
    if ($this->nav_data_after['Change_Type'] == 'New') {
      $this->timestamp = $this->nav_data_after['_TIMESTAMP'];
      return;
    }
    if (($this->nav_data_before['_TIMESTAMP'] != $this->nav_data_after['_TIMESTAMP'])){
      // FixME: Timestamps differ a bit, throw a warning for now, implement a threshold later
      $this->log("Timestamps of before and after don't match. '{$this->nav_data_before['_TIMESTAMP']}' != '{$this->nav_data_after['_TIMESTAMP']}'");
      //      throw new Exception("Timestamps of before and after don't match.");
    }
    $this->timestamp = $this->nav_data_before['_TIMESTAMP'];
  }

  /**
   * Compares before and after data, and saves changes in $changed_data
   */
  protected function compare_data() {
    $this->changed_data = array_diff($this->nav_data_after, $this->nav_data_before);
    // FixME: is this needed?
    unset($this->changed_data['Key']);
  }

  abstract protected function convert_to_civi_data();

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
    // TODO: set Transferred flag = 1
    $this->nav_data_before['Transferred'] = 1;
    if (isset($this->nav_data_before)) {
      $this->nav_data_before['Transferred'] =1;
    }
  }

  public function get_nav_after_data(){
    return $this->nav_data_after;
  }

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
      return $nav_data[$index];
    }
    $this->log("Value not set for {$index}");
    return "";
  }

  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

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
    CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] Dumping Record");
    $dump['timestamp'] = $this->timestamp;
    $dump['nav_before'] = $this->nav_data_before;
    $dump['civi_extra_data']  = $this->civi_data_after;
    $dump['changed_data']  = $this->changed_data;
    CRM_Core_Error::debug_log_message(json_encode($dump));
  }

  public function get_type() {
    return $this->type;
  }

  public function get_change_type() {
    return $this->change_type;
  }

}

