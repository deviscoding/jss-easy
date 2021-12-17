<?php

namespace DevCoding\Jss\Easy\Command\Preferences\CC;

use DevCoding\Mac\Command\AbstractMacConsole;
use DevCoding\Mac\Objects\AdobeApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to back up the preferences for an Adobe Creative Cloud application.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class BackupCommand extends AbstractMacConsole
{
  use BackupTrait;

  public function configure()
  {
    $this->setName('adobe:backup');
    $this->setDescription('Backs up the preferences of an Adobe Creative Cloud application.');
    $this->addArgument('application', InputArgument::REQUIRED);
    $this->addArgument('year', InputArgument::OPTIONAL);

    parent::configure();
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    $app   = strtolower(str_replace(' ', '-', $this->io()->getArgument('application')));
    $year  = $this->io()->getArgument('year');
    $ccApp = AdobeApplication::fromSlug($app, $year);

    $this->io()->msg('Locating Adobe Application', 50);
    if ($ccApp->getPath())
    {
      $this->io()->successln('[DONE]');

      try
      {
        $this->io()->msg('Backing Up '.$ccApp->getName().' Preferences', 50);
        $this->doPreferenceBackup($ccApp, $this->getBackupPath($ccApp));
        $this->io()->successln('[DONE]');
      }
      catch (\Exception $e)
      {
        $this->io()->errorln('[ERROR]');
        $this->io()->errorblk($e->getMessage());

        return self::EXIT_ERROR;
      }
    }
    else
    {
      $this->io()->errorln('[ERROR]');

      $this->io()->errorblk('Could not locate the requested application.');

      return self::EXIT_ERROR;
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * @return bool
   */
  protected function isAllowUserOption()
  {
    return true;
  }
}
