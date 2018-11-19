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
 * TODO: Create Settings page for these settings?
 * Class CRM_Nav_Exporter_Mailer
 */
class CRM_Nav_Exporter_Mailer {

  private $email_from              = 'civi2navision@boell.de';
  private $email_name_from         = 'Civi2Navision Bot';
  private $subject                 = 'Daily Civi2Nav Report';
  private $sender_contact_id       = '4'; // (TODO: create user for this .. ?)

  private $to_name_sw              = 'Studienwerk HBS';
  private $to_name_kred            = 'Navision HBS';

  private $to_email_sw             = 'civicrm-stw@boell.de';
  private $to_email_kred           = 'kreditorenNAV2009@boell.de';

  public function __construct() {
  }

  public function create_email($template_name, $content, $timestamp) {
    $values = [];
    switch ($template_name) {
      case CRM_Nav_Config::$studienwerk_temlpate_name:
        $template_id = $this->get_template_id($template_name);
        $values['to_name'] = $this->to_name_sw ;
        $values['to_email'] = $this->to_email_sw;
        break;
      case CRM_Nav_Config::$kreditoren_temlpate_name:
        $template_id = $this->get_template_id($template_name);
        $values['to_name'] = $this->to_name_kred;
        $values['to_email'] = $this->to_email_kred;
        break;
      default:
        throw new Exception("Invalid template type ({$template_name})!");
    }
    $values['id'] = $template_id;
    $values['from'] = "\"{$this->email_name_from}\" <{$this->email_from}>";
    $values['contact_id'] = $this->sender_contact_id;

    $smarty_variables = [
      'timestamp' => $timestamp,
      'all_contact_data' => $content,
    ];
    $values['template_params'] = $smarty_variables;
    $result = civicrm_api3('MessageTemplate', 'send', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Error sending Emails to {$template_name}");
    }
  }

  private function get_template_id($template_name) {
    $result = civicrm_api3('MessageTemplate', 'get', array(
      'sequential' => 1,
      'msg_title' => $template_name,
    ));
    if ($result['count'] > '1' || $result['is_error'] == '1') {
      throw new Exception("Error determining Email Template for {$template_name}.");
    }
    if ($result['count'] == '0') {
      return $this->create_template($template_name);
    }
    if ($result['count'] == '1') {
      return $result['id'];
    }
    throw new Exception("Template not found - unclear state. Seek help");
  }

  private function create_template($template_name) {
    $template_content = file_get_contents(__DIR__ . "/../../../templates/mailer_template.tpl");
    $result = civicrm_api3('MessageTemplate', 'create', [
      'sequential'  => 1,
      'msg_title'   => $template_name,
      'msg_html'    => $template_content,
      'msg_subject' => $this->subject,
    ]);
    if ($result['is_error'] == '1') {
      throw new Exception("Coulnd't create message template.");
    }
    return $result['id'];
  }
}