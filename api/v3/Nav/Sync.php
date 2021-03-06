<?php
use CRM_Nav_ExtensionUtil as E;

/**
 * Nav.Sync API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nav_Sync_spec(&$spec) {
  $spec['poll_size']['api.default'] = 100;
  $spec['entity']['api.default'] = "";
  $spec['entity']['api.description'] = "Restrict api call to nav data sources. Can be 'civiContact', 'civiContRelation', 'civiContStatus', 'civiProcess'";
  $spec['debug']['api.default'] = FALSE;
}

/**
 * Nav.Sync API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nav_Sync($params) {
  if (isset($params['entity'])) {
    $valid_values = array ('civiContact', 'civiProcess', 'civiContRelation', 'civiContStatus');
    foreach ($params['entity'] as $entity) {
      if (!in_array($entity, $valid_values)) {
        throw new API_Exception("Invalid entity parameter {$entity}");
      }
    }
  }
  $core_config = CRM_Core_Config::singleton();
  $nav_userID = CRM_Nav_Config::get('db_log_id');
  $current_id = (int) CRM_Core_DAO::singleValueQuery('SELECT @civicrm_user_id');

  CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
    array(1 => array($nav_userID, 'Integer'))
  );

  $runner = new CRM_Nav_Sync($params['poll_size'], $params['debug'], $params['entity']);
  try {
    $number_of_parsed_entries = $runner->run();
  } catch (Exception $e) {
    if (!empty($current_id)) {
      CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
        array(1 => array($current_id, 'Integer'))
      );
    }
    throw new API_Exception("Error occurred while parsing Navision Records. Error Message: " . $e->getMessage());
  }

  if ($current_id) {
    CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
      array(1 => array($current_id, 'Integer'))
    );
  } else {
    CRM_Core_DAO::executeQuery('SET @civicrm_user_id = NULL');
  }
  return civicrm_api3_create_success(array($number_of_parsed_entries), $params, 'Nav', 'Sync');
}
