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
 * Process AivlEvent.register
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_event_register($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlEvent.register');

  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);

  // load the event
  $event = civicrm_api3('Event', 'getsingle', array(
    'id'                => $params['event_id'],
    'check_permissions' => 0));

  // strip 'participant_' prefixes
  $keys = array_keys($params);
  foreach ($keys as $key) {
    if (substr($key, 0, 12) == 'participant_') {
      $new_key = substr($key, 12);
      if (empty($params[$new_key])) {
        $params[$new_key] = $params[$key];
      }
    }
  }

  // set some defaults
  if (empty($params['role_id']) && !empty($event['default_role_id'])) {
    $params['role_id'] = $event['default_role_id'];
  }

  // see if a participant already exists for this contact/event
  $existing_registrations = civicrm_api3('Participant', 'get', array(
    'check_permissions' => 0,
    'contact_id'        => $params['contact_id'],
    'event_id'          => $params['event_id'],
    'return'            => 'id,participant_role_id',
  ));


  if ($existing_registrations['count'] > 0) {
    // TODO: use i3val?
    // for now: create activity
    $registration = reset($existing_registrations['values']);
    $params['participant_id'] = $registration['id'];
    CRM_Aivlapi_Processor::stripTechnicalFields($params);

    $details = CRM_Aivlapi_Processor::renderTemplate('Aivlapi/AivlEvent/RepeatedRegistration.tpl', array(
      'contact_id'     => $params['contact_id'],
      'participant_id' => $params['participant_id'],
      'data'           => $params));
    civicrm_api3('Activity', 'create', array(
      'check_permissions' => 0,
      'activity_type_id'  => CRM_Aivlapi_Configuration::getRegistrationUpdateActivityID(),
      'subject'           => 'Repeated Registration Submitted',
      'target_id'         => $params['contact_id'],
      'details'           => $details,
      'status_id'         => 1, // scheduled
    ));

  } else {
    // not there? => just create a participant object
    $param['check_permissions'] = 0;
    $new_registration = civicrm_api3('Participant', 'create', $params);

    // and re-load to get the status
    $registration = civicrm_api3('Participant', 'getsingle', array(
      'check_permissions' => 0,
      'id'                => $new_registration['id'],
      'return'            => 'id,role_id',
    ));
  }

  return civicrm_api3_create_success($registration);
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_event_register_spec(&$params) {
  // CONTACT BASE
  $params['event_id'] = array(
    'name'         => 'event_id',
    'api.required' => 1,
    'title'        => 'Event to sign up to',
    );
  $params['role_id'] = array(
    'name'         => 'role_id',
    'api.required' => 0,
    'title'        => 'Particpant Role',
    );
}
