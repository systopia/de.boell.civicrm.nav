<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2021 SYSTOPIA                            |
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
 * Class CRM_Nav_Sync
 */
class CRM_Nav_Utils {

  /**
   * @param $entity
   * @param $action
   * @param $params
   * @throws CiviCRM_API3_Exception
   */
  public static function civicrm_nav_api($entity, $action, $params) {
    CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] API Params for {$entity}.{$action}: " . json_encode($params));

    // parameter validation
    foreach ($params as $key => &$value) {
      if (empty($value)) {
        unset($params[$key]);
        // debugging output
        CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] Cleaning up API Params for {$entity}.{$action} - {$key}");
      }
    }
    if (empty($params)) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] Empty Parameter array");
      throw new CRM_Nav_Exceptions_EmptyApiParameterArray("Empty Parameter array");
    }
    // call civicrm_api
    return civicrm_api3($entity,$action, $params);
  }


}