<?php

namespace Drupal\Tests\sampler\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\Mapping;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sampler\Mapping
 *
 * @group media
 */
class MappingTest extends UnitTestCase {

  /**
   * Array of entity type managers used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject[]
   */
  protected $entityTypeManager = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Entity IDs and corresponding entity keys to create
    // EntityTypeManagerInterface::getDefinition() mocks for.
    $entityKeysPerId = [
      'node' => [
        'id' => 'nid',
        'revision' => 'vid',
        'bundle' => 'type',
        'label' => 'title',
        'langcode' => 'langcode',
        'uuid' => 'uuid',
        'published' => 'status',
        'owner' => 'uid',
        'default_langcode' => 'default_langcode',
        'revision_translation_affected' => 'revision_translation_affected',
      ],
      'taxonomy_term' => [
        'id' => 'tid',
        'revision' => 'revision_id',
        'bundle' => 'vid',
        'label' => 'name',
        'langcode' => 'langcode',
        'uuid' => 'uuid',
        'published' => 'status',
        'default_langcode' => 'default_langcode',
        'revision_translation_affected' => 'revision_translation_affected',
      ],
      // Actual mocking is currently needed in ::testGetFieldMapping() for node
      // and taxonomy_term only. other entity IDs are not tested for keys.
      'default' => [],
    ];

    foreach ($entityKeysPerId as $entityTypeId => $entityKeys) {
      $entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
      $entityType->expects($this->any())
        ->method('getKeys')
        ->will($this->returnValue($entityKeys));

      $this->entityTypeManager[$entityTypeId] = $this->createMock(EntityTypeManagerInterface::class);
      $this->entityTypeManager[$entityTypeId]->expects($this->any())
        ->method('getDefinition')
        ->with($entityTypeId)
        ->will($this->returnValue($entityType));
    }

  }

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
      $mapping = $this->getMapping($entityTypeId);
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
    $mapping = $this->getMapping('user');
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
          'node' => [
            'field_something' => 'field-0',
            'field_body' => 'field-1',
            // Special treatment of keys.
            'nid' => 'id',
            'vid' => 'revision',
            'type' => 'bundle',
            'title' => 'label',
            'status' => 'published',
            'uid' => 'owner',
            'default_langcode' => 'default_langcode',
          ],
          // Taxonomy terms have special handling for parent.
          'taxonomy_term' => [
            'parent' => 'parent',
          ],
        ],
      ],
      'fields are not mapped' => [
        FALSE,
        [
          'node' => [
            'field_something' => 'field_something',
            'field_body' => 'field_body',
            'title' => 'title',
            'type' => 'type',
            'nid' => 'nid',
          ],
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
   *   The test data. An array with input roles as keys and mapped values as
   *   values.
   *
   * @covers ::getFieldMapping
   *
   * @dataProvider providerGetFieldMapping
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testGetFieldMapping(bool $enableMapping, array $testData) {
    foreach ($testData as $entityTypeId => $bundleMappings) {
      $mapping = $this->getMapping($entityTypeId);
      $mapping->enableMapping($enableMapping);

      foreach ($bundleMappings as $value => $mappedValue) {
        $this->assertSame($mappedValue, $mapping->getFieldMapping($entityTypeId, $value));
        // Multiple invocations must return the same mappings.
        $this->assertSame($mappedValue, $mapping->getFieldMapping($entityTypeId, $value));
      }
    }
  }

  /**
   * Get a mocked mapping service for a specific entity type.
   *
   * @param string $entityTypeId
   *   The entity type to get the mocked mapping for.
   *
   * @return \Drupal\sampler\Mapping
   *   The mokked mapping service.
   */
  protected function getMapping(string $entityTypeId) {
    if (empty($this->entityTypeManager[$entityTypeId])) {
      return new Mapping($this->entityTypeManager['default']);
    }

    return new Mapping($this->entityTypeManager[$entityTypeId]);
  }

}
