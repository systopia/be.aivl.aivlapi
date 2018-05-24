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
 * Offers event API processing functions
 */
class CRM_Aivlapi_EventProcessor {

  /**
   * Create a registration for the
   * @param $participant  array participant data, potentially with extra data with 'participant_' prefix
   * @param $event        array the event to register for
   * @return array              registration result
   * @throws CiviCRM_API3_Exception
   */
  public static function createParticipant($participant, $event) {
    if (empty($participant)) {
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
      // check if the 'repeated registration' activty should be suppressed
      if (empty('dont_create_repeated_registration')) {
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
      }

    } else {
      // not there? => just create a participant object
      $participant['check_permissions'] = 0;
      $participant['event_id'] = $event['id'];
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
}