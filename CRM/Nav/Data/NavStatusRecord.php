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


class CRM_Nav_Data_NavStatusRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiContStatus";
  private $hbs_contact_id = "4";
  // HBS
//  private $relationship_type_mapping = array(
//    'Vertrauensdozent_in'       => '15',
//    'Stipendiat_in'             => '12',
//    'Promotionsstipendiat_in'   => '11',
//    'Auswahlkommissionsmitglied'=> '14',
//  );
  // local
  private $relationship_type_mapping = array(
    'Vertrauensdozent_in'       => '14',
    'Stipendiat_in'             => '12',
    'Promotionsstipendiat_in'   => '11',
    'Auswahlkommissionsmitglied'=> '13',
  );

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
  }

  /**
   * @throws \Exception
   */
  protected function convert_to_civi_data() {
    // after data
    $nav_data                              = $this->get_nav_after_data();
    $this->civi_data_after['Contact']      = array(
      $this->navision_custom_field        => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    );
    $relationship_type_id                  = $this->get_relationship_type_id($this->get_nav_value_if_exist($nav_data, 'Status'));
    $this->civi_data_after['Relationship'] = array (
      'relationship_type_id'    => $relationship_type_id,
      'start_date'              => $this->get_nav_value_if_exist($nav_data, 'Valid_from'),
      'end_date'                => $this->get_nav_value_if_exist($nav_data, 'Valid_to'),
      'contact_id_b'            => $this->hbs_contact_id,
    );
    $this->fix_microsoft_dates($this->civi_data_after['Relationship']);

    // before data
    $nav_data                              = $this->get_nav_before_data();
    $this->civi_data_before['Contact']      = array(
      $this->navision_custom_field        => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    );
    $relationship_type_id                  = $this->get_relationship_type_id($this->get_nav_value_if_exist($nav_data, 'Status'));
    $this->civi_data_before['Relationship'] = array (
      'relationship_type_id'    => $relationship_type_id,
      'start_date'              => $this->get_nav_value_if_exist($nav_data, 'Valid_from'),
      'end_date'                => $this->get_nav_value_if_exist($nav_data, 'Valid_to'),
      'contact_id_b'            => $this->hbs_contact_id,
    );
    $this->fix_microsoft_dates($this->civi_data_before['Relationship']);
  }

  public function get_relationship_data($type = 'after') {
    switch ($type) {
      case 'before':
        return $this->civi_data_before['Relationship'];
      case 'after':
        return $this->civi_data_after['Relationship'];
      default:
        throw new Exception("Invalid Type {$type} in NavProcessRecord->get_relationship_data");
    }
  }

  public function get_Status_start_date($type = 'after') {
    switch ($type) {
      case 'before':
        return $this->civi_data_before['Relationship']['start_date'];
      case 'after':
        return $this->civi_data_after['Relationship']['start_date'];
      default:
        throw new Exception("Invalid Type {$type} in NavProcessRecord->get_relationship_data");
    }

  }

  /**
   * @param $relationship_type
   *
   * @return mixed
   * @throws \Exception
   */
  private function get_relationship_type_id ($relationship_type) {
    if ($relationship_type == "") {
      return "";
    }
    if (!isset($this->relationship_type_mapping[$relationship_type])) {
      $this->log("Cannot get the relationship_type_id from '{$relationship_type}'. Aborting");
      throw new Exception("Cannot get the relationship_type_id from '{$relationship_type}'. Aborting");
    }
    return $this->relationship_type_mapping[$relationship_type];
  }
}