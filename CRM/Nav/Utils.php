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
 * Class CRM_Nav_Utils
 */
class CRM_Nav_Utils {

  /**
   * Api Wrapper to validate API Arguments. Initially this extension was created for CiviCRM 5.4, and an older 4.7.x version.
   * In newer versions, it is not possible to provide empty parameters to the API.
   * This function filters empty parameters and then call the API.
   * If Arguments afterwards are empty, CRM_Nav_Exceptions_EmptyApiParameterArray is thrown.
   *
   * @param $entity
   * @param $action
   * @param $params
   * @throws CiviCRM_API3_Exception|CRM_Nav_Exceptions_EmptyApiParameterArray
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