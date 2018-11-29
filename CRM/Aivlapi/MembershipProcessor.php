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
    $contact_id = CRM_Aivlapi_Processor::getContactID($params);
    $membership = self::getMembership($contact_id);

    if ($membership) {
      // TODO
    } else {
      $activity_type_id = CRM_Aivlapi_Configuration::getSetting('membership_error_activity_type_id', 1);
      $assignee_id = CRM_Aivlapi_Configuration::getSetting('membership_error_assignee_id', 1);
      CRM_Aivlapi_ActivityProcessor::createErrorActivity($contact_id, $activity_type_id, "Membership cannot be extended: not found!", $assignee_id);
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
      // TODO
    } else {
      $activity_type_id = CRM_Aivlapi_Configuration::getSetting('membership_error_activity_type_id', 1);
      $assignee_id = CRM_Aivlapi_Configuration::getSetting('membership_error_assignee_id', 1);
      CRM_Aivlapi_ActivityProcessor::createErrorActivity($contact_id, $activity_type_id, "Membership cannot be stopped: not found!", $assignee_id);
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
   */
  protected static function getMembership($contact_id) {
    // TODO:
    return null;
  }
}