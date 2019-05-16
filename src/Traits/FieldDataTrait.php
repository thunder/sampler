<?php

namespace Drupal\sampler\Traits;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides methods for handling of field data.
 */
trait FieldDataTrait {

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
  protected function collectDefaultFieldData(FieldDefinitionInterface $fieldDefinition) {
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
   *
   * @return array
   *   The collected field data.
   */
  protected function collectEntityReferenceData(FieldDefinitionInterface $fieldDefinition) {
    $targetEntityTypeId = $fieldDefinition->getSetting('target_type');

    $settingName = ($targetEntityTypeId == 'paragraph') ? 'target_bundles_drag_drop' : 'target_bundles';

    $targetBundles = [];
    if (!empty($fieldDefinition->getSetting('handler_settings')[$settingName])) {
      $targetBundles = array_map(function ($bundle) use ($targetEntityTypeId) {
        return $this->getGroupMapping($targetEntityTypeId, $bundle);
      }, array_keys($fieldDefinition->getSetting('handler_settings')[$settingName]));
    }

    $data = [
      'target_type' => $targetEntityTypeId,
      'target_bundles' => $targetBundles,
    ];

    if (!$fieldDefinition instanceof BaseFieldDefinition) {
      $entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entityTypeId());
      $bundleField = $entityTypeDefinition->getKey('bundle');
      $idField = $entityTypeDefinition->getKey('id');

      $table_mapping = $this->entityTypeManager->getStorage($this->entityTypeId())
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
