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
 * Class CRM_Nav_ChangeTracker_PhoneAnalyzer
 */
class CRM_Nav_ChangeTracker_PhoneAnalyzer extends CRM_Nav_ChangeTracker_AnalyzerBase {

  /**
   * CRM_Nav_ChangeTracker_PhoneAnalyzer constructor.
   *
   * @param $timestamp
   * @param $debug
   *
   * @throws \Exception
   */
  public function __construct($timestamp, $debug) {
    $this->_select_fields = ['id', 'contact_id'];
    $this->type = 'Phone';
    $this->_log_table = 'log_civicrm_phone';
    parent::__construct($timestamp, $debug);
  }

  /**
   * @return mixed|string
   */
  protected function get_my_class_name() {
    return get_class();
  }


  /**
   * @param $query
   *
   * @return mixed|void
   * @throws \CiviCRM_API3_Exception
   */
  protected function eval_query(&$query) {
    if (!isset($this->_record_ids[$query->id])) {
      if ($this->is_nav_contact($query->contact_id)) {
        $this->_record_ids[$query->id] = $query->contact_id;
      }
    }
  }

}
