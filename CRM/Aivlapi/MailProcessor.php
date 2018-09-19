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
class CRM_Aivlapi_MailProcessor {

  /**
   * Get all (active) contacts with the given email address
   *
   * @return array of contacts
   */
  public static function getContacts(&$params) {
    $contacts = array();
    if (empty($params['email'])) {
      return $contacts;
    }

    // sanitise
    $params['email'] = trim($params['email']);

    // first, get all email entities
    $contact_ids = array();
    $email_query = civicrm_api3('Email', 'get', array(
        'check_permissions' => 0,
        'email'             => $params['email'],
        'option.limit'      => 0,
        'return'            => 'contact_id'));
    foreach ($email_query['values'] as $email) {
      $contact_ids[] = $email['contact_id'];
    }
    if (empty($contact_ids)) {
      return $contacts;
    }

    // now find all active contacts
    $contact_query = civicrm_api3('Contact', 'get', array(
        'check_permissions' => 0,
        'id'                => array('IN' => $contact_ids),
        'option.limit'      => 0,
        'is_deleted'        => 0,
        'return'            => 'id,display_name'));
    foreach ($contact_query['values'] as $contact) {
      $contacts[] = $contact;
    }

    return $contacts;
  }

  /**
   * Unsubscribe all given contacts
   */
  public static function optoutContacts($contacts, $params) {
    foreach ($contacts as $contact) {
      if (empty($contact['is_opt_out'])) {
        // not set yet:
        civicrm_api3('Contact', 'create', array(
            'check_permissions' => 0,
            'id'                => $contact['id'],
            'is_opt_out'        => 1));
      }
    }
  }

  /**
   * send an opt-out email to everybody in the contact list
   */
  public static function sendOptOutMail($contacts, $params) {
    if (empty($contacts)) {
      // no contacts -> nothing to do
      return;
    }

    if (empty($params['template_id']) || !is_numeric($params['template_id'])) {
      // no template -> nothing to do
      return;
    }

    // compile send params
    $optout_confirmation = array(
        'check_permissions' => 0,
        'id'                => (int) $params['template_id'],
        'contact_id'        => $contacts[0]['id'],
        'to_name'           => $contacts[0]['display_name'],
        'to_email'          => $params['email'],
        'from'              => 'info@amnesty-international.be',
        'reply_to'          => "do-not-reply@amnesty-international.be",
    );

    // add bcc if any
    if (!empty($params['bcc'])) {
      $optout_confirmation['bcc'] = $params['bcc'];
    }

    // send the thing
    return civicrm_api3('MessageTemplate', 'send', $optout_confirmation);
  }
}