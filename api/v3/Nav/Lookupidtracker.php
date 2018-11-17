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
//  SELECT h.id, h.entity_id, h.identifier_type, h.identifier, c.is_deleted FROM `civicrm_value_contact_id_history` h LEFT JOIN civicrm_contact c on c.id = h.entity_id WHERE `identifier_type` = 'navision' AND `identifier` = 'K000777' AND c.is_deleted != '1' GROUP BY id DESC LIMIT 0, 1
  $sql = "SELECT h.id, h.entity_id, h.identifier_type, h.identifier, c.is_deleted FROM `{$id_table}` h LEFT JOIN civicrm_contact c on c.id = h.entity_id WHERE `identifier_type` = 'navision' AND `identifier` = '{$params['navision_id']}' AND c.is_deleted != '1' GROUP BY id DESC LIMIT 0, 1;";
  $query = CRM_Core_DAO::executeQuery($sql);
  $returnValues = [];
  while($query->fetch()) {
    $returnValues[$query->entity_id] = ['contact_id' => $query->entity_id];

    return civicrm_api3_create_success($returnValues, $params, 'Nav', 'LookUpIdTracker');
  }
  return civicrm_api3_create_success($returnValues, $params, 'Nav', 'LookUpIdTracker');
}
