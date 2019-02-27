<?php

namespace Drupal\sampler\Commands;

use Drupal\sampler\Reporter;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class SamplerCommands extends DrushCommands {

  /**
   * The reporter instance.
   *
   * @var \Drupal\sampler\Reporter
   */
  protected $reporter;

  /**
   * SamplerCommands constructor.
   *
   * @param \Drupal\sampler\Reporter $reporter
   *   The reporter service.
   */
  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
  }

  /**
   * Create report for the Thunder performance project.
   *
   * @option file If a file is given, the report will be written into that file. Otherwise, the report will be printed to screen.
   * @option anonymize Option to anonymize the output. I.e. show actual bundle names or replace them with generic names. The given value will be converted to boolean.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @command sampler:report
   */
  public function report(array $options = ['file' => NULL, 'anonymize' => '1']) {
    $anonymize = $options['anonymize'];

    if (strtolower($anonymize) === '1') {
      $anonymize = TRUE;
    }
    else {
      $anonymize = FALSE;
    }

    $this->reporter
      ->setAnonymize($anonymize)
      ->collect()
      ->output($options['file']);
  }

}
