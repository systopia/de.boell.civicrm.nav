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


class CRM_Nav_Data_NavProcess extends CRM_Nav_Data_NavDataRecordBase {

  public function __construct($navision_data) {
    parent::__construct($navision_data);
  }

  /**
   * set $this->datamodel
   */
  protected function set_navision_data_model() {

  }

  /**
   * Verifies data against $this->nav_data
   * @return mixed
   */
  protected function verify_data() {

  }

  protected function compare_data(&$data) {

  }

  protected function convert_to_civi_data() {

  }
}