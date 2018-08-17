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


class CRM_Nav_Data_NavProcess extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiProcess";

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before = NULL);
  }

  protected function convert_to_civi_data() {
    $nav_data = $this->get_nav_after_data();
    // get contact details
    $this->civi_data['Contact'] = array(
      'custom_147'              => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    );

    $relationship_type_id = $this->get_type_id($this->get_nav_value_if_exist($nav_data, 'Advancement'));
    // get extra data for relationship
    $this->civi_extra_data['Relationship'] = array(
      'relationship_type_id'    => $relationship_type_id,
      'start_date'              => $this->get_nav_value_if_exist($nav_data, 'Förderbeginn'),
      'end_date'                => $this->get_nav_value_if_exist($nav_data, 'Allowance_to'), // TODO: this is basically doubled data. Check if this is the correct value
      'custom_130'              => $this->get_nav_value_if_exist($nav_data, 'Angestrebter_Studienabschluss'), // Angestrebter Studienabschluss
      'custom_126'              => $this->get_nav_value_if_exist($nav_data, 'Process_Entry_No'), // processID
      'custom_127'              => $this->get_nav_value_if_exist($nav_data, 'Candidature_Process_Code'), // Bewerbung Vorgang Code
      'custom_132'              => $this->get_nav_value_if_exist($nav_data, 'Hauptfach_1'), // Hauptfach Studium
      'custom_131'              => $this->get_nav_value_if_exist($nav_data, 'Subject_Group'), // Förderbereich
      'custom_140'              => $this->get_nav_value_if_exist($nav_data, 'Field_of_Study'), // Fächergruppe
      'custom_133'              => $this->get_nav_value_if_exist($nav_data, 'Promotionsfach'), // Promotionsfach
      'custom_134'              => $this->get_nav_value_if_exist($nav_data, 'Promotionsthema'), // Bewerbung Vorgang Code
      'custom_137'              => $this->get_nav_value_if_exist($nav_data, 'Project_Controller'), // Projektbearbeiter/in
      'custom_138'              => $this->get_nav_value_if_exist($nav_data, 'Consultant'), // Referent/in
      'custom_139'              => $this->get_nav_value_if_exist($nav_data, 'Allowance_to'), // Bewilligung bis
      'custom_129'              => $this->get_nav_value_if_exist($nav_data, 'Next_Report_to'), // Nächster Bericht
      // FixME: Unklare Feldzuordnung
      'custom_141'              => $this->get_nav_value_if_exist($nav_data, 'Advancement_to'), // Studienbereich ???
      //      'custom_126'              => $this->get_nav_value_if_exist($nav_data, 'Subsidie'), // What is a subsidie? And is this mapped in Civi?
    );
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