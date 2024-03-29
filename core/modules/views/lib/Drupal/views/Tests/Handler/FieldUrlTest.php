<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldUrlTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the core Drupal\views\Plugin\views\field\Url handler.
 */
class FieldUrlTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Field: URL',
      'description' => 'Test the core Drupal\views\Plugin\views\field\Url handler.',
      'group' => 'Views Handlers',
    );
  }

  public function setup() {
    parent::setup();
    $this->enableModules(array('system'));
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'url';
    return $data;
  }

  public function testFieldUrl() {
    $view = views_get_view('test_view');
    $view->setDisplay();

    $view->displayHandlers['default']->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'display_as_link' => FALSE,
      ),
    ));

    $this->executeView($view);

    $this->assertEqual('John', $view->field['name']->advanced_render($view->result[0]));

    // Make the url a link.
    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers['default']->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    $this->executeView($view);

    $this->assertEqual(l('John', 'John'), $view->field['name']->advanced_render($view->result[0]));
  }

}
