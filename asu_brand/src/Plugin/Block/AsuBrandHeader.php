<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Entity\Menu;

/**
 * Provides an ASU header block.
 *
 * @Block(
 *  id = "asu_brand_header",
 *  admin_label = @Translation("ASU Brand: Header"),
 * )
 */
class AsuBrandHeader extends AsuBrandBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'menu_injection_flag' => 1,
      'menu_name' => ASU_BRAND_SITE_MENU_NAME_DEFAULT,
      'asu_gtm' => 0,
      'custom_gtm' => 0,
      'custom_gtm_id' => '',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['site_menu'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site menu injection'),
      '#collapsed' => FALSE,
      '#weight' => 4,
    ];

    $form['site_menu']['menu_injection_flag'] = [
      '#type' => 'checkbox',
      '#title' => t('Append local site menu into ASU header menu and display in responsive state.'),
      '#default_value' =>  $this->configuration['menu_injection_flag'],
    ];

    $form['site_menu']['menu_name'] = [
      '#type' => 'select',
      '#title' => t('Menu to inject'),
      '#description' => t('Select the site menu to inject.'),
      '#options' => $this->get_menus(),
      '#default_value' => $this->configuration['menu_name'],
      '#states' => [
        'visible' => [
          ':input[name="settings[site_menu][menu_injection_flag]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['gtm'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Tag Manager (GTM)'),
      '#collapsed' => FALSE,
      '#weight' => 4,
    ];

    $form['gtm']['asu_gtm'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable ASU GTM.'),
      '#default_value' =>  $this->configuration['asu_gtm'],
    ];

    $form['gtm']['custom_gtm'] = [
      '#type' => 'checkbox',
      '#title' => t('Use a custom GTM in addtion to or instead on ASU GTM.'),
      '#default_value' =>  $this->configuration['custom_gtm'],
    ];

    $form['gtm']['custom_gtm_id'] = [
      '#type' => 'textfield',
      '#title' => t('Enter account Id for custom GTM.'),
      '#default_value' =>  $this->configuration['custom_gtm_id'],
      '#states' => [
        'visible' => [
          ':input[name="settings[gtm][custom_gtm]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $custom_gtm = $form_state->getValue(['gtm', 'custom_gtm']);
    $custom_gtm_id = $form_state->getValue(['gtm', 'custom_gtm_id']);

    if ($custom_gtm && empty($custom_gtm_id)) {
      $form_state->setError($form['gtm']['custom_gtm_id'],
        $this->t("Invalid GTM id. The custom GTM id can't be empty."));
    }
    else if($custom_gtm && !ctype_alnum(str_replace('-', '', $custom_gtm_id))) {
      $form_state->setError($form['gtm']['custom_gtm_id'],
        $this->t("Invalid GTM id. Only alphanumeric characters and a hyphen are allowed."));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['menu_injection_flag'] = $form_state->getValue(['site_menu', 'menu_injection_flag']);
    $this->configuration['menu_name'] = $form_state->getValue(['site_menu', 'menu_name']);
    $this->configuration['asu_gtm'] = $form_state->getValue(['gtm', 'asu_gtm']);
    $this->configuration['custom_gtm'] = $form_state->getValue(['gtm', 'custom_gtm']);
    $this->configuration['custom_gtm_id'] = $form_state->getValue(['gtm', 'custom_gtm_id']);
  }

  /**
   * Build the ASU Header block by following the steps below:
   *
   * - Load CSS and JS header assets from www.asu.edu/asuthemes.
   * - Inject inline javascript settings for ASUHeader (e.g. sso_signout_url).
   * - Optionally inject the mobile navigation menu settings.
   * - Build ASU Header block by fetching the HTML code from www.asu.edu/asuthemes.
   *
   * NOTE: The header block is automatically cached as long as the
   * Dynamic Page Caching (DPC) core module is enabled. There used
   *
   * {@inheritdoc}
   */
  public function build() {

    $basepath = ASU_BRAND_HEADER_BASEPATH_DEFAULT;
    $version = ASU_BRAND_HEADER_VERSION_DEFAULT;
    $template_key = ASU_BRAND_HEADER_TEMPLATE_DEFAULT;
    $js_settings = $this->getJsSettings();
    $inject_menu = $this->configuration['menu_injection_flag'];
    $asu_gtm = $this->configuration['asu_gtm'];
    $custom_gtm = $this->configuration['custom_gtm'];
    $custom_gtm_id = $this->configuration['custom_gtm_id'];

    $uri = "{$basepath}/{$version}/headers/{$template_key}.shtml";

    // Load CSS and JS header assets from www.asu.edu/asuthemes.
    $build['#attached']['library'][] = 'asu_brand/header';

    // Inject inline javascript settings for ASUHeader.
    $build['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => "
var ASUHeader = ASUHeader || {};
ASUHeader.browser = false;
ASUHeader.user_signedin = {$js_settings['asu_sso_signedin']};
ASUHeader.signin_url = '{$js_settings['asu_sso_signinurl']}';
ASUHeader.signout_url = '{$js_settings['asu_sso_signouturl']}';
",
      ],
      'asu-brand-header-inject-js-settings',
    ];

    // Inject mobile menu
    if ($inject_menu) {
      $menu_name = $this->configuration['menu_name'];
      $menu_items = $this->get_menu_items($menu_name);
      $site_name = \Drupal::config('system.site')->get('name');
      $build['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => "
ASUHeader.site_menu = ASUHeader.site_menu || {};
ASUHeader.site_menu.json = '" . json_encode($menu_items, JSON_HEX_APOS) . "';
ASUHeader.site_menu.site_name = '" . json_encode($site_name, JSON_HEX_APOS) . "';
",
        ],
        'asu-brand-header-inject-mobile-menu',
      ];
    }

    // Inject ASU GTM
    if ($asu_gtm) {
      $build['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => "
  (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','SI_dataLayer','" . ASU_BRAND_GTM_ID . "');
  ",
        ],
        'asu-brand-header-inject-asu-gtm',
      ];
    }

    // Inject custom GTM
    if ($custom_gtm) {
      $build['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => "
  (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','SI_dataLayer','" . $custom_gtm_id . "');
  ",
        ],
        'asu-brand-header-inject-custom-gtm',
      ];
    }

    $build['header'] = [
      '#type' => 'inline_template',
      '#template' => '{{ html | raw }}',
      '#context' => [
        'html' => $this->fetchExternalMarkUp($uri),
      ]
    ];

    return $build;
  }

  /**
   * Get ASU brand block settings.
   */
  private function getJsSettings() {

    $is_user_logged_in = TRUE;
    $moduleHandler = \Drupal::service('module_handler');

    if (\Drupal::currentUser()->isAnonymous()) {
      $is_user_logged_in = FALSE;
    }

    // Set javascript settings.
    $js_settings = [
      'asu_sso_signedin' => ($is_user_logged_in ? 'true' : 'false'),
      'asu_sso_signinurl' => '',
      'asu_sso_signouturl' => '',
    ];

    // NOTE: Since we're currently relying on Drupal core's Dynamic Page Cache module
    // (enabled by default in most sites) to cache ASU Brand blocks, appending a destination query
    // to the Sing In path won't work correctly. This is because the path with
    // the destination query will be cached, and it will be the same on all pages,
    // which is not desired.

    // Alter the signin/signout URL if cas is enabled.
    if ($moduleHandler->moduleExists('cas')){
      $cas_sign_in_path = \Drupal::config('cas.settings')->get('server.path');
      $js_settings['asu_sso_signinurl'] = Url::fromUserInput($cas_sign_in_path, ['absolute' => TRUE, 'https' => TRUE])->toString();
      $js_settings['asu_sso_signouturl'] = Url::fromUserInput('/caslogout', ['absolute' => TRUE])->toString();
    } else {
      $js_settings['asu_sso_signinurl'] = Url::fromUserInput('/user/login', ['absolute' => TRUE])->toString();
      $js_settings['asu_sso_signouturl'] = Url::fromUserInput('/user/logout', ['absolute' => TRUE])->toString();
    }

    return $js_settings;
  }

  /**
   * Get a list of menus.
   *
   * @return array Associative array of menus.
   */
  private function get_menus() {
    $all_menus = Menu::loadMultiple();
    $menus = [];
    foreach ($all_menus as $id => $menu) {
      $menus[$id] = $menu->label();
    }

    return $menus;
  }

  /**
   * Pass a menu name and get a list of menu links.
   *
   * @param string $menu_name Menu machine name.
   * @return array Associative array of menu items.
   */
  private function get_menu_items($menu_name) {
    $menu_tree = \Drupal::menuTree();
    // Build the typical default set of menu tree parameters.
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth(3);
    // Load the tree based on this set of parameters.
    $tree = $menu_tree->load($menu_name, $parameters);
    // Transform the tree using the manipulators you want.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    // Finally, build a renderable array from the transformed tree.
    $menu_tmp = $menu_tree->build($tree);
    $menu = [];
    foreach ($menu_tmp['#items'] as $item) {
      $top_level = $this->get_menu_item($item);
      if (!empty($item['below'])) {
        foreach ($item['below'] as $child) {
          $second_level = $this->get_menu_item($child);
          if (!empty($child['below'])) {
            foreach ($child['below'] as $grandchild) {
              $second_level['children'][] = $this->get_menu_item($grandchild);
            }
          }
          $top_level['children'][] = $second_level;
        }
      }
      $menu[] = $top_level;
    }
    return $menu;
  }

  /**
   * Compose and return menu item
   *
   * @param array $item
   * @return array $menu_item
   */
  function get_menu_item($item) {

    $menu_item = ['title' => $item['title'], 'path' => $item['url']->toString()];

    return $menu_item;

  }

}
