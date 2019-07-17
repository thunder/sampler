<?php

namespace Drupal\sampler;

use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Provides helper methods for retrieving field data.
 */
trait FieldHelperTrait {

  /**
   * Retrieve the index of a field in the bundle report.
   *
   * This implementation mimics the generation of fields in Sampler\Bundle.php
   * and Sampler\BaseFields.php.
   *
   * @param string $entityTypeId
   *   The entity ID.
   * @param string $fieldName
   *   The field name.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param string $bundle
   *   The media bundle. If bundle is NULL, the field has to be a base field.
   *
   * @return int
   *   The index of the source field
   */
  protected function getFieldIndex(string $entityTypeId, string $fieldName, EntityFieldManagerInterface $entityFieldManager, string $bundle = NULL) {
    if ($bundle !== NULL) {
      $fields = $this->getNonBaseFieldDefinitions($entityTypeId, $bundle, $entityFieldManager);
    }
    else {
      $fields = $entityFieldManager->getBaseFieldDefinitions($entityTypeId);
    }

    return array_search($fieldName, array_keys($fields));
  }

  /**
   * Helper function to get all fields, that are not base fields for the entity.
   *
   * @param string $entityTypeId
   *   The entity ID.
   * @param string $bundle
   *   The bundle of the entity get fields for.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
   *   The found fields.
   */
  protected function getNonBaseFieldDefinitions(string $entityTypeId, string $bundle, EntityFieldManagerInterface $entityFieldManager) {
    $baseFields = $entityFieldManager->getBaseFieldDefinitions($entityTypeId);

    $fields = array_diff_key(
      $entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
      $baseFields
    );

    return $fields;
  }

}
