<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\PluginBase.
 */

namespace Drupal\views\Plugin\views;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;

abstract class PluginBase extends ComponentPluginBase {

  /**
   * Options for this plugin will be held here.
   *
   * @var array
   */
  public $options = array();

  /**
   * The top object of a view.
   *
   * @var Drupal\views\ViewExecutable
   */
  public $view = NULL;

  /**
   * The display object this plugin is for.
   *
   * For display plugins this is empty.
   *
   * @todo find a better description
   *
   * @var Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $displayHandler;

  /**
   * Plugins's definition
   *
   * @var array
   */
  public $definition;

   /**
   * Denotes whether the plugin has an additional options form.
   *
   * @var bool
   */
  protected $usesOptions = FALSE;


  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->definition = $this->discovery->getDefinition($plugin_id) + $configuration;
  }

  /**
   * Information about options for all kinds of purposes will be held here.
   * @code
   * 'option_name' => array(
   *  - 'default' => default value,
   *  - 'translatable' => (optional) TRUE/FALSE (wrap in t() on export if true),
   *  - 'contains' => (optional) array of items this contains, with its own
   *      defaults, etc. If contains is set, the default will be ignored and
   *      assumed to be array().
   *  - 'bool' => (optional) TRUE/FALSE Is the value a boolean value. This will
   *      change the export format to TRUE/FALSE instead of 1/0.
   *  ),
   *
   * @return array
   *   Returns the options of this handler/plugin.
   */
  protected function defineOptions() { return array(); }

  protected function setOptionDefaults(&$storage, $options, $level = 0) {
    foreach ($options as $option => $definition) {
      if (isset($definition['contains']) && is_array($definition['contains'])) {
        $storage[$option] = array();
        $this->setOptionDefaults($storage[$option], $definition['contains'], $level++);
      }
      elseif (!empty($definition['translatable']) && !empty($definition['default'])) {
        $storage[$option] = t($definition['default']);
      }
      else {
        $storage[$option] = isset($definition['default']) ? $definition['default'] : NULL;
      }
    }
  }

  /**
   * Unpack options over our existing defaults, drilling down into arrays
   * so that defaults don't get totally blown away.
   */
  public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE) {
    if ($check && !is_array($options)) {
      return;
    }

    if (!isset($definition)) {
      $definition = $this->defineOptions();
    }

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        // Ignore arrays with no definition.
        if (!$all && empty($definition[$key])) {
          continue;
        }

        if (!isset($storage[$key]) || !is_array($storage[$key])) {
          $storage[$key] = array();
        }

        // If we're just unpacking our known options, and we're dropping an
        // unknown array (as might happen for a dependent plugin fields) go
        // ahead and drop that in.
        if (!$all && isset($definition[$key]) && !isset($definition[$key]['contains'])) {
          $storage[$key] = $value;
          continue;
        }

        $this->unpackOptions($storage[$key], $value, isset($definition[$key]['contains']) ? $definition[$key]['contains'] : array(), $all, FALSE);
      }
      else if ($all || !empty($definition[$key])) {
        $storage[$key] = $value;
      }
    }
  }

  /**
   * Clears a plugin.
   */
  public function destroy() {
    unset($this->view, $this->display, $this->query);
  }

  /**
   * Init will be called after construct, when the plugin is attached to a
   * view and a display.
   */

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the form_state['values'] hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = 'views_ui_pre_render_add_fieldset_markup';
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * Add anything to the query that we might need to.
   */
  public function query() { }

  /**
   * Provide a full list of possible theme templates used by this style.
   */
  public function themeFunctions() {
    return views_theme_functions($this->definition['theme'], $this->view, $this->view->display_handler->display);
  }

  /**
   * Validate that the plugin is correct and can be saved.
   *
   * @return
   *   An array of error strings to tell the user what is wrong with this
   *   plugin.
   */
  public function validate() { return array(); }

  /**
   * Returns the summary of the settings in the display.
   */
  public function summaryTitle() {
    return t('Settings');
  }

  /**
   * Return the human readable name of the display.
   *
   * This appears on the ui beside each plugin and beside the settings link.
   */
  public function pluginTitle() {
    if (isset($this->definition['short_title'])) {
      return check_plain($this->definition['short_title']);
    }
    return check_plain($this->definition['title']);
  }

  /**
   * Returns the usesOptions property.
   */
  public function usesOptions() {
    return $this->usesOptions;
  }

  /**
   * Returns a string with any core tokens replaced.
   *
   * @param string $string
   *   The string to preform the token replacement on.
   * @param array $options
   *   An array of options, as passed to token_replace.
   *
   * @return string
   *   The tokenized string.
   */
  public function globalTokenReplace($string = '', array $options = array()) {
    return token_replace($string, array('view' => $this->view), $options);
  }

  /**
   * Returns an array of available token replacements.
   *
   * @param bool $prepared
   *   Whether to return the raw token info for each token or an array of
   *   prepared tokens for each type. E.g. "[view:name]".
   * @param array $types
   *   An array of additional token types to return, defaults to 'site' and
   *   'view'.
   *
   * @return array
   *   An array of available token replacement info or tokens, grouped by type.
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = array()) {
    $info = token_info();
    // Site and view tokens should always be available.
    $types += array('site', 'view');
    $available = array_intersect_key($info['tokens'], array_flip($types));

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = array();
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

  /**
   * Adds elements for available core tokens to a form.
   *
   * @param array $form
   *   The form array to alter, passed by reference.
   * @param array $form_state
   *   The form state array to alter, passed by reference.
   */
  public function globalTokenForm(&$form, &$form_state) {
    $token_items = array();

    foreach ($this->getAvailableGlobalTokens() as $type => $tokens) {
      $item = array(
        '#markup' => $type,
        'children' => array(),
      );
      foreach ($tokens as $name => $info) {
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . ': ' . $info['description'];
      }

      $token_items[$type] = $item;
    }

    $form['global_tokens'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available global token replacements'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['global_tokens']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => array(
        'class' => array('global-tokens'),
      ),
    );
  }

}
