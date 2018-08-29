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
  $spec['poll_size']['api.default'] = 10;
  $spec['entity']['api.default'] = "";
  $spec['entity']['api.description'] = "Restrict api call to nav data sources. Can be 'civiContact', 'civiContRelation', 'civiContStatus', 'civiProcess'";
  $spec['debug']['api.defaul'] = FALSE;
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
    $returnValues = array();

    if (isset($params['entity'])) {
      $valid_values = array ('civiContact', 'civiProcess', 'civiContRelation', 'civiContStatus');
      foreach ($params['entity'] as $entity) {
        if (!in_array($entity, $valid_values)) {
          throw new API_Exception("Invalid entity parameter {$entity}");
        }
      }
    }
    $runner = CRM_Nav_Sync($params['size'], $params['entity'], $params['debug']);
    $runner->run();

    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
//    throw new API_Exception('Everyone knows that the magicword is "sesame"', 1234);
}
