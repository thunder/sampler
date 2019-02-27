<?php

/**
 * @file
 * Drush commands for sampler module.
 */

/**
 * Implements hook_drush_command().
 */
function sampler_drush_command() {
  $items = [];
  $items['sampler-report'] = [
    'description' => dt('Create report for the Thunder performance project.'),
    'options' => [
      'file' => [
        'description' => dt('If a file is given, the report will be written into that file. Otherwise, the report will be printed to screen.'),
        'example-value' => 'report.json',
      ],
      'anonymize' => [
        'description' => dt('Option to anonymize the output. I.e. show actual bundle names or replace them with generic names. The given value will be converted to boolean. [default: "1"]'),
        'example-value' => '1|0',
      ],
    ],
    'aliases' => ['sampler:report'],
  ];
  return $items;
}

/**
 * Run the sampler report.
 */
function drush_sampler_report() {
  $file = drush_get_option('file', NULL);
  $anonymize = (bool) drush_get_option('anonymize', '1');

  $reporter = \Drupal::getContainer()
    ->get('sampler.reporter');

  $reporter
    ->setAnonymize($anonymize)
    ->collect()
    ->output($file);
}
