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
 * Offers membership API processing functions
 *
 * @see https://civicoop.plan.io/issues/3478
 */
class CRM_Aivlapi_MembershipProcessor {

  /**
   * Will extend the membership:
   *  - extend the membership by one year
   *  - create a "Sign of Life" (tbd) activity
   *  - sign the contact up for any petitions
   *  - apply the contact changes (I3Val?)
   *
   * @param $params array parameters
   * @return array API result
   *
   * @throws CiviCRM_API3_Exception
   */
  public static function extendMembership($params) {
    $passed_contact_id = empty($params['contact_id']) ? NULL : $params['contact_id'];
    $contact_id = CRM_Aivlapi_Processor::getContactID($params);

    // first, perform some minor tasks
    try {
      // create a "Sign of Life" (tbd) activity
      $activity_type_id = CRM_Aivlapi_Configuration::getSetting('membership_signoflive_activity_type_id');
      if ($activity_type_id) {
        $subject = CRM_Aivlapi_Configuration::getSetting('membership_signoflive_activity_subject');
        if (empty($subject)) {
          $subject = "Ik blijf lid";
        }
        CRM_Aivlapi_ActivityProcessor::createFullActivity($contact_id, $activity_type_id, $subject);
      }

      // sign the contact up for any petitions
      $result = CRM_Aivlapi_PetitionProcessor::signPetitions($params);
      if (empty($result['error'])) {
        CRM_Core_Error::debug_log_message("'AivlMembership.feedback': Signed {$result['counter_signed']} petitions, {$result['counter_already']} were already signed.");
      } else {
        CRM_Core_Error::debug_log_message("'AivlMembership.feedback': {$result['error']}");
      }

      // apply the contact changes
      if ($passed_contact_id) {
        $params['id'] = $passed_contact_id;
        $params['check_permissions'] = 0;
        civicrm_api3('Contact', 'request_update', $params);
      }
    } catch (Exception $ex) {
      CRM_Core_Error::debug_log_message("'AivlMembership.feedback': Error: " . $ex->getMessage());
    }

    // NOW: for the membership:
    $membership = self::getMembership($contact_id);
    if ($membership) {
      // extend the membership by one year:
      //  calculate end date (last day of the month)
      $end_date = strtotime($membership['end_date']);          // parse current end date
      if ($end_date < strtotime('now')) {
        // only extend, if it's in the past
        $end_date = strtotime("+1 year", $end_date);        // add one year
        $end_date = strtotime("+1 month", $end_date);       // move to the next month
        $end_date = strtotime(date('Y-m-01', $end_date)); // move to the FRIST of next month
        $end_date = strtotime("-1 day", $end_date);         // back one day => result: last day of that month

        // update the membership
        civicrm_api3('Membership', 'create', [
            'id'                 => $membership['id'],
            'end_date'           => date('Ymd', $end_date),
            'membership_type_id' => $membership['membership_type_id'],
            'contact_id'         => $membership['contact_id']
        ]);
      }

    } else {
      $activity_type_id = CRM_Aivlapi_Configuration::getSetting('membership_error_activity_type_id', 1);
      $assignee_id = CRM_Aivlapi_Configuration::getSetting('membership_error_assignee_id', 1);
      CRM_Aivlapi_ActivityProcessor::createFullActivity($contact_id, $activity_type_id, "Membership cannot be extended: not found!", 'Scheduled', $assignee_id);
      return civicrm_api3_create_error("Membership cannot be extended: not found!");
    }

    return civicrm_api3_create_success();
  }

  /**
   * Will stop the membership:
   *  - set membership to "cancelled", and the membership end date to the current date
   *  - store the reason?
   *  - create an activity?
   *
   * @param $params array parameters
   * @return array API result
   *
   * @throws CiviCRM_API3_Exception
   */
  public static function stopMembership($params) {
    $contact_id = CRM_Aivlapi_Processor::getContactID($params);
    $membership = self::getMembership($contact_id);

    if ($membership) {
      // set membership to "cancelled", and the membership end date to the current date
      // TODO: store the reason?
      civicrm_api3('Membership', 'create', [
          'id'                 => $membership['id'],
          'status_id'          => 'Cancelled',
          'membership_type_id' => $membership['membership_type_id'],
          'contact_id'         => $membership['contact_id'],
          'end_date'           => date('YmdHis')
      ]);

      // create an acitivity?
      // TODO

    } else {
      $activity_type_id = CRM_Aivlapi_Configuration::getSetting('membership_error_activity_type_id', 1);
      $assignee_id = CRM_Aivlapi_Configuration::getSetting('membership_error_assignee_id', 1);
      CRM_Aivlapi_ActivityProcessor::createFullActivity($contact_id, $activity_type_id, "Membership cannot be stopped: not found!", 'Scheduled', $assignee_id);
      return civicrm_api3_create_error("Membership cannot be stopped: not found!");
    }

    return civicrm_api3_create_success();
  }

  /**
   * Get the current membership of type 'Lid' from the given contact
   *
   * @param $contact_id int Contact ID
   * @return array|null the membership data, if one is found
   *
   * @throws CiviCRM_API3_Exception
   */
  protected static function getMembership($contact_id) {
    // get current status IDs
    $status_query = civicrm_api3('MembershipStatus', 'get', [
        'sequential'        => 0,
        'is_current_member' => 1]);

    $membership = civicrm_api3('Membership', 'get', [
        'status_id'          => ['IN' => array_keys($status_query['values'])],
        'contact_id'         => $contact_id,
        'membership_type_id' => CRM_Aivlapi_Configuration::getSetting('membership_type_id'),
    ]);

    if (empty($membership['id'])) {
      return NULL;
    } else {
      return reset($membership['values']);
    }
  }
}