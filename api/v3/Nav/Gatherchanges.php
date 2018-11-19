<?php
use CRM_Nav_ExtensionUtil as E;

/**
 * Nav.GatherChanges API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nav_GatherChanges_spec(&$spec) {
  $spec['entity']['api.default'] = '';
  $spec['entity']['api.description'] = "Restrict api call to changes for civi Entities. Supported Entities: 'Contact', 'Address', 'Relationship', 'Email', 'Phone', 'Website','Custom_Contact'";
  $spec['debug']['api.default'] = FALSE;
}

/**
 * Nav.GatherChanges API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nav_GatherChanges($params) {
  try{
    $runner = new CRM_Nav_ChangeTracker_LogAnalyzeRunner($params['entity'], $params['debug']);
    $runner->process();
    return civicrm_api3_create_success($runner->get_stats(), $params, 'Nav', 'GatherChanges');
  } catch (Exception $e) {
    throw new API_Exception(/*errorMessage*/ 'Everyone knows that the magicword is "sesame"', /*errorCode*/ 1234);
  }
}