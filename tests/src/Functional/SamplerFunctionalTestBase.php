<?php

namespace Drupal\Tests\sampler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Sampler functional tests.
 */
abstract class SamplerFunctionalTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field',
    'media',
    'sampler',
    'sampler_test',
  ];

}
