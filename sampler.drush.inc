<?php

/**
 * @file
 */

/**
 *
 */
function sampler_drush_command() {
  $items = [];
  $items['sampler-report'] = [
    'description' => dt('Create report for the Thunder performance project.'),
    'arguments' => [
      'filename' => dt('If a filename is given, the report will be written into that file. Otherwise, the report will be printed to screen.'),
    ],
    'aliases' => ['sampler:report'],
  ];
  return $items;
}

/**
 *
 */
function drush_sampler_report($filename = NULL) {
  $reporter = \Drupal::service('sampler.reporter');
  $reporter->collect()
    ->output($filename);
}
