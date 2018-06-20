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
 * Will sanitise the API input (for RapidMiner artifacts):
 *  - drop parameters with '?' value
 */
class CRM_Aivlapi_ParameterSanitation implements API_Wrapper {
  /**
   * the wrapper contains a method that allows you to alter the parameters of the api request (including the action and the entity)
   */
  public function fromApiInput($apiRequest) {
    // remove the sentinel parameter
    if (isset($apiRequest['params']['drop_questionmarks'])) {
      unset($apiRequest['params']['drop_questionmarks']);
    }

    // drop all parameters with value '?'
    $keys = array_keys($apiRequest['params']);
    foreach ($keys as $key) {
      if ($apiRequest['params'][$key] == '?') {
        unset($apiRequest['params'][$key]);
      }
    }
    return $apiRequest;
  }

  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }
}
