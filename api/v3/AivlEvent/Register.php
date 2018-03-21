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
  $event = civicrm_api3('Event', 'getsingle', array('id' => $params['event_id']));

  // set some defaults
  if (empty($params['role_id']) && !empty($event['default_role_id'])) {
    $params['role_id'] = $event['default_role_id'];
  }

  // check if a participant already exists for this role
  $existing_registrations = civicrm_api3('Participant', 'get', array(
    'contact_id' => $params['contact_id'],
    'event_id'   => $params['event_id'],
  ));
  if ($existing_registrations['count'] > 0) {
    // TODO: use i3val:
    // $existing_registration = reset($existing_registrations['values']);
    // $params['id'] = $existing_registration['id'];
    // civicrm_api3('Participant', 'request_update', $params);

    throw new Exception("Contact already registered", 1);
  }

  // now basically just create a participant
  return civicrm_api3('Participant', 'create', $params);
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
