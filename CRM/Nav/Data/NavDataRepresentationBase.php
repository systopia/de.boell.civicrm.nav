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

abstract class CRM_Nav_Data_NavDataRepresentationBase {

  /**
   * @var $nav_data data model for navision data
   */
  protected $nav_data;
  protected $nav_data_model;

  /**
   * CRM_Nav_Data_NavDataRepresentationBase constructor.
   *
   * @param $navision_data
   *
   * @throws \Exception if inherited class doesn't set $nav_data
   */
  public function __construct($navision_data) {
    if (!isset($this->nav_data)) {
      throw new Exception("Runtime Error - No Data Model defined. Please define a Data Structure for \$nav_data in classes inheriting from CRM_Nav_Data_NavDataRepresentationBase");
    }
    $this->set_navision_data_model();
    if ($this->verify_data($navision_data)) {
      $this->nav_data = $navision_data;
    }
  }

  /**
   * set the datamodel
   */
  protected  abstract function set_navision_data_model();

  /**
   * Verifies data against $this->nav_data
   * @return mixed
   */
  protected abstract function verify_data($navision_data);

  /**
   * - Verifies the incoming data ($this->verify_data)
   * - Compares the data with array_diff
   * - returns array with different data
   * @param $data
   *
   * @return mixed
   */
  protected abstract function compare_data($other_nav_data);

  /**
   * @return mixed
   */
  public function getNavData() {
    return $this->nav_data;
  }

}

