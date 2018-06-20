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
class CRM_Aivlapi_RelationshipProcessor {

  /**
   * Create or update a relationship (if requested)
   *
   * @param $relationship_data  array relationship data
   * @param $contactA           array contact 1 data (with field 'contact_id')
   * @param $contactB           array contact 2 data (with field 'contact_id')
   * @throws CiviCRM_API3_Exception
   */
  public static function processRelationship($relationship_data, $contactA, $contactB) {
    if (empty($contactA['contact_id']) || empty($contactB['contact_id'])) {
      // no contacts to relate...
      return;
    }

    if (empty($relationship_data['relationship_type_id'])) {
      // no relation to create
      return;
    }

    // Does the relationship has a life_time_days?
    if (!empty($relationship_data['life_time_days'])) {
      $life_time_days = (int) $relationship_data['life_time_days'];
      $new_end_date = date("YmdHis", strtotime("+{$life_time_days} days"));
    } else {
      $new_end_date = NULL;
    }

    // load all active relationships for these contacts
    $relationships = civicrm_api3('Relationship', 'get', array(
        'check_permissions'    => 0,
        'relationship_type_id' => $relationship_data['relationship_type_id'],
        'contact_id_a'         => $contactA['contact_id'],
        'contact_id_b'         => $contactB['contact_id'],
        'option.limit'         => 0,
    ));

    // see if we can find a relationship to update
    foreach ($relationships['values'] as $relationship) {
      if (empty($relationship['end_date'])) {
        // this is active AND has no end date => we're done!
        return;
      }

      $end_date = date('YmdHis', strtotime($relationship['end_date']));
      if ($end_date < $new_end_date) {
        // simply extend the relationship
        civicrm_api3('Relationship', 'create', array(
            'check_permissions' => 0,
            'id'                => $relationship['id'],
            'end_date'          => $new_end_date));
      }

      // either way, we're done
      return;
    }

    // if we get here, we should create a new relationship
    $relationship_data['check_permissions'] = 0;
    $relationship_data['contact_id_a']      = $contactA['contact_id'];
    $relationship_data['contact_id_b']      = $contactB['contact_id'];
    $relationship_data['start_date']        = date('YmdHis');
    if ($new_end_date) {
      $relationship_data['end_date'] = $new_end_date;
    }
    civicrm_api3('Relationship', 'create', $relationship_data);
  }
}