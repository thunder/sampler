<?php

namespace Drupal\Tests\sampler\Unit;

use Drupal\sampler\Mapping;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sampler\Mapping
 *
 * @group sampler
 */
class MappingTest extends UnitTestCase {

  /**
   * Data provider for testGetBundleMapping().
   *
   * @see ::testGetBundleMapping()
   *
   * @return array
   *   The test data.
   */
  public function providerGetBundleMapping() {
    return [
      'bundles are mapped' => [
        TRUE,
        [
          'node' => [
            'something' => 'bundle-0',
            'article' => 'bundle-1',
            'page' => 'bundle-2',
          ],
          // Different entities should start with bundle-0 again.
          'media' => [
            'image' => 'bundle-0',
            'video' => 'bundle-1',
          // Same bundle as node has, but different mapping.
            'something' => 'bundle-2',
          ],
        ],
      ],
      'bundles are not mapped' => [
        FALSE,
        [
          'node' => [
            'article' => 'article',
            'page' => 'page',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests that getBundleMapping() maps correctly.
   *
   * @param bool $enableMapping
   *   Enable mapping.
   * @param array $testData
   *   The test data. A two dimensional array. Root keys are $entityTypeIds,
   *   values are arrays of [<input bundle> => <expected mapped value>].
   *
   * @covers ::getBundleMapping
   *
   * @dataProvider providerGetBundleMapping
   */
  public function testGetBundleMapping(bool $enableMapping, array $testData) {
    foreach ($testData as $entityTypeId => $bundleMappings) {
      $mapping = new Mapping();
      $mapping->enableMapping($enableMapping);

      foreach ($bundleMappings as $value => $mappedValue) {
        $this->assertSame($mappedValue, $mapping->getBundleMapping($entityTypeId, $value));
        // Multiple invocations must return the same mappings.
        $this->assertSame($mappedValue, $mapping->getBundleMapping($entityTypeId, $value));
      }
    }
  }

  /**
   * Data provider for testGetUserRoleMapping().
   *
   * @see ::testGetUserRoleMapping()
   *
   * @return array
   *   The test data.
   */
  public function providerGetUserRoleMapping() {
    return [
      'roles are mapped' => [
        TRUE,
        [
          'editor' => 'role-0',
          'administrator' => 'role-1',
          'something' => 'role-2',
        ],
      ],
      'roles are not mapped' => [
        FALSE,
        [
          'editor' => 'editor',
          'administrator' => 'administrator',
          'something' => 'something',
        ],
      ],
    ];
  }

  /**
   * Tests that getUserRoleMapping() maps correctly.
   *
   * @param bool $enableMapping
   *   Enable mapping.
   * @param array $testData
   *   The test data. An array with input roles as keys and mapped values as
   *   values.
   *
   * @covers ::getUserRoleMapping
   *
   * @dataProvider providerGetUserRoleMapping
   */
  public function testGetUserRoleMapping(bool $enableMapping, array $testData) {
    $mapping = new Mapping();
    $mapping->enableMapping($enableMapping);

    foreach ($testData as $role => $mappedValue) {
      $this->assertSame($mappedValue, $mapping->getUserRoleMapping($role));
      // Multiple invocations must return the same mappings.
      $this->assertSame($mappedValue, $mapping->getUserRoleMapping($role));
    }
  }

  /**
   * Data provider for testGetFieldMapping().
   *
   * @see ::testGetFieldMapping()
   *
   * @return array
   *   The test data.
   */
  public function providerGetFieldMapping() {
    return [
      'fields are mapped' => [
        TRUE,
        [
          'field_something' => 'field-0',
          'field_body' => 'field-1',
        ],
      ],
      'fields are not mapped' => [
        FALSE,
        [
          'field_something' => 'field_something',
          'field_body' => 'field_body',
        ],
      ],
    ];
  }

  /**
   * Tests that getFieldMapping() maps correctly.
   *
   * @param bool $enableMapping
   *   Enable mapping.
   * @param array $testData
   *   The test data. An array with input fields as keys and mapped values as
   *   values.
   *
   * @covers ::getFieldMapping
   *
   * @dataProvider providerGetFieldMapping
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testGetFieldMapping(bool $enableMapping, array $testData) {
    $entityTypeId = 'node';
    $mapping = new Mapping();
    $mapping->enableMapping($enableMapping);

    foreach ($testData as $value => $mappedValue) {
      $this->assertSame($mappedValue, $mapping->getFieldMapping($entityTypeId, $value));
      // Multiple invocations must return the same mappings.
      $this->assertSame($mappedValue, $mapping->getFieldMapping($entityTypeId, $value));
    }
  }

}
