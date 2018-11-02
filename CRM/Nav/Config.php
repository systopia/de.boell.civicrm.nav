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
 * Class CRM_Nav_Config
 */
class CRM_Nav_Config {

  private static $local        = FALSE;

  private static $candidature_process_code_option_value_cache = [];

  public static  $filter       = [
    '0001-01-01',
    '_blank_',
  ];

  private static $local_config = [
    'hbs_contact_id'                => '4',
    'navision_custom_field'         => 'custom_41',
    'process_id'                    => 'custom_172',
    'org_name_1'                    => 'custom_106',
    'org_name_2'                    => 'custom_107',
    'location_type_private'         => '6',
    'location_type_organization'    => '8',
    'creditor_custom_field_id'      => 'custom_42',
    'debitor_custom_field_id'       => 'custom_43',
    'Vertrauensdozent_in'           => '14',
    'Stipendiat_in'                 => '12',
    'Promotionsstipendiat_in'       => '11',
    'Auswahlkommissionsmitglied'    => '13',
    'Allowance_to'                  => 'custom_174',
    'Angestrebter_Studienabschluss' => 'custom_176',
    'Process_Entry_No'              => 'custom_172',
    'Candidature_Process_Code'      => 'custom_173',
    'Hauptfach_1'                   => 'custom_178',
    'Subject_Group'                 => 'custom_181',
    'Field_of_Study'                => 'custom_182',
    'Promotionsfach'                => 'custom_179',
    'Promotionsthema'               => 'custom_180',
    'Project_Controller'            => 'custom_183',
    'Consultant'                    => 'custom_184',
    'Next_Report_to'                => 'custom_175',
    'Subsidie'                      => 'custom_177',
    'Graduation'                    => '11',
    'Study'                         => '12',
    'bewerbungscode_option_group'   => 'bewerbung_vorgang_code',
    'Advancement_to'                => 'end_date',
    'Förderbeginn'                  => 'start_date',
    // phone_type_id mapping
    'Phone'                         => '1',
    'Mobile'                        => '2',
    'Fax'                           => '3',
    'Pager'                         => '4',
    'Voicemail'                     => '5',
    'website_type_id'               => 'Work',
  ];

  /**
   * @param $attribute
   *
   * @return mixed|string
   */
  public static function get($attribute) {
    if (self::$local) {
      if (isset(self::$local_config[$attribute])) {
        return self::$local_config[$attribute];
      }
      return "";
    }
    if (isset(self::$local_config[$attribute])) {
      return self::$hbs_config[$attribute];
    }
    return "";
  }

  private static $hbs_config   = [
    'hbs_contact_id'                => '4',
    'navision_custom_field'         => 'custom_147',
    'process_id'                    => 'custom_126',
    'org_name_1'                    => 'custom_45',
    'org_name_2'                    => 'custom_46',
    'location_type_private'         => '6',
    'location_type_organization'    => '8',
    'creditor_custom_field_id'      => 'custom_164',
    'debitor_custom_field_id'       => 'custom_165',
    'Vertrauensdozent_in'           => '15',
    'Stipendiat_in'                 => '12',
    'Promotionsstipendiat_in'       => '11',
    'Auswahlkommissionsmitglied'    => '14',
    'Allowance_to'                  => 'custom_139',
    'Angestrebter_Studienabschluss' => 'custom_130',
    'Process_Entry_No'              => 'custom_126',
    'Candidature_Process_Code'      => 'custom_127',
    'Hauptfach_1'                   => 'custom_132',
    'Subject_Group'                 => 'custom_140',
    'Field_of_Study'                => 'custom_141',
    'Promotionsfach'                => 'custom_133',
    'Promotionsthema'               => 'custom_134',
    'Project_Controller'            => 'custom_137',
    'Consultant'                    => 'custom_138',
    'Next_Report_to'                => 'custom_129',
    'Subsidie'                      => 'custom_131',
    'Graduation'                    => '11',
    'Study'                         => '12',
    'bewerbungscode_option_group'   => 'bewerbung_vorgang_code',
    'Advancement_to'                => 'end_date',
    'Förderbeginn'                  => 'start_date',
    // phone_type_id mapping
    'Phone'                         => '1',
    'Mobile'                        => '2',
    'Fax'                           => '3',
    'Pager'                         => '4',
    'Voicemail'                     => '5',
  ];

  /**
   * Checks if an option_value exist, cache results locally and create
   * Option_value if non existent
   * @param $check_option_value
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function check_or_create_option_value($check_option_value) {
    self::get_option_values();
    if (in_array($check_option_value, self::$candidature_process_code_option_value_cache)) {
      return;
    }
    self::create_option_value($check_option_value);
  }

  /**
   * Create an Optionvalue to the 'bewerbungscode_option_group' group
   * and adds the name to the local_cache
   * @param $check_option_value
   *
   * @throws \CiviCRM_API3_Exception
   */
  private static function create_option_value($check_option_value) {
    $result = civicrm_api3('OptionValue', 'create', array(
      'sequential' => 1,
      'option_group_id' => self::get('bewerbungscode_option_group'),
      'label' => $check_option_value,
    ));
    if ($result['is_error'] == '1') {
      throw new Exception("Error Occured while adding Optionvalue to group " . self::get('bewerbungscode_option_group'));
    }
    self::$candidature_process_code_option_value_cache[] = $check_option_value;
  }

  /**
   * Gets option values for bewerbungscode_option_group, static for now.
   * If other option values are needed to be checked/created this has to be rewritten
   * @throws \CiviCRM_API3_Exception
   */
  private static function get_option_values() {
    if (!empty(self::$candidature_process_code_option_value_cache)) {
      return;
    }
    $option_group = self::get('bewerbungscode_option_group');
    $result = civicrm_api3('OptionValue', 'get', array(
      'sequential' => 1,
      'option_group_id' => $option_group,
    ));
    if ($result['is_error'] != '1') {
      self::$candidature_process_code_option_value_cache = [];  // clear cache
      foreach ($result['values'] as $val) {
        self::$candidature_process_code_option_value_cache[] = $val['name'];
      }
    }
  }

  /**
   * @return bool
   */
  public static function local() {
    return self::$local;
  }
}