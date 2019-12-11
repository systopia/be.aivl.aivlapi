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

  // issue 5669 - process street numbers if present
  CRM_Aivlapi_Processor::processStreetNumbers($params);

  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);

  // add to groups
  CRM_Aivlapi_Processor::processGroupSignup($params);


  // also process the ORGANISATION if submitted
  $organisation = CRM_Aivlapi_Processor::extractSubdata('organization_',$params);
  if ($organisation) {
    // resolve contact
    CRM_Aivlapi_Processor::resolveContact($organisation);

    // add to groups
    CRM_Aivlapi_Processor::processGroupSignup($organisation);
  }

  // load the EVENT
  $event = civicrm_api3('Event', 'getsingle', array(
    'id'                => $params['event_id'],
    'check_permissions' => 0));

  // register main contact
  $registration = CRM_Aivlapi_EventProcessor::createParticipant($params, $event);

  // register organisation (if submitted)
  CRM_Aivlapi_EventProcessor::createParticipant($organisation, $event);

  // process ACTIVITY
  $activity = CRM_Aivlapi_Processor::extractSubdata('activity_',$params);
  CRM_Aivlapi_ActivityProcessor::createActivity($activity, $params, $organisation);

  // process RELATIONSHIP
  $relationship = CRM_Aivlapi_Processor::extractSubdata('relationship_',$params);
  CRM_Aivlapi_RelationshipProcessor::processRelationship($relationship, $params, $organisation);

  // return (the main registration)
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
