<?php

namespace Drupal\Tests\sampler\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the FieldData service.
 *
 * @group sampler
 */
class FieldDataTest extends KernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'sampler',
    'sampler_test',
    'system',
    'node',
    'user',
    'field',
    'taxonomy',
    'file',
    'entity_reference_revisions',
    'paragraphs',
  ];

  /**
   * The field data service.
   *
   * @var \Drupal\sampler\FieldData
   */
  protected $fieldData;

  /**
   * The mapping service.
   *
   * @var \Drupal\sampler\Mapping
   */

  protected $mapping;
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['sampler', 'sampler_test']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');

    $this->fieldData = \Drupal::service('sampler.field_data');
    $this->mapping = \Drupal::service('sampler.mapping');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
  }

  /**
   * Test collect method.
   */
  public function testCollect() {
    $entityTypeId = 'node';
    $entityBundle = 'type_one';
    $entityReferenceField = 'field_six';

    $this->mapping->enableMapping(FALSE);

    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $entityBundle);
    $fieldData = $this->fieldData->collect($fields[$entityReferenceField], $entityTypeId);
    $this->assertEquals(['one'], $fieldData['target_bundles']);

    $this->entityTypeManager->getStorage('paragraphs_type')->load('one')->delete();

    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $entityBundle);
    $fieldData = $this->fieldData->collect($fields[$entityReferenceField], $entityTypeId);
    print_r($fields[$entityReferenceField]->getSetting('handler_settings'));

    $this->assertTrue(FALSE);
  }

}
