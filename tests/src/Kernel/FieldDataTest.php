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
   * The config factory.
   *
   * @var \Drupal\core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['sampler', 'sampler_test']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');

    $this->fieldData = \Drupal::service('sampler.field_data');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->configFactory = \Drupal::service('config.factory');
    $this->mapping = \Drupal::service('sampler.mapping');

    $this->mapping->enableMapping(FALSE);
  }

  /**
   * Data provider for testParagraphTargetBundleCollect().
   */
  public function providerParagraphTargetBundleCollect() {
    return [
      [FALSE, [], ['one', 'two']],
      [FALSE, ['one' => 'one'], ['one']],
      [FALSE, ['two' => 'two'], ['two']],
      [FALSE, ['one' => 'one', 'two' => 'two'], ['one', 'two']],
      [TRUE, [], ['one', 'two']],
      [TRUE, ['one' => 'one'], ['two']],
      [TRUE, ['two' => 'two'], ['one']],
      [TRUE, ['one' => 'one', 'two' => 'two'], []],
    ];
  }

  /**
   * Test collected paragraph target bundle data for different field settings.
   *
   * @param bool $negate
   *   The paragraphs negate field setting.
   * @param array $selectedTargetBundles
   *   The selected bundles.
   * @param array $expectedAllowedTargetBundles
   *   The expected allowed bundles.
   *
   * @dataProvider providerParagraphTargetBundleCollect
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testParagraphTargetBundleCollect(bool $negate, array $selectedTargetBundles, array $expectedAllowedTargetBundles) {
    $testField = $this->getParagraphsField();
    $entityTypeId = $testField->getTargetEntityTypeId();
    $entityBundle = $testField->getTargetBundle();
    $fieldName = $testField->getName();

    $fieldConfig = $this->configFactory->getEditable('field.field.' . $entityTypeId . '.' . $entityBundle . '.' . $fieldName);
    $fieldConfig->set('settings.handler_settings.negate', $negate);
    $fieldConfig->set('settings.handler_settings.target_bundles', $selectedTargetBundles);
    $fieldConfig->save();

    $testField = $this->getParagraphsField();
    $fieldData = $this->fieldData->collect($testField, $entityTypeId);

    $this->assertEquals($expectedAllowedTargetBundles, $fieldData['target_bundles']);
  }

  /**
   * Test collected paragraph target bundle data with deleted a paragraph type.
   */
  public function testDeletedParagraphTypeTargetBundleCollection() {
    $testField = $this->getParagraphsField();
    $entityTypeId = $testField->getTargetEntityTypeId();

    $fieldData = $this->fieldData->collect($testField, $entityTypeId);
    $this->assertEquals(['one', 'two'], $fieldData['target_bundles']);

    $this->entityTypeManager->getStorage('paragraphs_type')->load('one')->delete();

    $testField = $this->getParagraphsField();

    $fieldData = $this->fieldData->collect($testField, $entityTypeId);
    $this->assertEquals(['two'], $fieldData['target_bundles']);
  }

  /**
   * Get the paragraphs field from sampler_test_module config.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The paragraphs field definition.
   */
  protected function getParagraphsField() {
    // These values are defined in the sampler_test_module configuration.
    $entityTypeId = 'node';
    $entityBundle = 'type_one';
    $entityReferenceField = 'field_six';

    $this->entityFieldManager->clearCachedFieldDefinitions();
    return $this->entityFieldManager->getFieldDefinitions($entityTypeId, $entityBundle)[$entityReferenceField];
  }

}
