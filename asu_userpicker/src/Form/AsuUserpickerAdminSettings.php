<?php

/**
 * @file
 * Contains \Drupal\asu_userpicker\Form\AsuUserpickerAdminSettings.
 */

namespace Drupal\asu_userpicker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;

/**
 * Class AsuUserpickerAdminSettings
 * @package Drupal\asu_userpicker\Form
 */
class AsuUserpickerAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_userpicker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['asu_userpicker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $intro = '<p>The ASU Userpicker module provides an AJAX autocomplete ';
    $intro .= 'userpicker widget which allows you to search by name and ';
    $intro .= 'ASURITE ID to select users within your Drupal site as well as ';
    $intro .= 'from ASU Solr records.</p><p>If you select a user not yet in ';
    $intro .= 'your Drupal site, the userpicker creates the user when the ';
    $intro .= 'form is submitted.</p><p>This widget is easily assignable to ';
    $intro .= 'user reference fields using "Manage form display" in the Fields ';
    $intro .= 'UI.</p>';

    $form['asu_userpicker_intro'] = array(
      '#title' => $this->t('About the ASU Userpicker'),
      '#type' => 'item',
      '#markup' => $this->t($intro, array('@add_cas_user' => '/' . Url::fromRoute('user.admin_create')->getInternalPath())),
    );

    // Solr server query URL to use.
    $form['asu_userpicker_solr_query_url'] = [
      '#type' => 'textfield',
      '#default_value' => \Drupal::config('asu_userpicker.settings')->get('asu_userpicker_solr_query_url'),
      '#title' => $this->t('ASU Solr Query URL'),
      '#description' => $this->t('Provide the ASU Solr People Query URL. Probably https://asudir-solr.asu.edu/asudir/directory/select'),
      '#required' => TRUE,
    ];

    // We only want user fields, don't you know.
    $entity_type = 'user';
    $bundle = 'user';

    // Gather user entity user bundle fields.
    $field_defs = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);

    $field_options = [];
    foreach ($field_defs as $candidate) {
      if ($candidate instanceof \Drupal\field\Entity\FieldConfig) {
        $text = $this->t('@label (@field_name)', [
          '@label' => $candidate->getLabel(),
          '@field' => $candidate->getName(),
        ]);
        $field_options[$candidate->getName()] = \Drupal\Component\Utility\Unicode::truncate($text, 80, FALSE, TRUE);
      }
    }
    asort($field_options);

    // Default value is empty array. another option would be to rovide a
    // default value in config/install/asu_userpicker.settings.yml and
    // config/schema/asu_userpicker.schema.yml.
    $form['asu_userpicker_search_user_fields'] = [
      '#type' => 'checkboxes',
      '#options' => $field_options,
      '#default_value' => \Drupal::config('asu_userpicker.settings')->get('asu_userpicker_search_user_fields') ?: array(),
      '#title' => $this->t('User fields to search with ASU Userpicker widget'),
      '#description' => $this->t('In addition to select ASURITE values and local Drupal usernames and emails, the checked Drupal user fields will be consulted when using the ASU Userpicker'),
    ];

    /* @todo Explore options for re-introducing this functionality.
    $advanced_setup = '<p>It is possible when new users are created via the ';
    $advanced_setup .= 'ASU Userpicker to automatically map the user\'s first ';
    $advanced_setup .= 'and last names to the user entity. To do this, setup ';
    $advanced_setup .= 'CAS Attribute mapping to user fields:</p>';
    $advanced_setup .= '<ol><li>Navigate to the ';
    $advanced_setup .= '<a href="@cas_attr_conf">CAS Attributes admin page</a>.';
    $advanced_setup .= '<br />Set the first name field to [cas:ldap:givenname].';
    $advanced_setup .= '<br />Set the last name field to [cas:ldap:sn]. </li>';
    $advanced_setup .= '<li>Map other LDAP attributes to your user fields as ';
    $advanced_setup .= 'necessary by using the [cas:ldap:?] tokens (see ';
    $advanced_setup .= '<a href="@cas_attr_tokens">CAS attribute tokens</a>).</li>';
    $advanced_setup .= '</ol>';
    $advanced_setup .= '<p>Do not set the Username and E-mail address fields ';
    $advanced_setup .= 'as those user fields will automatically get populated ';
    $advanced_setup .= 'by the CAS module when the user first logs in.</p>';

    $form['asu_userpicker_advanced_setup'] = array(
      '#title' => $this->t('Automatic user attribute mappings'),
      '#type' => 'item',
      '#markup' => $this->t($advanced_setup, array('@cas_attr_conf' => url('admin/config/people/cas/attributes'), '@cas_attr_tokens' => url('admin/config/people/cas/attributes/ldap'))),
    );
    */

    $role_objects = Role::loadMultiple();
    $system_roles = array_combine(array_keys($role_objects), array_map(function($a){ return $a->label();}, $role_objects));
    unset($system_roles['anonymous']);

    $form['asu_userpicker_referenceable_roles'] = [
      '#type' => 'checkboxes',
      '#default_value' => \Drupal::config('asu_userpicker.settings')->get('asu_userpicker_referenceable_roles'),
      '#options' => $system_roles,
      '#title' => $this->t('Search local users with the following roles'),
      '#required' => FALSE,
    ];
    $status_options = ['active' => 'Active', 'inactive' => 'Inactive'];
    $form['asu_userpicker_referenceable_status'] = [
      '#type' => 'checkboxes',
      '#default_value' => \Drupal::config('asu_userpicker.settings')->get('asu_userpicker_referenceable_status'),
      '#options' => $status_options,
      '#title' => $this->t('Search local users with the following statuses'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('asu_userpicker.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
?>
