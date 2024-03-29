<?php

/**
 * @file
 * Definition of Drupal\simpletest\Tests\BrowserTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test internal testing framework browser.
 */
class BrowserTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'SimpleTest browser',
      'description' => 'Test the internal browser of the testing framework.',
      'group' => 'SimpleTest',
    );
  }

  /**
   * Test Drupal\simpletest\WebTestBase::getAbsoluteUrl().
   */
  function testGetAbsoluteUrl() {
    $url = 'user/login';

    $this->drupalGet($url);
    $absolute = url($url, array('absolute' => TRUE));
    $this->assertEqual($absolute, $this->url, t('Passed and requested URL are equal.'));
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), t('Requested and returned absolute URL are equal.'));

    $this->drupalPost(NULL, array(), t('Log in'));
    $this->assertEqual($absolute, $this->url, t('Passed and requested URL are equal.'));
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), t('Requested and returned absolute URL are equal.'));

    $this->clickLink('Create new account');
    $url = 'user/register';
    $absolute = url($url, array('absolute' => TRUE));
    $this->assertEqual($absolute, $this->url, t('Passed and requested URL are equal.'));
    $this->assertEqual($this->url, $this->getAbsoluteUrl($this->url), t('Requested and returned absolute URL are equal.'));
  }

  /**
   * Tests XPath escaping.
   */
  function testXPathEscaping() {
    $testpage = <<< EOF
<html>
<body>
<a href="link1">A "weird" link, just to bother the dumb "XPath 1.0"</a>
<a href="link2">A second "even more weird" link, in memory of George O'Malley</a>
</body>
</html>
EOF;
    $this->drupalSetContent($testpage);

    // Matches the first link.
    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A "weird" link, just to bother the dumb "XPath 1.0"'));
    $this->assertEqual($urls[0]['href'], 'link1', 'Match with quotes.');

    $urls = $this->xpath('//a[text()=:text]', array(':text' => 'A second "even more weird" link, in memory of George O\'Malley'));
    $this->assertEqual($urls[0]['href'], 'link2', 'Match with mixed single and double quotes.');
  }
}
