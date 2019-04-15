<?php

namespace Drupal\sampler\Command;

use Drupal\sampler\ConfigCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
// phpcs:disable
use Drupal\Console\Annotations\DrupalCommand;
// phpcs:enable

/**
 * Class CreateConfigCommand.
 *
 * @DrupalCommand (
 *     extension="sampler",
 *     extensionType="module"
 * )
 */
class CreateConfigCommand extends ContainerAwareCommand {

  /**
   * The reporter instance.
   *
   * @var \Drupal\sampler\ConfigCreator
   */
  protected $configCreator;

  /**
   * Constructs a new CreateConfigCommand object.
   */
  public function __construct(ConfigCreator $configCreator) {
    $this->configCreator = $configCreator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sampler:create-config')
      ->addArgument('file', InputArgument::REQUIRED, $this->trans('commands.sampler.create-config.arguments.file'))
      ->setDescription($this->trans('commands.sampler.create-config.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $file = $input->getArgument('file');

    $this->configCreator
      ->setReportData($file)
      ->cleanup()
      ->create();
  }

}
