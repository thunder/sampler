<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\sampler\SamplerBase;
use Drupal\sampler\Traits\GroupedDataTrait;

/**
 * Collects bundle data.
 *
 * @Sampler(
 *   id = "bundle",
 *   label = @Translation("Bundle"),
 *   description = @Translation("Collects bundle data."),
 *   deriver = "\Drupal\sampler\Plugin\Derivative\BundleDeriver"
 * )
 */
class Bundle extends SamplerBase {

  use GroupedDataTrait;

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $data = [];
    $entityTypeId = $this->entityTypeId();
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);

    $baseTable = $entityTypeDefinition->getBaseTable();
    $bundleField = $entityTypeDefinition->getKey('bundle');
    $bundles = array_keys($this->bundleInfo->getBundleInfo($entityTypeId));

    $baseFields = array_keys($this->entityFieldManager->getBaseFieldDefinitions($entityTypeId));

    foreach ($bundles as $bundle) {
      $mapping = $this->getGroupMapping($entityTypeId, $bundle);

      $query = $this->connection->select($baseTable, 'b');
      $query->condition($bundleField, $bundle);
      $data[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

      $fields = array_diff_key(
        array_keys($this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle)),
        array_keys($baseFields)
      );
      $data[$mapping]['fields'] = count($fields);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return $this->getBaseId();
  }

}
