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

  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
  }

  /**
   * Create report for the Thunder performance project.
   *
   * @param string $filename
   *  If a filename is given, the report will be written into that file.
   *  Otherwise the report will be printed to screen.
   *
   * @command sampler:report
   */
  public function report($filename = NULL) {
    $this->reporter
      ->collect()
      ->output($filename);
  }

}
