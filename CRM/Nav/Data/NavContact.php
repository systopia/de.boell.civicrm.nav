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


class CRM_Nav_Data_NavContact extends CRM_Nav_Data_NavDataRepresentationBase {

  public function __construct($navision_data) {
    parent::__construct($navision_data);
  }

  /**
   * set $this->datamodel
   *
   * TODO: data fields from Test DB - might need an update
   */
  protected function set_navision_data_model() {
    $nav_contact_file = "resources/dataModel/navContact.json";
    $file_content = explode("\n", file_get_contents($nav_contact_file));
    $this->nav_data_model = json_decode(array_pop(array_reverse($file_content)), TRUE);
  }

  /**
   * Verifies data against $this->nav_data
   * @return mixed
   */
  protected function verify_data($navision_data) {
    foreach ($navision_data as $key => $val) {
      if (!in_array($key, $this->nav_data_model)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * - Verifies the incoming data ($this->verify_data)
   * - Compares the data with array_diff
   * - returns array with different data
   * @param $data
   *
   * @return mixed
   */
  protected function compare_data($other_nav_data) {
    // TODO: Compare Objects
    // return array with data (key->val) from $other_nav_data that is different
    // than in  this object
  }
}