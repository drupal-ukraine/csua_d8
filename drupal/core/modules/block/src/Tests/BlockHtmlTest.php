<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockHtmlTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block HTML ID validity.
 *
 * @group block
 */
class BlockHtmlTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);

    // Enable the test_html block, to test HTML ID and attributes.
    \Drupal::state()->set('block_test.attributes', array('data-custom-attribute' => 'foo'));
    \Drupal::state()->set('block_test.content', $this->randomMachineName());
    $this->drupalPlaceBlock('test_html', array('id' => 'test_html_block'));

    // Enable a menu block, to test more complicated HTML.
    $this->drupalPlaceBlock('system_menu_block:admin');
  }

  /**
   * Tests for valid HTML for a block.
   */
  function testHtml() {
    $this->drupalGet('');

    // Ensure that a block's ID is converted to an HTML valid ID, and that
    // block-specific attributes are added to the same DOM element.
    $this->assertFieldByXPath('//div[@id="block-test-html-block" and @data-custom-attribute="foo"]', NULL, 'HTML ID and attributes for test block are valid and on the same DOM element.');

    // Ensure expected markup for a menu block.
    $elements = $this->xpath('//nav[contains(@class, :nav-class)]/ul[contains(@class, :ul-class)]/li', array(':nav-class' => 'block-menu', ':ul-class' => 'menu'));
    $this->assertTrue(!empty($elements), 'The proper block markup was found.');
  }

}
