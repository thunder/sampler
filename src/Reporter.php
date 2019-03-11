<?php

namespace Drupal\sampler;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The Reporter class.
 *
 * @package Drupal\sampler
 */
class Reporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The report.
   *
   * @var array
   */
  protected $report = [];

  /**
   * Entity types whose usage will get counted per bundle.
   *
   * @var string[]
   */
  protected $bundledEntityTypes = [];

  /**
   * Mapping of group names to integer values.
   *
   * @var string[]
   */
  protected $groupMapping = [];

  /**
   * The group count manager service.
   *
   * @var \Drupal\sampler\SamplerPluginManager
   */
  protected $samplerPluginManager;

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $anonymize;

  /**
   * Reporter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sampler\SamplerPluginManager $sampler_plugin_manager
   *   The group count manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SamplerPluginManager $sampler_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->samplerPluginManager = $sampler_plugin_manager;

    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition->getBundleEntityType() && $definition->getBaseTable()) {
        $this->bundledEntityTypes[] = $definition->id();
      }
    }

    $this->bundledEntityTypes[] = 'user';
    $this->anonymize(TRUE);
  }

  /**
   * Collect the data.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function collect(): Reporter {
    foreach ($this->bundledEntityTypes as $entityTypeId) {
      $this->report[$entityTypeId] = [];

      foreach ($this->samplerPluginManager->getDefinitions() as $definition) {
        /** @var \Drupal\sampler\SamplerInterface $instance */
        $instance = $this->samplerPluginManager->createInstance($definition['id']);
        if (!$instance->setEntityType($entityTypeId)) {
          continue;
        }

        $instance->anonymize($this->anonymize);

        $collection = $instance->collect();
        $collectionKey = $instance->key();
        if (is_array($collection) && isset($this->report[$entityTypeId][$collectionKey])) {
          $this->report[$entityTypeId][$collectionKey] = array_merge($this->report[$entityTypeId][$collectionKey], $collection);
        }
        else {
          $this->report[$entityTypeId][$collectionKey] = $collection;
        }
      }
    }

    return $this;
  }

  /**
   * Print the report.
   *
   * @param null|string $file
   *   The file to put the report into.
   */
  public function output($file = NULL) {
    $report = $this->getReport(TRUE);

    if ($file) {
      file_put_contents($file, $report);
    }
    else {
      print $report;
    }
  }

  /**
   * Get the report.
   *
   * @param bool $formatted
   *   If set to true, the report will be formatted into a string.
   *
   * @return array|string
   *   The report.
   */
  public function getReport(bool $formatted = FALSE) {
    if ($formatted === TRUE) {
      return $this->getFormattedReport();
    }
    return $this->report;
  }

  /**
   * Set anonymize flag.
   *
   * If set to true, bundle and role names are anonymized in output.
   * Otherwise the machine name of bundles and roles are printed.
   *
   * @param bool $anonymize
   *   Anonymize or not.
   *
   * @return $this
   */
  public function anonymize(bool $anonymize): Reporter {
    $this->anonymize = $anonymize;

    return $this;
  }

  /**
   * Format the report.
   *
   * @return string
   *   The formatted report.
   */
  protected function getFormattedReport(): string {
    return json_encode($this->report, JSON_PRETTY_PRINT);
  }

}
