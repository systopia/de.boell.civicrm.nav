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
 * Class CRM_Nav_ChangeTracker_RelationshipAnalyzer
 */
class CRM_Nav_ChangeTracker_RelationshipAnalyzer extends CRM_Nav_ChangeTracker_AnalyzerBase {

  private $relationship_cache;

  /**
   * CRM_Nav_ChangeTracker_RelationshipAnalyzer constructor.
   *
   * @param $timestamp
   * @param $debug
   *
   * @throws \Exception
   */
  public function __construct($timestamp, $debug) {
    $this->_select_fields = ['id', 'entity_id'];
    $this->type = 'Relationship';
    $this->_log_table = 'log_civicrm_value_studienwerk_relationship';

    $this->relationship_cache = [];
    parent::__construct($timestamp, $debug);
  }

  /**
   * @return mixed|string
   */
  protected function get_my_class_name() {
    return get_class();
  }


  /**
   * @param $query
   *
   * @return mixed|void
   * @throws \CiviCRM_API3_Exception
   */
  protected function eval_query(&$query) {
    if (!isset($this->_record_ids[$query->id])) {
      $contact_id = $this->get_relationship_contact_id($query->entity_id);
      if ($contact_id == '') {
        return;
      }
      if ($this->is_nav_contact($contact_id)) {
        $this->_record_ids[$query->id] = $contact_id;
      }
    }
  }

  /**
   * @param $relationship_id
   *
   * @return mixed|string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_relationship_contact_id($relationship_id) {
    if (isset($this->relationship_cache[$relationship_id])) {
      return $this->relationship_cache[$relationship_id];
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('Relationship', 'get', array(
      'sequential' => 1,
      'return' => array("contact_id_a"),
      'id' => $relationship_id,
    ));
    if ($result['count'] != '1' || $result['is_error'] == '1') {
      $this->log("[ERROR] Didn't find Relationship for ID {$relationship_id}.");
      $this->error_counter += 1;
      return '';
    }
    return $result['values']['0']['contact_id_a'];
  }

}
