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
  private $nav_data_after;
  private $consumed;

  // overwritten by subclasses
  protected $type;

  protected $timestamp;
  protected $civi_data_mapping;
  protected $civi_data;
  protected $civi_extra_data;
  protected $changed_data;

  /**
   * CRM_Nav_Data_NavDataRepresentationBase constructor.
   *
   * @param $navision_data
   *
   * @throws \Exception if data is not valid
   */
  public function __construct($nav_data_after, $nav_data_before = NULL) {
    $this->nav_data_before = $nav_data_before;
    $this->nav_data_after = $nav_data_after;
    $this->consumed = FALSE;
    $this->set_timestamp();
    $this->compare_data();
    $this->set_navision_data_model("{$this->type}.json");
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
      throw new Exception("Timestamps of before and after don't match.");
    }
    $this->timestamp = $this->nav_data_before['_TIMESTAMP'];
  }

  /**
   * Compares before and after data, and saves changes in $changed_data
   */
  protected function compare_data() {
    $this->changed_data = array_diff($this->nav_data_before, $this->nav_data_after);
  }

  protected function set_navision_data_model($file_name) {
    $nav_contact_file = "resources/dataModel/{$file_name}";
    $file_content = file_get_contents($nav_contact_file);
    $this->civi_data_mapping = json_decode($file_content, TRUE);
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
  }

  protected function get_nav_after_data(){
    return $this->nav_data_after;
  }

  protected function get_nav_before_data() {
    return $this->nav_data_before;
  }

}

