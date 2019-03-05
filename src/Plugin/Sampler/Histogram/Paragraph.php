<?php

namespace Drupal\sampler\Plugin\Sampler\Histogram;

use Drupal\sampler\HistogramBase;

/**
 * Builds histogram for paragraphs entity type.
 *
 * @SamplerHistogram(
 *   id = "paragraph",
 *   label = @Translation("Paragraph"),
 *   description = @Translation("Builds histogram for paragraphs.")
 * )
 */
class Paragraph extends HistogramBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable($entityTypeId) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build($entityTypeId) {
    $histogram = [];

    $entityTypeDefinition = $this->entityTypeManager->getDefinition('paragraph');
    $dataTable = $entityTypeDefinition->getDataTable();

    $query = $this->connection
      ->select($dataTable, 'r')
      ->fields('r', ['parent_type', 'parent_id']);

    $query->condition('parent_type', $entityTypeId);
    $query->addExpression('count(id)', 'count');
    $query->groupBy('parent_id');

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($histogram[$record->count])) {
        $histogram[$record->count] = 1;
        continue;
      }
      $histogram[$record->count]++;
    }

    ksort($histogram);

    return $histogram;
  }

}
