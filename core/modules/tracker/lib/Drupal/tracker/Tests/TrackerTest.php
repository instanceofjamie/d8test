<?php

/**
 * @file
 * Definition of Drupal\tracker\Tests\TrackerTest.
 */

namespace Drupal\tracker\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for testing tracker.module.
 */
class TrackerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'tracker', 'history');

  /**
   * The main user for testing.
   *
   * @var object
   */
  protected $user;

  /**
   * A second user that will 'create' comments and nodes.
   *
   * @var object
   */
  protected $other_user;

  public static function getInfo() {
    return array(
      'name' => 'Tracker',
      'description' => 'Create and delete nodes and check for their display in the tracker listings.',
      'group' => 'Tracker'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $permissions = array('access comments', 'create page content', 'post comments', 'skip comment approval');
    $this->user = $this->drupalCreateUser($permissions);
    $this->other_user = $this->drupalCreateUser($permissions);

    // Make node preview optional.
    variable_set('comment_preview_page', 0);
  }

  /**
   * Tests for the presence of nodes on the global tracker listing.
   */
  function testTrackerAll() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'status' => 0,
    ));
    $published = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'status' => 1,
    ));

    $this->drupalGet('tracker');
    $this->assertNoText($unpublished->label(), 'Unpublished node do not show up in the tracker listing.');
    $this->assertText($published->label(), 'Published node show up in the tracker listing.');
    $this->assertLink(t('My recent content'), 0, 'User tab shows up on the global tracker page.');

    // Delete a node and ensure it no longer appears on the tracker.
    node_delete($published->nid);
    $this->drupalGet('tracker');
    $this->assertNoText($published->label(), 'Deleted node do not show up in the tracker listing.');
  }

  /**
   * Tests for the presence of nodes on a user's tracker listing.
   */
  function testTrackerUser() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->user->uid,
      'status' => 0,
    ));
    $my_published = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->user->uid,
      'status' => 1,
    ));
    $other_published_no_comment = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->other_user->uid,
      'status' => 1,
    ));
    $other_published_my_comment = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->other_user->uid,
      'status' => 1,
    ));
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]' => $this->randomName(20),
    );
    $this->drupalPost('comment/reply/' . $other_published_my_comment->nid, $comment, t('Save'));

    $this->drupalGet('user/' . $this->user->uid . '/track');
    $this->assertNoText($unpublished->label(), "Unpublished nodes do not show up in the users's tracker listing.");
    $this->assertText($my_published->label(), "Published nodes show up in the user's tracker listing.");
    $this->assertNoText($other_published_no_comment->label(), "Other user's nodes do not show up in the user's tracker listing.");
    $this->assertText($other_published_my_comment->label(), "Nodes that the user has commented on appear in the user's tracker listing.");

    // Verify that unpublished comments are removed from the tracker.
    $admin_user = $this->drupalCreateUser(array('post comments', 'administer comments', 'access user profiles'));
    $this->drupalLogin($admin_user);
    $this->drupalPost('comment/1/edit', array('status' => COMMENT_NOT_PUBLISHED), t('Save'));
    $this->drupalGet('user/' . $this->user->uid . '/track');
    $this->assertNoText($other_published_my_comment->label(), 'Unpublished comments are not counted on the tracker listing.');
  }

  /**
   * Tests for the presence of the "new" flag for nodes.
   */
  function testTrackerNewNodes() {
    $this->drupalLogin($this->user);

    $edit = array(
      'title' => $this->randomName(8),
    );

    $node = $this->drupalCreateNode($edit);
    $title = $edit['title'];
    $this->drupalGet('tracker');
    $this->assertPattern('/' . $title . '.*new/', 'New nodes are flagged as such in the tracker listing.');

    $this->drupalGet('node/' . $node->nid);
    $this->drupalGet('tracker');
    $this->assertNoPattern('/' . $title . '.*new/', 'Visited nodes are not flagged as new.');

    $this->drupalLogin($this->other_user);
    $this->drupalGet('tracker');
    $this->assertPattern('/' . $title . '.*new/', 'For another user, new nodes are flagged as such in the tracker listing.');

    $this->drupalGet('node/' . $node->nid);
    $this->drupalGet('tracker');
    $this->assertNoPattern('/' . $title . '.*new/', 'For another user, visited nodes are not flagged as new.');
  }

  /**
   * Tests for comment counters on the tracker listing.
   */
  function testTrackerNewComments() {
    $this->drupalLogin($this->user);

    $node = $this->drupalCreateNode(array(
      'comment' => 2,
      'title' => $this->randomName(8),
    ));

    // Add a comment to the page.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]' => $this->randomName(20),
    );
    // The new comment is automatically viewed by the current user.
    $this->drupalPost('comment/reply/' . $node->nid, $comment, t('Save'));

    $this->drupalLogin($this->other_user);
    $this->drupalGet('tracker');
    $this->assertText('1 new', 'New comments are counted on the tracker listing pages.');
    $this->drupalGet('node/' . $node->nid);

    // Add another comment as other_user.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]' => $this->randomName(20),
    );
    // If the comment is posted in the same second as the last one then Drupal
    // can't tell the difference, so we wait one second here.
    sleep(1);
    $this->drupalPost('comment/reply/' . $node->nid, $comment, t('Save'));

    $this->drupalLogin($this->user);
    $this->drupalGet('tracker');
    $this->assertText('1 new', 'New comments are counted on the tracker listing pages.');
  }

  /**
   * Tests that existing nodes are indexed by cron.
   */
  function testTrackerCronIndexing() {
    $this->drupalLogin($this->user);

    // Create 3 nodes.
    $edits = array();
    $nodes = array();
    for ($i = 1; $i <= 3; $i++) {
      $edits[$i] = array(
        'comment' => 2,
        'title' => $this->randomName(),
      );
      $nodes[$i] = $this->drupalCreateNode($edits[$i]);
    }

    // Add a comment to the last node as other user.
    $this->drupalLogin($this->other_user);
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]' => $this->randomName(20),
    );
    $this->drupalPost('comment/reply/' . $nodes[3]->nid, $comment, t('Save'));

    // Start indexing backwards from node 3.
    variable_set('tracker_index_nid', 3);

    // Clear the current tracker tables and rebuild them.
    db_delete('tracker_node')
      ->execute();
    db_delete('tracker_user')
      ->execute();
    tracker_cron();

    $this->drupalLogin($this->user);

    // Fetch the user's tracker.
    $this->drupalGet('tracker/' . $this->user->uid);

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }
    $this->assertText('1 new', 'New comment is counted on the tracker listing pages.');
    $this->assertText('updated', 'Node is listed as updated');

    // Fetch the site-wide tracker.
    $this->drupalGet('tracker');

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }
    $this->assertText('1 new', 'New comment is counted on the tracker listing pages.');
  }

  /**
   * Tests that publish/unpublish works at admin/content/node.
   */
  function testTrackerAdminUnpublish() {
    $admin_user = $this->drupalCreateUser(array('access content overview', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($admin_user);

    $node = $this->drupalCreateNode(array(
      'comment' => 2,
      'title' => $this->randomName(),
    ));

    // Assert that the node is displayed.
    $this->drupalGet('tracker');
    $this->assertText($node->label(), 'Node is displayed on the tracker listing pages.');

    // Unpublish the node and ensure that it's no longer displayed.
    $edit = array(
      'operation' => 'unpublish',
      'nodes[' . $node->nid . ']' => $node->nid,
    );
    $this->drupalPost('admin/content', $edit, t('Update'));

    $this->drupalGet('tracker');
    $this->assertText(t('No content available.'), 'Node is displayed on the tracker listing pages.');
  }
}
