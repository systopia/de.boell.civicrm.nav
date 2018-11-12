<?php
use CRM_Nav_ExtensionUtil as E;

/**
 * Nav.LookUpIdTracker API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nav_Lookupidtracker_spec(&$spec) {
  $spec['navision_id']['api.required'] = 1;
}

/**
 * Nav.LookUpIdTracker API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nav_Lookupidtracker($params) {
  $id_table = CRM_Nav_Config::get('id_table');
  $sql = "SELECT * FROM `{$id_table}` WHERE `identifier_type` = 'navision' AND `identifier` = '{$params['navision_id']}' GROUP BY id DESC LIMIT 0, 1;";
  $query = CRM_Core_DAO::executeQuery($sql);
  $returnValues = [];
  while($query->fetch()) {
    $returnValues['contact_id'] = $query->entity_id;
    return civicrm_api3_create_success($returnValues, $params, 'Nav', 'LookUpIdTracker');
  }
  return civicrm_api3_create_success($returnValues, $params, 'Nav', 'LookUpIdTracker');
}
