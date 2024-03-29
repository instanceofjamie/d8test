<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsFieldUITest.
 */

namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Options module UI tests.
 */
class OptionsFieldUITest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('options', 'field_test', 'taxonomy', 'field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Options field UI',
      'description' => 'Test the Options field UI functionality.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer taxonomy'));
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = 'test_' . strtolower($this->randomName());
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->type;
  }

  /**
   * Options (integer) : test 'allowed values' input.
   */
  function testOptionsAllowedValuesInteger() {
    $this->field_name = 'field_options_integer';
    $this->createOptionsField('list_integer');

    // Flat list of textual values.
    $string = "Zero\nOne";
    $array = array('0' => 'Zero', '1' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Unkeyed lists are accepted.');
    // Explicit integer keys.
    $string = "0|Zero\n2|Two";
    $array = array('0' => 'Zero', '2' => 'Two');
    $this->assertAllowedValuesInput($string, $array, 'Integer keys are accepted.');
    // Check that values can be added and removed.
    $string = "0|Zero\n1|One";
    $array = array('0' => 'Zero', '1' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added and removed.');
    // Non-integer keys.
    $this->assertAllowedValuesInput("1.1|One", 'keys must be integers', 'Non integer keys are rejected.');
    $this->assertAllowedValuesInput("abc|abc", 'keys must be integers', 'Non integer keys are rejected.');
    // Mixed list of keyed and unkeyed values.
    $this->assertAllowedValuesInput("Zero\n1|One", 'invalid input', 'Mixed lists are rejected.');

    // Create a node with actual data for the field.
    $settings = array(
      'type' => $this->type,
      $this->field_name => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => 1))),
    );
    $node = $this->drupalCreateNode($settings);

    // Check that a flat list of values is rejected once the field has data.
    $this->assertAllowedValuesInput( "Zero\nOne", 'invalid input', 'Unkeyed lists are rejected once the field has data.');

    // Check that values can be added but values in use cannot be removed.
    $string = "0|Zero\n1|One\n2|Two";
    $array = array('0' => 'Zero', '1' => 'One', '2' => 'Two');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added.');
    $string = "0|Zero\n1|One";
    $array = array('0' => 'Zero', '1' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
    $this->assertAllowedValuesInput("0|Zero", 'some values are being removed while currently in use', 'Values in use cannot be removed.');

    // Delete the node, remove the value.
    node_delete($node->nid);
    $string = "0|Zero";
    $array = array('0' => 'Zero');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
  }

  /**
   * Options (float) : test 'allowed values' input.
   */
  function testOptionsAllowedValuesFloat() {
    $this->field_name = 'field_options_float';
    $this->createOptionsField('list_float');

    // Flat list of textual values.
    $string = "Zero\nOne";
    $array = array('0' => 'Zero', '1' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Unkeyed lists are accepted.');
    // Explicit numeric keys.
    $string = "0|Zero\n.5|Point five";
    $array = array('0' => 'Zero', '0.5' => 'Point five');
    $this->assertAllowedValuesInput($string, $array, 'Integer keys are accepted.');
    // Check that values can be added and removed.
    $string = "0|Zero\n.5|Point five\n1.0|One";
    $array = array('0' => 'Zero', '0.5' => 'Point five', '1' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added and removed.');
    // Non-numeric keys.
    $this->assertAllowedValuesInput("abc|abc\n", 'each key must be a valid integer or decimal', 'Non numeric keys are rejected.');
    // Mixed list of keyed and unkeyed values.
    $this->assertAllowedValuesInput("Zero\n1|One\n", 'invalid input', 'Mixed lists are rejected.');

    // Create a node with actual data for the field.
    $settings = array(
      'type' => $this->type,
      $this->field_name => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => .5))),
    );
    $node = $this->drupalCreateNode($settings);

    // Check that a flat list of values is rejected once the field has data.
    $this->assertAllowedValuesInput("Zero\nOne", 'invalid input', 'Unkeyed lists are rejected once the field has data.');

    // Check that values can be added but values in use cannot be removed.
    $string = "0|Zero\n.5|Point five\n2|Two";
    $array = array('0' => 'Zero', '0.5' => 'Point five', '2' => 'Two');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added.');
    $string = "0|Zero\n.5|Point five";
    $array = array('0' => 'Zero', '0.5' => 'Point five');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
    $this->assertAllowedValuesInput("0|Zero", 'some values are being removed while currently in use', 'Values in use cannot be removed.');

    // Delete the node, remove the value.
    node_delete($node->nid);
    $string = "0|Zero";
    $array = array('0' => 'Zero');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
  }

  /**
   * Options (text) : test 'allowed values' input.
   */
  function testOptionsAllowedValuesText() {
    $this->field_name = 'field_options_text';
    $this->createOptionsField('list_text');

    // Flat list of textual values.
    $string = "Zero\nOne";
    $array = array('Zero' => 'Zero', 'One' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Unkeyed lists are accepted.');
    // Explicit keys.
    $string = "zero|Zero\none|One";
    $array = array('zero' => 'Zero', 'one' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Explicit keys are accepted.');
    // Check that values can be added and removed.
    $string = "zero|Zero\ntwo|Two";
    $array = array('zero' => 'Zero', 'two' => 'Two');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added and removed.');
    // Mixed list of keyed and unkeyed values.
    $string = "zero|Zero\nOne\n";
    $array = array('zero' => 'Zero', 'One' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Mixed lists are accepted.');
    // Overly long keys.
    $this->assertAllowedValuesInput("zero|Zero\n" . $this->randomName(256) . "|One", 'each key must be a string at most 255 characters long', 'Overly long keys are rejected.');

    // Create a node with actual data for the field.
    $settings = array(
      'type' => $this->type,
      $this->field_name => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => 'One'))),
    );
    $node = $this->drupalCreateNode($settings);

    // Check that flat lists of values are still accepted once the field has
    // data.
    $string = "Zero\nOne";
    $array = array('Zero' => 'Zero', 'One' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Unkeyed lists are still accepted once the field has data.');

    // Check that values can be added but values in use cannot be removed.
    $string = "Zero\nOne\nTwo";
    $array = array('Zero' => 'Zero', 'One' => 'One', 'Two' => 'Two');
    $this->assertAllowedValuesInput($string, $array, 'Values can be added.');
    $string = "Zero\nOne";
    $array = array('Zero' => 'Zero', 'One' => 'One');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
    $this->assertAllowedValuesInput("Zero", 'some values are being removed while currently in use', 'Values in use cannot be removed.');

    // Delete the node, remove the value.
    node_delete($node->nid);
    $string = "Zero";
    $array = array('Zero' => 'Zero');
    $this->assertAllowedValuesInput($string, $array, 'Values not in use can be removed.');
  }

  /**
   * Options (boolen) : test 'On/Off' values input.
   */
  function testOptionsAllowedValuesBoolean() {
    $this->field_name = 'field_options_boolean';
    $this->createOptionsField('list_boolean');

    // Check that the separate 'On' and 'Off' form fields work.
    $on = $this->randomName();
    $off = $this->randomName();
    $allowed_values = array(1 => $on, 0 => $off);
    $edit = array(
      'on' => $on,
      'off' => $off,
    );
    $this->drupalPost($this->admin_path, $edit, t('Save field settings'));
    $this->assertRaw(t('Updated field %label field settings.', array('%label' => $this->field_name)));
    // Test the allowed_values on the field settings form.
    $this->drupalGet($this->admin_path);
    $this->assertFieldByName('on', $on, t("The 'On' value is stored correctly."));
    $this->assertFieldByName('off', $off, t("The 'Off' value is stored correctly."));
    $field = field_info_field($this->field_name);
    $this->assertEqual($field['settings']['allowed_values'], $allowed_values, 'The allowed value is correct');
    $this->assertFalse(isset($field['settings']['on']), 'The on value is not saved into settings');
    $this->assertFalse(isset($field['settings']['off']), 'The off value is not saved into settings');
  }

  /**
   * Helper function to create list field of a given type.
   *
   * @param string $type
   *   'list_integer', 'list_float', 'list_text' or 'list_boolean'
   */
  protected function createOptionsField($type) {
    // Create a test field and instance.
    $field = array(
      'field_name' => $this->field_name,
      'type' => $type,
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => $this->type,
    );
    field_create_instance($instance);

    $this->admin_path = 'admin/structure/types/manage/' . $this->type . '/fields/' . $this->field_name . '/field-settings';
  }

  /**
   * Tests a string input for the 'allowed values' form element.
   *
   * @param $input_string
   *   The input string, in the pipe-linefeed format expected by the form
   *   element.
   * @param $result
   *   Either an expected resulting array in
   *   $field['settings']['allowed_values'], or an expected error message.
   * @param $message
   *   Message to display.
   */
  function assertAllowedValuesInput($input_string, $result, $message) {
    $edit = array('field[settings][allowed_values]' => $input_string);
    $this->drupalPost($this->admin_path, $edit, t('Save field settings'));

    if (is_string($result)) {
      $this->assertText($result, $message);
    }
    else {
      field_info_cache_clear();
      $field = field_info_field($this->field_name);
      $this->assertIdentical($field['settings']['allowed_values'], $result, $message);
    }
  }
}
