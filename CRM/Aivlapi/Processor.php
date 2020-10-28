<?php
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2018 SYSTOPIA                            |
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
 * Offers generic API processing functions
 */
class CRM_Aivlapi_Processor {

  private static $technical_fields = array('sequential', 'prettyprint', 'json', 'check_permissions', 'version');

  /**
   * generic preprocessor for every call
   */
  public static function preprocessCall(&$params, $log_id = 'n/a') {
    self::fixAPIUser();
    if (CRM_Aivlapi_Configuration::logAPICalls()) {
      CRM_Core_Error::debug_log_message("{$log_id}: " . json_encode($params));
    }

    // undo REST related changes
    CRM_Aivlapi_CustomData::unREST($params);

    // resolve any custom fields
    CRM_Aivlapi_CustomData::resolveCustomFields($params);

    // trim all values to remove unwanted spaces
    foreach ($params as $paramKey => $paramValue) {
      if (!is_array($paramValue)) {
        $params[$paramKey] = trim($paramValue);
      }
    }
  }

  /**
   * strip the technical API fields from the params
   */
  public static function stripTechnicalFields(&$params) {
    foreach (self::$technical_fields as $field_name) {
      if (isset($params[$field_name])) {
        unset($params[$field_name]);
      }
    }
  }

  /**
   * Get the contact ID:
   *  if contact_id is given, great!
   *  if not, use XCM/resolveContact to find/create it
   *
   * @param $params array parameters
   * @return int contact ID
   */
  public static function getContactID(&$params) {
    // if the contact_id is given, we will take that
    if (!empty($params['contact_id'])) {
      return $params['contact_id'];
    }

    // otherwise, use XCM
    CRM_Aivlapi_Processor::resolveContact($params);
    return $params['contact_id'];
  }

  /**
   * will use XCM to resolve the contact and add it as
   *  'contact_id' parameter in the params array
   */
  public static function resolveContact(&$params) {
    $params['check_permissions'] = 0;
    if (empty($params['contact_type'])) {
      $params['contact_type'] = 'Individual';
    }
    // if hash is in parameters, use that to get contactId
    if (isset($params['hash']) && !empty($params['hash'])) {
      try {
        $params['contact_id'] = civicrm_api3('Contact', 'getvalue', [
          'hash' => $params['hash'],
          'return' => 'id',
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    } else {
      $contact_match = civicrm_api3('Contact', 'getorcreate', $params);
      $params['contact_id'] = $contact_match['id'];
    }
  }

  /**
   * Issue 5669 - sending form might have street_numbers that have to be added to the street address
   *
   * @param $params
   */
  public static function processStreetNumbers(&$params) {
    if (isset($params['street_number'])) {
      $params['street_address'] .= " " . trim($params['street_number']);
    }
    if (isset($params['organization_street_number'])) {
      $params['organization_street_address'] .= " " . trim($params['organization_street_number']);
    }
  }

  /**
   * Extract (and remove) all the data with a certain prefix.
   * The prefix is stripped
   *
   * @param $prefix
   * @param $data
   *
   * @return array the extracted data
   */
  public static function extractSubdata($prefix, &$data) {
    $subdata = array();
    $prefix_length = strlen($prefix);
    $keys = array_keys($data);
    foreach ($keys as $key) {
      if (substr($key, 0, $prefix_length) == $prefix) {
        // prefix matches! add to subdata
        $subdata[substr($key, $prefix_length)] = $data[$key];

        // ...and remove from data
        unset($data[$key]);
      }
    }

    // undo REST related changes
    CRM_Aivlapi_CustomData::unREST($subdata);

    // resolve any custom fields
    CRM_Aivlapi_CustomData::resolveCustomFields($subdata);

    return $subdata;
  }

  /**
   * Sign contact up for a group if the two fields
   *  add_to_group
   *  add_to_group_id
   * are not empty.
   */
  public static function processGroupSignup($params) {
    if (   !empty($params['add_to_group'])
        && !empty($params['add_to_group_id'])
        && !empty($params['contact_id'])) {

      $group_id = (int) $params['add_to_group_id'];
      if ($group_id && !empty($params['contact_id'])) {
        civicrm_api3('GroupContact', 'create', array(
          'check_permissions' => 0,
          'contact_id'        => $params['contact_id'],
          'group_id'          => $group_id));
      }
    }
  }


  /**
   * Create a registration for the
   * @param $participant  array participant data, potentially with extra data with 'participant_' prefix
   * @param $event        array the event to register for
   * @return array              registration result
   * @throws CiviCRM_API3_Exception
   */
  public static function createParticipant($participant, $event) {
    if (empty($contact_data)) {
      return civicrm_api3_create_success();
    }

    // extract the 'participant_' data
    $participant = CRM_Aivlapi_Processor::extractSubdata('participant_', $participant) + $participant;

    // set some defaults
    if (empty($participant['role_id']) && !empty($event['default_role_id'])) {
      $participant['role_id'] = $event['default_role_id'];
    }

    // see if a participant already exists for this contact/event
    $existing_registrations = civicrm_api3('Participant', 'get', array(
        'check_permissions' => 0,
        'contact_id'        => $participant['contact_id'],
        'event_id'          => $event['id'],
        'return'            => 'id,participant_role_id',
    ));


    if ($existing_registrations['count'] > 0) {
      // TODO: use i3val?
      // for now: create activity
      $registration = reset($existing_registrations['values']);
      $participant['participant_id'] = $registration['id'];
      CRM_Aivlapi_Processor::stripTechnicalFields($participant);

      $details = CRM_Aivlapi_Processor::renderTemplate('Aivlapi/AivlEvent/RepeatedRegistration.tpl', array(
          'contact_id'     => $participant['contact_id'],
          'participant_id' => $participant['participant_id'],
          'data'           => $participant));
      civicrm_api3('Activity', 'create', array(
          'check_permissions' => 0,
          'activity_type_id'  => CRM_Aivlapi_Configuration::getRegistrationUpdateActivityID(),
          'subject'           => 'Repeated Registration Submitted',
          'target_id'         => $participant['contact_id'],
          'details'           => $details,
          'status_id'         => 1, // scheduled
      ));

    } else {
      // not there? => just create a participant object
      $param['check_permissions'] = 0;
      $new_registration = civicrm_api3('Participant', 'create', $participant);

      // and re-load to get the status
      $registration = civicrm_api3('Participant', 'getsingle', array(
          'check_permissions' => 0,
          'id'                => $new_registration['id'],
          'return'            => 'id,role_id',
      ));
    }

    return $registration;
  }

  /**
   * Make sure the current user exists
   */
  public static function fixAPIUser() {
    // see https://github.com/CiviCooP/org.civicoop.apiuidfix
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (empty($userId)) {
      $valid_user = FALSE;

      // Check and see if a valid secret API key is provided.
      $api_key = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
      if (!$api_key || strtolower($api_key) == 'null') {
        $session->set('userID', CRM_Aivlapi_Configuration::getFallbackUserID());
      }

      $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

      // If we didn't find a valid user, die
      if (!empty($valid_user)) {
        //now set the UID into the session
        $session->set('userID', $valid_user);
      }
    }
  }

  /**
   * Render the given template with the given data
   */
  public static function renderTemplate($template_path, $data) {
    $smarty = CRM_Core_Smarty::singleton();

    // first backup original variables, since smarty instance is a singleton
    $oldVars = $smarty->get_template_vars();
    $backupFrame = array();
    foreach ($data as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $backupFrame[$key] = isset($oldVars[$key]) ? $oldVars[$key] : NULL;
    }

    // then assign new variables
    foreach ($data as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    // create result
    $rendered_text =  $smarty->fetch($template_path);

    // reset smarty variables
    foreach ($backupFrame as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    return $rendered_text;
  }

}
