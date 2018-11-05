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

class CRM_Nav_ChangeTracker_LogAnalyzer {

  private $_entities = ['contact', 'address', 'relationship', 'phone', 'email', 'website'];
  private $_timestamp;

  public function __construct() {
    // we always check for yesterday
    $this->_timestamp = date('Y-m-d', strtotime("-2 days"));
  }

  public function process() {
    foreach ($this->_entities as $entity) {
      $logging_data = $this->get_logging_data($entity);
    }
  }

  private function check_civi_data(&$logging_data) {

  }

  private function get_logging_data($entity) {
    $sql = "select contact_id FROM log_civicrm_{$entity} WHERE log_date > '{$this->_timestamp}'";
//    $sql = "SELECT id FROM log_civicrm_{$entity}";
    $query = CRM_Core_DAO::executeQuery($sql);
    while($query->fetch()) {
      $result[] = $query->id();
    }
    return $result;

  }




}