<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
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
   * @var \Drupal\sampler\GroupMapping
   */
  protected $groupMapping;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * FieldData constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\sampler\GroupMapping $group_mapping
   *   The group mapping service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupMapping $group_mapping, Connection $connection) {
    $this->entityTypeManager = $entityTypeManager;
    $this->groupMapping = $group_mapping;
    $this->connection = $connection;
  }

  /**
   * Collect field data.
   *
   * By default we collect cardinality, is_required and is_translatable
   * for each field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field configuration.
   *
   * @return array
   *   The field data.
   */
  public function defaultFieldData(FieldDefinitionInterface $fieldDefinition) {
    return [
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
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field configuration.
   * @param string $entityTypeId
   *   The entity type.
   *
   * @return array
   *   The collected field data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityReferenceFieldData(FieldDefinitionInterface $fieldDefinition, $entityTypeId) {
    $targetEntityTypeId = $fieldDefinition->getSetting('target_type');

    $settingName = ($targetEntityTypeId == 'paragraph') ? 'target_bundles_drag_drop' : 'target_bundles';

    $targetBundles = [];
    if (!empty($fieldDefinition->getSetting('handler_settings')[$settingName])) {
      $targetBundles = array_map(function ($bundle) use ($targetEntityTypeId) {
        return $this->groupMapping->getGroupMapping($targetEntityTypeId, $bundle);
      }, array_keys($fieldDefinition->getSetting('handler_settings')[$settingName]));
    }

    $data = [
      'target_type' => $targetEntityTypeId,
      'target_bundles' => $targetBundles,
    ];

    if (!$fieldDefinition instanceof BaseFieldDefinition) {
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

      $data['histogram'] = array_count_values($results);
    }

    return $data;
  }

}
