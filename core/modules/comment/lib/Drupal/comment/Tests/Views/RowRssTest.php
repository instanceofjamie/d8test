<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\RowRssTest.
 */

namespace Drupal\comment\Tests\Views;

/**
 * Tests the comment rss row plugin.
 *
 * @see \Drupal\comment\Plugin\views\row\Rss
 */
class RowRssTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_comment_rss');

  public static function getInfo() {
    return array(
      'name' => 'Comment: Rss Row',
      'description' => 'Tests the comment rss row plugin.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Test comment rss output.
   */
  public function testRssRow() {
    $this->drupalGet('test-comment-rss');

    $result = $this->xpath('//item');
    $this->assertEqual(count($result), 1, 'Just one comment was found in the rss output.');

    $this->assertEqual($result[0]->pubdate, gmdate('r', $this->comment->created), 'The right pubDate appears in the rss output.');
  }

}
