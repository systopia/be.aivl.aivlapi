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

use CRM_Aivlapi_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Aivlapi_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {

    $this->add(
        'checkbox',
        'debug',
        E::ts('Debug Logging')
    );

    $this->add(
        'text',
        'fallback_contact_id',
        E::ts('Fallback API User Contact ID'),
        [],
        TRUE
    );


    $this->add(
        'select',
        'membership_type_id',
        E::ts('Membership Type'),
        $this->getList('MembershipType', 'id', 'name'),
        TRUE
    );

    $this->add(
        'select',
        'membership_error_activity_type_id',
        E::ts('Membership Error Activity'),
        $this->getList('OptionValue', 'value', 'label', ['option_group_id' => 'activity_type']),
        TRUE
    );

    $this->add(
        'select',
        'membership_signoflive_activity_type_id',
        E::ts('Membership Sign-of-Live Activity'),
        $this->getList('OptionValue', 'value', 'label', ['option_group_id' => 'activity_type']),
        TRUE
    );

    $this->add(
        'text',
        'membership_error_assignee_id',
        E::ts('Membership Error Assignee Contact ID'),
        [],
        TRUE
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    $current_values = CRM_Core_BAO_Setting::getItem('be.aivl.aivlapi', 'aivlapi_config');
    $this->setDefaults($current_values);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    CRM_Core_BAO_Setting::setItem($values,'be.aivl.aivlapi', 'aivlapi_config');
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Generate a dropdown list of arbitrary entites
   *
   * @param $entity       string entity name
   * @param $id_field     string name of the field to be used as ID
   * @param $label_field  string name of the field to be used as label
   * @param $params       array  additional parameters for the query
   *
   * @return array list
   * @throws API_Exception
   */
  protected function getList($entity, $id_field = 'id', $label_field = 'name', $params = []) {
    $list = [];
    $params['return'] = "{$id_field},{$label_field}";
    $params['option.limit'] = 0;
    $results = civicrm_api3($entity, 'get', $params);
    foreach ($results['values'] as $key => $entry) {
      $list[$entry[$id_field]] = $entry[$label_field];
    }
    return $list;
  }
}
