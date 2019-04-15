<?php

namespace Drupal\sampler\Commands;

use Drupal\sampler\ConfigCreator;
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

  protected $configCreator;

  /**
   * SamplerCommands constructor.
   *
   * @param \Drupal\sampler\Reporter $reporter
   *   The reporter service.
   */
  public function __construct(Reporter $reporter, ConfigCreator $configCreator) {
    $this->reporter = $reporter;
    $this->configCreator = $configCreator;
  }

  /**
   * Create report for the Thunder performance project.
   *
   * @option file If a file is given, the report will be written into that file. Otherwise, the report will be printed to screen.
   * @option anonymize Option to anonymize the output. I.e. show actual bundle names or replace them with generic names. The given value will be converted to boolean.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
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
      ->anonymize($anonymize)
      ->collect()
      ->output($options['file']);
  }

  /**
   * Create config from a report file.
   *
   * @param $file The report file the config should created of.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command sampler:create-config
   */
  public function createConfig($file) {
    $this->configCreator
      ->setReportData($file)
      ->cleanup()
      ->create();
  }

}
