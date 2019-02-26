<?php

namespace Drupal\sampler\Plugin\Sampler\Histogram;

use Drupal\sampler\HistogramBase;

/**
 * Builds histogram for revisionalbe entity types.
 *
 * @SamplerHistogram(
 *   id = "revisionable",
 *   label = @Translation("Revisionable"),
 *   description = @Translation("Builds histogram for revisionalbe entity types.")
 * )
 */
class Revisionable extends HistogramBase {

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
  public function build($entityTypeId) {
    $histogram = ['revision' => []];

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $revisionTable = $entityTypeDefinition->getRevisionTable();

    $idKey = $entityTypeDefinition->getKey('id');
    $revisionId = $entityTypeDefinition->getKey('revision');

    $query = $this->connection->select($revisionTable, 'r');
    $query->addExpression('count(' . $revisionId . ')', 'count');
    $query->groupBy($idKey);

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($histogram['revision'][$record->count])) {
        $histogram['revision'][$record->count] = 1;
        continue;
      }
      $histogram['revision'][$record->count]++;
    }

    ksort($histogram['revision']);

    return [$entityTypeId => $histogram];
  }

}
