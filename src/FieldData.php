<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * The FieldData class.
 *
 * @package Drupal\sampler
 */
class FieldData {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group mapping service.
   *
   * @var \Drupal\sampler\Mapping
   */
  protected $mapping;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The field configuration.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * FieldData constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\sampler\Mapping $mapping
   *   The group mapping service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Mapping $mapping, Connection $connection, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mapping = $mapping;
    $this->connection = $connection;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * Collect field data.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field configuration.
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return array
   *   The collected field data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function collect(FieldDefinitionInterface $field_definition, string $entity_type_id) {
    $this->fieldDefinition = $field_definition;
    $this->entityTypeId = $entity_type_id;

    // Data common for all fields.
    $fieldData = $this->defaultFieldData();

    // Reference field specific data.
    if ($this->isReferenceField()) {
      $fieldData = array_merge($fieldData, $this->entityReferenceFieldData());
    }

    return $fieldData;
  }

  /**
   * Collect default field data.
   *
   * By default we collect cardinality, is_required and is_translatable
   * for each field.
   *
   * @return array
   *   The field data.
   */
  protected function defaultFieldData() {
    $fieldDefinition = $this->fieldDefinition;

    return [
      'type' => $fieldDefinition->getType(),
      'required' => (bool) $fieldDefinition->isRequired(),
      'translatable' => (bool) $fieldDefinition->isTranslatable(),
      'cardinality' => $fieldDefinition->getFieldStorageDefinition()->getCardinality(),
    ];
  }

  /**
   * Collect entity reference data.
   *
   * For entity reference and entity reference revision fields, we collect the
   * number of fields for a given target type.
   *
   * @return array
   *   The collected field data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function entityReferenceFieldData() {
    $fieldDefinition = $this->fieldDefinition;
    $entityTypeId = $this->entityTypeId;

    $targetEntityTypeId = $fieldDefinition->getSetting('target_type');
    $settingName = ($targetEntityTypeId == 'paragraph') ? 'target_bundles_drag_drop' : 'target_bundles';

    $installedBundles = $this->bundleInfo->getBundleInfo($targetEntityTypeId);
    $targetBundles = $fieldDefinition->getSetting('handler_settings')[$settingName];

    if (!empty($targetBundles)) {
      // Handler settings could contain target bundles, that have been deleted.
      // We have to filter out bundles, that are not installed anymore.
      $targetBundles = array_intersect_key($targetBundles, $installedBundles);
    }

    $mappedTargetBundles = [];
    if (!empty($targetBundles)) {
      // Map target bundles for anonymization.
      $mappedTargetBundles = array_map(
        function ($bundle) use ($targetEntityTypeId) {
          return $this->mapping->getBundleMapping($targetEntityTypeId, $bundle);
        },
        array_keys($targetBundles)
      );
    }

    // Data common for all entity references.
    $data = [
      'target_type' => $targetEntityTypeId,
      'target_bundles' => $mappedTargetBundles,
    ];

    if ($this->isBaseField()) {
      return $data;
    }

    // Add histogram of number of entries in bundle.
    $data['histogram'] = $this->histogramData($fieldDefinition, $entityTypeId);

    return $data;
  }

  /**
   * Collect histogram.
   *
   * @return array
   *   The collected histogram.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function histogramData() {
    $fieldDefinition = $this->fieldDefinition;
    $entityTypeId = $this->entityTypeId;

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $bundleField = $entityTypeDefinition->getKey('bundle');
    $idField = $entityTypeDefinition->getKey('id');

    $table_mapping = $this->entityTypeManager->getStorage($entityTypeId)
      ->getTableMapping();
    $dataTable = $table_mapping->getFieldTableName($fieldDefinition->getFieldStorageDefinition()
      ->getName());
    $query = $this->connection->select($dataTable, 'ft');
    $query->addExpression('count(ft.bundle)', 'number_of_entries');
    $query->innerJoin($entityTypeDefinition->getBaseTable(), 'bt',
      "bt.$idField=ft.entity_id");
    $query->condition("bt.$bundleField", $fieldDefinition->getTargetBundle());
    $query->groupBy('ft.entity_id');
    $query->orderBy('number_of_entries');
    $results = $query->execute()->fetchCol();

    return array_count_values($results);
  }

  /**
   * Check, if current filed is a reference field.
   *
   * @return bool
   *   Field is reference field.
   */
  protected function isReferenceField() {
    return in_array($this->fieldDefinition->getType(), ['entity_reference', 'entity_reference_revisions']);
  }

  /**
   * Check, if current field is an entity base field.
   *
   * @return bool
   *   Field is base field.
   */
  protected function isBaseField() {
    return ($this->fieldDefinition instanceof BaseFieldDefinition  || $this->fieldDefinition instanceof BaseFieldOverride);
  }

}
