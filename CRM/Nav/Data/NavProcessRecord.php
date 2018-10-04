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


class CRM_Nav_Data_NavProcessRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiProcess";

  // HBS
    private $process_id = 'custom_126';
  // local test
//  private $process_id = 'custom_172';

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
  }

  protected function convert_to_civi_data() {
    $nav_data = $this->get_nav_after_data();
    // get contact details
    $this->civi_data_after['Contact'] = array(
      $this->navision_custom_field              => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    );

    $relationship_type_id = $this->get_type_id($this->get_nav_value_if_exist($nav_data, 'Advancement'));
    // get extra data for relationship
    $this->civi_data_after['Relationship'] = array(
      'relationship_type_id'    => $relationship_type_id,
      'start_date'              => $this->get_nav_value_if_exist($nav_data, 'Förderbeginn'),
      'end_date'                => $this->get_nav_value_if_exist($nav_data, 'Allowance_to'), // TODO: this is basically doubled data. Check if this is the correct value
      'custom_130'              => $this->get_nav_value_if_exist($nav_data, 'Angestrebter_Studienabschluss'), // Angestrebter Studienabschluss [option val]
      $this->process_id         => $this->get_nav_value_if_exist($nav_data, 'Process_Entry_No'), // processID
      'custom_127'              => $this->get_nav_value_if_exist($nav_data, 'Candidature_Process_Code'), // Bewerbung Vorgang Code [option val]
      'custom_132'              => $this->get_nav_value_if_exist($nav_data, 'Hauptfach_1'), // Hauptfach Studium
      'custom_140'              => $this->get_nav_value_if_exist($nav_data, 'Subject_Group'), // Fächergruppe [option val]
      'custom_141'              => $this->get_nav_value_if_exist($nav_data, 'Field_of_Study'), // Studienbereich [option val]
      'custom_133'              => $this->get_nav_value_if_exist($nav_data, 'Promotionsfach'), // Promotionsfach
      'custom_134'              => $this->get_nav_value_if_exist($nav_data, 'Promotionsthema'), // Promotionsthema
      'custom_137'              => $this->get_nav_value_if_exist($nav_data, 'Project_Controller'), // Projektbearbeiter/in
      'custom_138'              => $this->get_nav_value_if_exist($nav_data, 'Consultant'), // Referent/in
      'custom_139'              => $this->get_nav_value_if_exist($nav_data, 'Allowance_to'), // Bewilligung bis
      'custom_129'              => $this->get_nav_value_if_exist($nav_data, 'Next_Report_to'), // Nächster Bericht
      // FixME: Unklare Feldzuordnung
//      'custom_???'              => $this->get_nav_value_if_exist($nav_data, 'Advancement_to'), // Studienbereich ???
      'custom_131'              => $this->get_nav_value_if_exist($nav_data, 'Subsidie'), // Förderbereich [option val]
    );
  }

  public function get_relationship_data($type = 'after') {
    switch ($type) {
      case 'before':
        return $this->civi_data_after['Relationship'];
      case 'after':
        return $this->civi_data_after['Relationship'];
      default:
        throw new Exception("Invalid Type {$type} in NavProcessRecord->get_relationship_data");
    }
  }

  public function get_process_id() {
    if (empty($this->civi_data_after['Relationship'][$this->process_id])) {
      $this->log("Couldn't determine processId. Aborting.");
      throw new Exception("Couldn't determine ProcessId. Aborting.");
    }
    return $this->civi_data_after['Relationship'][$this->process_id];
  }

  /**
   * switch over $advancement, then return the corrosponding CiviCRM type_id
   * TODO: Mapping has to be done properly
   *       What values are in $advancements??
   * @param $advancement
   */
  private function get_type_id($advancement) {
    switch ($advancement) {
      case "Study":
        // Studienstipendiat/in
        return 12;
      // TODO: This is a wild guess. Figure out the correct value here
      case "Doctorate":
        // Promotionsstipendiat/in
        return 11;
      default:
        // default value Currently is just Studienstipendiat ?
        $this->log("Couldn't map Advancement Type {$advancement} to relationship_type_id. Default (12 - Studienstipendiat/in) is used");
        return 12;
    }
  }

}