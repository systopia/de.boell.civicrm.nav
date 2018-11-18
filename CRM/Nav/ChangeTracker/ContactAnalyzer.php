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

class CRM_Nav_ChangeTracker_ContactAnalyzer extends CRM_Nav_ChangeTracker_AnalyzerBase {

  public function __construct($timestamp) {
    $this->_select_fields = ['id'];
    $this->_lookupfields  = ['id'];
    $this->type = 'Contact';
    parent::__construct($timestamp);
  }

  protected function get_my_class_name() {
    return get_class();
  }

  protected function eval_data() {
    // TODO: Implement eval_data() method.
    // Compare before dataset and after dataset, parse and save here in format:
    // contactId => [
    //    field_name => [
    //      old => old_val
    //      new => new_val
    //   ]
    // ]
  }

}