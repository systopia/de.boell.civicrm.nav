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

class CRM_Nav_ChangeTracker_WebsiteAnalyzer extends CRM_Nav_ChangeTracker_AnalyzerBase {

  public function __construct($timestamp, $debug) {
    $this->_select_fields = ['id', 'contact_id'];
    $this->type = 'Email';
    $this->_log_table = 'log_civicrm_website';
    parent::__construct($timestamp, $debug);
  }

  protected function get_my_class_name() {
    return get_class();
  }


  protected function eval_query(&$query) {
    if (!isset($this->_record_ids[$query->id])) {
      if ($this->is_nav_contact($query->contact_id)) {
        $this->_record_ids[$query->id] = $query->contact_id;
        CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$query->contact_id] = FALSE;
      }
    }
  }

}
