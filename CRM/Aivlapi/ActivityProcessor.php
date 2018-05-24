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
 * Offers relationship API processing functions
 */
class CRM_Aivlapi_ActivityProcessor {

  /**
   * Create or update a relationship (if requested)
   *
   * @param $activity_data      array relationship data
   * @param $contactA           array contact 1 data (with field 'contact_id')
   * @param $contactB           array contact 2 data (with field 'contact_id')
   * @return array Activity.create result
   * @throws CiviCRM_API3_Exception
   */
  public static function createActivity($activity_data, $contactA, $contactB) {
    if (empty($activity_data) || empty($contactA['contact_id'])) {
      // not enough data
      return array();
    }

    $activity_data['check_permissions'] = 0;
    if (empty($contactB['contact_id'])) {
      $activity_data['target_contact_id'] = $contactA['contact_id'];
    } else {
      $activity_data['target_contact_id'] = array($contactA['contact_id'], $contactB['contact_id']);
    }

    if (empty($activity_data['activity_date_time'])) {
      $activity_data['activity_date_time'] = date('YmdHis');
    }

    return civicrm_api3('Activity', 'create', $activity_data);
  }
}