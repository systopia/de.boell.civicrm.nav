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

  private static $is_dev       = FALSE;

  private static $soap_url     = [
    'pro' => 'http://10.1.0.143:7077/NAVUSemployer_idER/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/',
    'dev' => 'http://10.1.0.148:7037/NAVUSER/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/',
  ];

  private static $candidature_process_code_option_value_cache = [];

  public static  $filter       = [
    '0001-01-01',
    '_blank_',
  ];

  public static $studienwerk_temlpate_name = 'nav_studienwerk_template';

  public static $kreditoren_temlpate_name  = 'nav_kreditoren_template';

  public static $exculde_log_fields = [
    'log_date', 'log_user_id', 'log_action', 'log_conn_id', 'website_type_id', 'modified_date',
    'email_greeting_display', 'postal_greeting_display', 'addressee_display', 'email_greeting_custom',
    'postal_greeting_id', 'postal_greeting_custom', 'addressee_custom', 'addressee_id',
    'entity_id', 'id', 'hash', 'phone_numeric', 'contact_id', 'is_primary',
    'location_type_id', 'phone_type_id',
    'employer_id',
    'organization_name',
    'contact_type',
    'contact_sub_type',
    'do_not_email',
    'do_not_phone',
    'do_not_mail',
    'do_not_sms',
    'do_not_trade',
    'is_opt_out',
    'legal_identifier',
    'display_name',
    'nick_name',
    'legal_name',
    'image_URL',
    'preferred_language',
    'preferred_communication_method',
    'birth_date',
    'gender_id',
    'hash',
    'api_key',
    'source',
    'created_date',
    'modified_date',
    'is_deleted',
    'sic_code',
    'primary_contact_id',
  ];

  /**
   * @var array Not used yet - needs to be a filter specific for stw notifications
   */
  public static $exclude_for_kreditoren = [
    'job_title',
  ];

  private static $local_config = [
    'db_log_id'                       => '1',
    'hbs_contact_id'                  => '2',
    'navision_custom_field'           => 'custom_88',
    'process_id'                      => 'custom_52',
    'org_name_1'                      => 'custom_92',
    'org_name_2'                      => 'custom_93',
    'location_type_private'           => '6',
    'location_type_organization'      => '8',
    'creditor_custom_field_id'        => 'custom_89',
    'debitor_custom_field_id'         => 'custom_90',
    'Vertrauensdozent_in'             => '14',
    'Stipendiat_in'                   => '12',
    'Promotionsstipendiat_in'         => '11',
    'Auswahlkommissionsmitglied'      => '13',
    'Allowance_to'                    => 'custom_54',
    'Angestrebter_Studienabschluss'   => 'custom_56',
    'Process_Entry_No'                => 'custom_52',
    'Candidature_Process_Code'        => 'custom_53',
    'Hauptfach_1'                     => 'custom_58',
    'Subject_Group'                   => 'custom_61',
    'Field_of_Study'                  => 'custom_62',
    'Promotionsfach'                  => 'custom_59',
    'Promotionsthema'                 => 'custom_600',
    'Project_Controller'              => 'custom_63',
    'Consultant'                      => 'custom_64',
    'Next_Report_to'                  => 'custom_55',
    'Subsidie'                        => 'custom_57',
    'Graduation'                      => '11',
    'Study'                           => '12',
    'bewerbungscode_option_group'     => 'bewerbung_vorgang_code',
    'Advancement_to'                  => 'end_date',
    'Förderbeginn'                    => 'start_date',
    // phone_type_id mapping
    'Phone'                           => '1',
    'Mobile'                          => '2',
    'Fax'                             => '3',
    'Pager'                           => '4',
    'Voicemail'                       => '5',
    'website_type_id'                 => 'Work',
    'id_table'                        => 'civicrm_value_contact_id_history',
    'employee_relationship_type_id'   => '16',
    'contact_extra_information_table' => 'civicrm_value_contact_extra',
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
    'db_log_id'                       => '145801',
    'hbs_contact_id'                  => '4',
    'navision_custom_field'           => 'custom_147',
    'process_id'                      => 'custom_126',
    'org_name_1'                      => 'custom_106',
    'org_name_2'                      => 'custom_107',
    'location_type_private'           => '6',
    'location_type_organization'      => '8',
    'creditor_custom_field_id'        => 'custom_164',
    'debitor_custom_field_id'         => 'custom_165',
    'Vertrauensdozent_in'             => '15',
    'Stipendiat_in'                   => '12',
    'Promotionsstipendiat_in'         => '11',
    'Auswahlkommissionsmitglied'      => '14',
    'Allowance_to'                    => 'custom_139',
    'Angestrebter_Studienabschluss'   => 'custom_130',
    'Process_Entry_No'                => 'custom_126',
    'Candidature_Process_Code'        => 'custom_127',
    'Hauptfach_1'                     => 'custom_132',
    'Subject_Group'                   => 'custom_140',
    'Field_of_Study'                  => 'custom_141',
    'Promotionsfach'                  => 'custom_133',
    'Promotionsthema'                 => 'custom_134',
    'Project_Controller'              => 'custom_137',
    'Consultant'                      => 'custom_138',
    'Next_Report_to'                  => 'custom_129',
    'Subsidie'                        => 'custom_131',
    'Graduation'                      => '11',
    'Study'                           => '12',
    'bewerbungscode_option_group'     => 'bewerbung_vorgang_code',
    'Advancement_to'                  => 'end_date',
    'Förderbeginn'                    => 'start_date',
    // phone_type_id mapping
    'Phone'                           => '1',
    'Mobile'                          => '2',
    'Fax'                             => '3',
    'Pager'                           => '4',
    'Voicemail'                       => '5',
    'id_table'                        => 'civicrm_value_contact_id_history',
    'employee_relationship_type_id'   => '18',
    'contact_extra_information_table' => 'civicrm_value_contact_extra',
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

  public static function is_dev() {
    return self::$is_dev;
  }

  public static function get_soap_url() {
    if (self::$is_dev) {
      return self::$soap_url['dev'];
    } else {
      return self::$soap_url['pro'];
    }
  }

  public static function get_last_timestamp() {
    $nav_settings = self::get_settings();
    if (!empty($nav_settings['last_change_gather_run'])) {
      return $nav_settings['last_change_gather_run'];
    }
    return '';
  }

  public static function set_last_timestamp($timestamp) {
    $nav_settings = self::get_settings();
    $nav_settings['last_change_gather_run'] = $timestamp;
    self::set_settings($nav_settings);
  }

  // Log Analyszer
  // use Civi-Settings for timestamp etc ...

  private static function get_settings() {
    $settings = CRM_Core_BAO_Setting::getItem('de.boell.civicrm.nav', 'NavSync_settings');
    return $settings;
  }

  /**
   * set Mailingtools settings
   *
   * @param $settings array
   */
  private static function set_settings($settings) {
    CRM_Core_BAO_Setting::setItem($settings, 'de.boell.civicrm.nav', 'NavSync_settings');
  }

}