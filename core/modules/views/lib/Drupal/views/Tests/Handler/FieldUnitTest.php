<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldUnitTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the generic field handler.
 *
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldUnitTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_field_tokens', 'test_field_output');

  protected $column_map = array(
    'views_test_data_name' => 'name',
  );

  public static function getInfo() {
    return array(
      'name' => 'Field: Unit Test',
      'description' => 'Tests the generic field handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableModules(array('user'));
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['job']['field']['id'] = 'test_field';
    return $data;
  }

  /**
   * Tests that the render function is called.
   */
  public function testRender() {
    $view = views_get_view('test_field_tokens');
    $this->executeView($view);

    $random_text = $this->randomName();
    $view->field['job']->setTestValue($random_text);
    $this->assertEqual($view->field['job']->theme($view->result[0]), $random_text, 'Make sure the render method rendered the manual set value.');
  }

  /**
   * Tests all things related to the query.
   */
  public function testQuery() {
    // Tests adding additional fields to the query.
    $view = views_get_view('test_view');
    $view->initHandlers();

    $id_field = $view->field['id'];
    $id_field->additional_fields['job'] = 'job';
    // Choose also a field alias key which doesn't match to the table field.
    $id_field->additional_fields['created_test'] = array('table' => 'views_test_data', 'field' => 'created');
    $view->build();

    // Make sure the field aliases have the expected value.
    $this->assertEqual($id_field->aliases['job'], 'views_test_data_job');
    $this->assertEqual($id_field->aliases['created_test'], 'views_test_data_created');

    $this->executeView($view);
    // Tests the get_value method with and without a field aliases.
    foreach ($this->dataSet() as $key => $row) {
      $id = $key + 1;
      $result = $view->result[$key];
      $this->assertEqual($id_field->get_value($result), $id);
      $this->assertEqual($id_field->get_value($result, 'job'), $row['job']);
      $this->assertEqual($id_field->get_value($result, 'created_test'), $row['created']);
    }
  }

  /**
   * Asserts that a string is part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertSubString($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos($haystack, $needle) !== FALSE, $message, $group);
  }

  /**
   * Asserts that a string is not part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotSubString($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos($haystack, $needle) === FALSE, $message, $group);
  }

  /**
   * Tests general rewriting of the output.
   */
  public function testRewrite() {
    $view = views_get_view('test_view');
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $id_field = $view->field['id'];

    // Don't check the rewrite checkbox, so the text shouldn't appear.
    $id_field->options['alter']['text'] = $random_text = $this->randomName();
    $output = $id_field->theme($row);
    $this->assertNotSubString($output, $random_text);

    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $id_field->theme($row);
    $this->assertSubString($output, $random_text);
  }

  /**
   * Tests the field tokens, row level and field level.
   */
  public function testFieldTokens() {
    $view = views_get_view('test_field_tokens');
    $this->executeView($view);
    $name_field_0 = $view->field['name'];
    $name_field_1 = $view->field['name_1'];
    $name_field_2 = $view->field['name_2'];
    $row = $view->result[0];

    $name_field_0->options['alter']['alter_text'] = TRUE;
    $name_field_0->options['alter']['text'] = '[name]';

    $name_field_1->options['alter']['alter_text'] = TRUE;
    $name_field_1->options['alter']['text'] = '[name_1] [name]';

    $name_field_2->options['alter']['alter_text'] = TRUE;
    $name_field_2->options['alter']['text'] = '[name_2] [name_1]';

    foreach ($view->result as $row) {
      $expected_output_0 = $row->views_test_data_name;
      $expected_output_1 = "$row->views_test_data_name $row->views_test_data_name";
      $expected_output_2 = "$row->views_test_data_name $row->views_test_data_name $row->views_test_data_name";

      $output = $name_field_0->advanced_render($row);
      $this->assertEqual($output, $expected_output_0);

      $output = $name_field_1->advanced_render($row);
      $this->assertEqual($output, $expected_output_1);

      $output = $name_field_2->advanced_render($row);
      $this->assertEqual($output, $expected_output_2);
    }

    $job_field = $view->field['job'];
    $job_field->options['alter']['alter_text'] = TRUE;
    $job_field->options['alter']['text'] = '[test-token]';

    $random_text = $this->randomName();
    $job_field->setTestValue($random_text);
    $output = $job_field->advanced_render($row);
    $this->assertSubString($output, $random_text, format_string('Make sure the self token (!value) appears in the output (!output)', array('!value' => $random_text, '!output' => $output)));
  }

  /**
   * Tests the exclude setting.
   */
  public function testExclude() {
    $view = views_get_view('test_field_output');
    $view->initHandlers();
    // Hide the field and see whether it's rendered.
    $view->field['name']->options['exclude'] = TRUE;

    $output = $view->preview();
    foreach ($this->dataSet() as $entry) {
      $this->assertNotSubString($output, $entry['name']);
    }

    // Show and check the field.
    $view->field['name']->options['exclude'] = FALSE;

    $output = $view->preview();
    foreach ($this->dataSet() as $entry) {
      $this->assertSubString($output, $entry['name']);
    }
  }

  /**
   * Tests everything related to empty output of a field.
   */
  function testEmpty() {
    $this->_testHideIfEmpty();
    $this->_testEmptyText();
  }

  /**
   * Tests the hide if empty functionality.
   *
   * This tests alters the result to get easier and less coupled results.
   */
  function _testHideIfEmpty() {
    $view = views_get_view('test_view');
    $view->initDisplay();
    $this->executeView($view);

    $column_map_reversed = array_flip($this->column_map);
    $view->row_index = 0;
    $random_name = $this->randomName();
    $random_value = $this->randomName();

    // Test when results are not rewritten and empty values are not hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = FALSE;
    $view->field['name']->options['empty_zero'] = FALSE;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'By default, a string should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'By default, "" should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, '0', 'By default, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'By default, "0" should not be treated as empty.');

    // Test when results are not rewritten and non-zero empty values are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If hide_empty is checked, a string should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If hide_empty is checked, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, '0', 'If hide_empty is checked, but not empty_zero, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If hide_empty is checked, but not empty_zero, "0" should not be treated as empty.');

    // Test when results are not rewritten and all empty values are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = TRUE;

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If hide_empty and empty_zero are checked, 0 should be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If hide_empty and empty_zero are checked, "0" should be treated as empty.');

    // Test when results are rewritten to a valid string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = $random_name;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_value;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If the rewritten string is not empty, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If the rewritten string is not empty, "" should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If the rewritten string is not empty, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If the rewritten string is not empty, "0" should not be treated as empty.');

    // Test when results are rewritten to an empty string and non-zero empty results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_name, 'If the rewritten string is empty, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If the rewritten string is empty, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, '0', 'If the rewritten string is empty, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If the rewritten string is empty, "0" should not be treated as empty.');

    // Test when results are rewritten to zero as a string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "0";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If the rewritten string is zero and empty_zero is not checked, the string rewritten as 0 should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If the rewritten string is zero and empty_zero is not checked, "" rewritten as 0 should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If the rewritten string is zero and empty_zero is not checked, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If the rewritten string is zero and empty_zero is not checked, "0" should not be treated as empty.');

    // Test when results are rewritten to a valid string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = $random_value;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_value, 'If the original and rewritten strings are valid, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If either the original or rewritten string is invalid, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_value, 'If the original and rewritten strings are valid, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $random_value, 'If the original and rewritten strings are valid, "0" should not be treated as empty.');

    // Test when results are rewritten to zero as a string and all empty
    // original values and results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = TRUE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "0";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If the rewritten string is zero, it should be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If the rewritten string is zero, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If the rewritten string is zero, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "", 'If the rewritten string is zero, "0" should not be treated as empty.');
  }

  /**
   * Tests the usage of the empty text.
   */
  function _testEmptyText() {
    $view = views_get_view('test_view');
    $view->initDisplay();
    $this->executeView($view);

    $column_map_reversed = array_flip($this->column_map);
    $view->row_index = 0;

    $empty_text = $view->field['name']->options['empty'] = $this->randomName();
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $empty_text, 'If a field is empty, the empty text should be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, "0", 'If a field is 0 and empty_zero is not checked, the empty text should not be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "0";
    $view->field['name']->options['empty_zero'] = TRUE;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $empty_text, 'If a field is 0 and empty_zero is checked, the empty text should be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "";
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $alter_text = $view->field['name']->options['alter']['text'] = $this->randomName();
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $alter_text, 'If a field is empty, some rewrite text exists, but hide_alter_empty is not checked, render the rewrite text.');

    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $render = $view->field['name']->advanced_render($view->result[0]);
    $this->assertIdentical($render, $empty_text, 'If a field is empty, some rewrite text exists, and hide_alter_empty is checked, use the empty text.');
  }

  /**
   * Tests views_handler_field::is_value_empty().
   */
  function testIsValueEmpty() {
    $view = views_get_view('test_view');
    $view->initHandlers();
    $field = $view->field['name'];

    $this->assertFalse($field->is_value_empty("not empty", TRUE), 'A normal string is not empty.');
    $this->assertTrue($field->is_value_empty("not empty", TRUE, FALSE), 'A normal string which skips empty() can be seen as empty.');

    $this->assertTrue($field->is_value_empty("", TRUE), '"" is considered as empty.');

    $this->assertTrue($field->is_value_empty('0', TRUE), '"0" is considered as empty if empty_zero is TRUE.');
    $this->assertTrue($field->is_value_empty(0, TRUE), '0 is considered as empty if empty_zero is TRUE.');
    $this->assertFalse($field->is_value_empty('0', FALSE), '"0" is considered not as empty if empty_zero is FALSE.');
    $this->assertFalse($field->is_value_empty(0, FALSE), '0 is considered not as empty if empty_zero is FALSE.');

    $this->assertTrue($field->is_value_empty(NULL, TRUE, TRUE), 'Null should be always seen as empty, regardless of no_skip_empty.');
    $this->assertTrue($field->is_value_empty(NULL, TRUE, FALSE), 'Null should be always seen as empty, regardless of no_skip_empty.');
  }

}
