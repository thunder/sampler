<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\sampler\SamplerBase;

/**
 * Builds histogram for revisionable entity types.
 *
 * @Sampler(
 *   id = "revision",
 *   label = @Translation("Revision"),
 *   description = @Translation("Builds histogram for revisions.")
 * )
 */
class Revision extends SamplerBase {

  /**
   * {@inheritdoc}
   */
  public function collect($entityTypeId) {
    $histogram = [];

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $revisionTable = $entityTypeDefinition->getRevisionTable();

    $idKey = $entityTypeDefinition->getKey('id');
    $revisionId = $entityTypeDefinition->getKey('revision');

    $query = $this->connection->select($revisionTable, 'r');
    $query->addExpression('count(' . $revisionId . ')', 'count');
    $query->groupBy($idKey);

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($histogram[$record->count])) {
        $histogram[$record->count] = 1;
        continue;
      }
      $histogram[$record->count]++;
    }

    ksort($histogram);
    return [$this->getPluginId() => $histogram];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable($entityTypeId) {
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    return $entityTypeDefinition->isRevisionable();
  }

  /**
   * {@inheritdoc}
   */
  public function key($entityTypeId) {
    return 'histogram';
  }

}
