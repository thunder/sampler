<?php

namespace Drupal\sampler\Command;

use Drupal\sampler\Reporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
// phpcs:disable
use Drupal\Console\Annotations\DrupalCommand;
// phpcs:enable

/**
 * Class ReportCommand.
 *
 * @DrupalCommand (
 *     extension="sampler",
 *     extensionType="module"
 * )
 */
class ReportCommand extends ContainerAwareCommand {

  /**
   * The reporter instance.
   *
   * @var \Drupal\sampler\Reporter
   */
  protected $reporter;

  /**
   * Constructs a new ReportCommand object.
   */
  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sampler:report')
      ->addOption('file', NULL, InputOption::VALUE_OPTIONAL, $this->trans('commands.sampler.report.options.file'), NULL)
      ->addOption('anonymize', NULL, InputOption::VALUE_OPTIONAL, $this->trans('commands.sampler.report.options.anonymize'), 1)
      ->setDescription($this->trans('commands.sampler.report.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $file = $input->getOption('file');
    $anonymize = $input->getOption('anonymize');

    $this->reporter
      ->setAnonymize($anonymize)
      ->collect()
      ->output($file);
  }

}
