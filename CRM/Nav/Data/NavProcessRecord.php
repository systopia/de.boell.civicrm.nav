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
 * Class CRM_Nav_Data_NavProcessRecord
 */
class CRM_Nav_Data_NavProcessRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiProcess";

  /**
   * CRM_Nav_Data_NavProcessRecord constructor.
   *
   * @param      $nav_data_after
   * @param null $nav_data_before
   *
   * @throws \Exception
   */
  public function __construct($nav_data_after, $nav_data_before = NULL, $debug = FALSE) {
    parent::__construct($nav_data_after, $nav_data_before, $debug);
  }

  /**
   * convert_to_civi_data
   */
  public function convert_to_civi_data() {
    $nav_data = $this->get_nav_after_data();
    // get contact details
    $this->civi_data_after['Contact'] = [
      $this->navision_custom_field => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    ];

    $relationship_type_id = $this->get_type_id($this->get_nav_value_if_exist($nav_data, 'Advancement'));
    // get extra data for relationship
    $this->civi_data_after['Relationship'] = [
      'relationship_type_id'                               => $relationship_type_id,
      'start_date'                                         => $this->get_nav_value_if_exist($nav_data, 'Förderbeginn'),
      'end_date'                                           => $this->get_nav_value_if_exist($nav_data, 'Advancement_to'),
      CRM_Nav_Config::get('Angestrebter_Studienabschluss') => $this->get_nav_value_if_exist($nav_data, 'Angestrebter_Studienabschluss'), // Angestrebter Studienabschluss [option val]
      CRM_Nav_Config::get('Process_Entry_No')              => $this->get_nav_value_if_exist($nav_data, 'Process_Entry_No'),// processID
      CRM_Nav_Config::get('Candidature_Process_Code')      => $this->get_nav_value_if_exist($nav_data, 'Candidature_Process_Code'),// Bewerbung Vorgang Code [option val]
      CRM_Nav_Config::get('Hauptfach_1')                   => $this->get_nav_value_if_exist($nav_data, 'Hauptfach_1'),// Hauptfach Studium
      CRM_Nav_Config::get('Subject_Group')                 => $this->get_nav_value_if_exist($nav_data, 'Subject_Group'),// Fächergruppe [option val]
      CRM_Nav_Config::get('Field_of_Study')                => $this->get_nav_value_if_exist($nav_data, 'Field_of_Study'),// Studienbereich [option val]
      CRM_Nav_Config::get('Promotionsfach')                => $this->get_nav_value_if_exist($nav_data, 'Promotionsfach'),// Promotionsfach
      CRM_Nav_Config::get('Promotionsthema')               => $this->get_nav_value_if_exist($nav_data, 'Promotionsthema'),// Promotionsthema
      CRM_Nav_Config::get('Project_Controller')            => $this->get_nav_value_if_exist($nav_data, 'Project_Controller'), // Projektbearbeiter/in
      CRM_Nav_Config::get('Consultant')                    => $this->get_nav_value_if_exist($nav_data, 'Consultant'), // Referent/in
      CRM_Nav_Config::get('Allowance_to')                  => $this->get_nav_value_if_exist($nav_data, 'Allowance_to'), // Bewilligung bis
      CRM_Nav_Config::get('Next_Report_to')                => $this->get_nav_value_if_exist($nav_data, 'Next_Report_to'), // Nächster Bericht
      CRM_Nav_Config::get('Subsidie')                      => $this->get_nav_value_if_exist($nav_data, 'Subsidie'),// Förderbereich [option val]
    ];
    $this->fix_navision_data();
  }

  /**
   * Fix Navision Entries for Consultant and Project Controller by
   * removing the INTRANET/ substring from the civi value
   */
  private function fix_navision_data() {
    $consultant_string = $this->civi_data_after['Relationship'][CRM_Nav_Config::get('Consultant')];
    if(strpos($consultant_string, 'INTRANET\\') !== FALSE) {
      $this->civi_data_after['Relationship'][CRM_Nav_Config::get('Consultant')] = explode('\\', $consultant_string)[1];
    }
    $project_controller_string = $this->civi_data_after['Relationship'][CRM_Nav_Config::get('Project_Controller') ];
    if(strpos($project_controller_string, 'INTRANET\\') !== FALSE) {
      $this->civi_data_after['Relationship'][CRM_Nav_Config::get('Project_Controller')] = explode('\\', $project_controller_string)[1];
    }
  }

  /**
   * @param string $type
   *
   * @return mixed
   * @throws \Exception
   */
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

  /**
   * @return mixed
   * @throws \Exception
   */
  public function get_process_id() {
    if (empty($this->civi_data_after['Relationship'][CRM_Nav_Config::get('process_id')])) {
      $this->log("Couldn't determine processId. Aborting.");
      throw new Exception("Couldn't determine ProcessId. Aborting.");
    }
    return $this->civi_data_after['Relationship'][CRM_Nav_Config::get('process_id')];
  }

  /**
   * switch over $advancement, then return the corresponding CiviCRM type_id
   * TODO: Mapping has to be done properly
   *       What values are in $advancements??
   * @param $advancement
   *
   * @return int
   */
  private function get_type_id($advancement) {
    switch ($advancement) {
      case "Study":
        // Studienstipendiat/in
        return CRM_Nav_Config::get('Study');
      // TODO: This is a wild guess. Figure out the correct value here
      case "Graduation":
        // Promotionsstipendiat/in
        return CRM_Nav_Config::get('Graduation');
      case "Sonstige":
        // Sonstiges Stipendium
        return CRM_Nav_Config::get('Sonstige_Stipendiat_in');
      default:
        throw new Exception("Invalid Advancement Type {$advancement}");
        // default value Currently is just Studienstipendiat ?
//        $this->log("Couldn't map Advancement Type {$advancement} to relationship_type_id. Default (12 - Studienstipendiat/in) is used");
//        return CRM_Nav_Config::get('Study');
    }
  }

}