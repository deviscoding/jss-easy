<?php

namespace DevCoding\Jss\Easy\Command\Download;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs a package using the provided download URL, and verifies that it is present at the provided destination.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
class PkgInstallCommand extends AbstractDownloadConsole
{
  /**
   * Specifies whether this command may use the '--target' option.
   *
   * @return bool
   */
  protected function isTargetOption()
  {
    return true;
  }

  /**
   * Returns the expected extension of downloaded files, in lowercase.
   *
   * @return string
   */
  protected function getDownloadExtension()
  {
    return 'pkg';
  }

  /**
   * Extension to set the name of the command, and add the 'url' argument.
   *
   * @return void
   */
  protected function configure()
  {
    parent::configure();

    $this->setName('install:pkg')->addArgument('url', InputArgument::REQUIRED);
  }

  /**
   * Executes the command by performing a check to see if the PKG should be downloaded, downloading and installing
   * the PKG file, then verifying that the application is present, and with the intended version.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Check Vs. Current if Provided
    $retval = $this->executeUpgradeCheck($input, $output);
    if (self::CONTINUE === $retval)
    {
      // Download & Install
      $retval = $this->executeDownload($input, $output);
      if (self::CONTINUE === $retval)
      {
        // Perform Installation
        $retval = $this->executeInstall($input, $output);
        if (self::CONTINUE === $retval)
        {
          // Verify Install
          $retval = $this->executeVerify($input, $output);
        }
      }
    }

    if (self::EXIT_SUCCESS === $retval && self::EXIT_SUCCESS === $this->executeCleanup($input, $output))
    {
      return self::EXIT_SUCCESS;
    }
    else
    {
      return self::EXIT_ERROR;
    }
  }

  /**
   * Installs the package from the download file, and shows feedback to the user.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function executeInstall(InputInterface $input, OutputInterface $output)
  {
    $pkgFile = $this->getDownloadFile();
    $this->io()->msg('Installing from PKG', 50);
    if (!$this->installPkgFile($pkgFile, $errors))
    {
      $this->errorbg('error');
      $aErrors = explode("\n", $errors);
      foreach ($aErrors as $error)
      {
        $this->io()->writeln('  '.$error);
      }

      return self::EXIT_ERROR;
    }

    return self::CONTINUE;
  }

  /**
   * Cleans up the downloaded file & shows feedback to the user.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function executeCleanup(InputInterface $input, OutputInterface $output)
  {
    $pkgFile = $this->getDownloadFile();
    $this->io()->msg('Cleaning Up', 50);
    if (file_exists($pkgFile) && !unlink($pkgFile))
    {
      $this->errorbg('ERROR');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');

      return self::EXIT_SUCCESS;
    }
  }
}
