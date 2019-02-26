<?php

namespace Drupal\sampler\Plugin\Sampler\Histogram;

use Drupal\sampler\HistogramBase;

/**
 * Builds histogram for paragraphs entity type.
 *
 * @SamplerHistogram(
 *   id = "entity_type_paragraph",
 *   label = @Translation("Entity type Paragraph"),
 *   description = @Translation("Builds histogram for paragraphs entity type.")
 * )
 */
class EntityTypeParagraph extends HistogramBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable($entityTypeId) {
    return $entityTypeId === 'paragraph';
  }

  /**
   * {@inheritdoc}
   */
  public function build($entityTypeId) {
    $histogram = [];

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $dataTable = $entityTypeDefinition->getDataTable();

    $query = $this->connection
      ->select($dataTable, 'r')
      ->fields('r', ['parent_type']);

    $query->isNotNull('parent_type');
    $query->addExpression('count(id)', 'count');
    $query->groupBy('parent_type');
    $query->groupBy('parent_id');

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($histogram[$record->parent_type])) {
        $histogram[$record->parent_type] = ['paragraph' => []];
      }
      if (!isset($histogram[$record->parent_type]['paragraph'][$record->count])) {
        $histogram[$record->parent_type]['paragraph'][$record->count] = 1;
        continue;
      }
      $histogram[$record->parent_type]['paragraph'][$record->count]++;
    }

    foreach ($histogram as $parentType => $counts) {
      ksort($histogram[$parentType]['paragraph']);
    }

    return $histogram;
  }

}
