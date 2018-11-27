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
 * Class CRM_Nav_ErrorHandler
 */
class CRM_Nav_Handler_ErrorHandler extends CRM_Nav_Handler_HandlerBase {

  public function __construct($record, $debug = false) {
    parent::__construct($record, $debug);
  }

  /**
   * see #7616
   */
  public function process() {
    if (!$this->check_record_type()) {
      return;      // nothing to do here
    }
    CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] ERROR " . $this->record->get_error_message());
    if ($this->debug) {
      $this->record->dump_record();
    }
  }

  /**
   * @return bool
   */
  protected function check_record_type() {
    $error_message = $this->record->get_error_message();
    if (empty($error_message)) {
      return FALSE;
    }
    return TRUE;
  }
}