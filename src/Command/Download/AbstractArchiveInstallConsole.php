<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Exception\DmgMountException;
use DevCoding\Jss\Easy\Exception\ZipExtractException;
use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for install commands which download an archive or disk image.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
abstract class AbstractArchiveInstallConsole extends AbstractInstallConsole
{
  /**
   * Must extract the downloaded file, and provide user feedback on the success of those operations.
   * Must return -1 for success (implying that installation should continue) or 1 for an error.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   * @throws DmgMountException
   * @throws ZipExtractException
   */
  abstract protected function executeExtract(InputInterface $input, OutputInterface $output);

  /**
   * Must clean up any downloaded or extract files, and provide the user feedback on the success of those operations.
   * Must return 0 for success, or 1 for an error.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  abstract protected function executeCleanup(InputInterface $input, OutputInterface $output);

  /**
   * Must return the extracted source file as a PkgFile, MacApplication, or string.
   *
   * @return PkgFile|MacApplication|string|null
   *
   * @throws DmgMountException
   * @throws ZipExtractException
   */
  abstract protected function getSource();

  /**
   * Performs a check to determine if the file should be downloaded & installed, downloads, extracts, verifies source,
   * installs, verifies the installed application, then cleans up.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   * @throws DmgMountException
   * @throws ZipExtractException
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
        // Mount the DMG & Getting Source File
        $retval = $this->executeExtract($input, $output);
        if (self::CONTINUE === $retval)
        {
          // Verify Source File Is Valid
          $retval = $this->executeVerifySource($input, $output);
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
   * Verifies that the extracted source contains the destination application, or a PKG installer.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   *
   * @throws DmgMountException|ZipExtractException
   */
  protected function executeVerifySource(InputInterface $input, OutputInterface $output)
  {
    $source = $this->getSource();

    if (!empty($source))
    {
      if ($source instanceof PkgFile)
      {
        return self::CONTINUE;
      }
      else
      {
        $this->io()->msg('Verifying File', 50);
        if ($source instanceof MacApplication)
        {
          if ($this->getRecipe()->isMatch($source))
          {
            $target = $this->getTargetVersion();
            $this->successbg($target ?? 'MATCH');
            if (!$target)
            {
              $this->setTargetVersion($target);
            }

            return self::CONTINUE;
          }
          else
          {
            $this->errorbg('NO MATCH');

            return self::EXIT_ERROR;
          }
        }
        elseif (is_file($source))
        {
          $sFile = pathinfo($source, PATHINFO_BASENAME);
          $dFile = pathinfo($this->getDestination(), PATHINFO_BASENAME);

          if ($sFile === $dFile)
          {
            $this->successbg('MATCH');

            return self::CONTINUE;
          }
          else
          {
            $this->errorbg('NO MATCH');

            return self::EXIT_ERROR;
          }
        }
      }
    }

    return self::EXIT_ERROR;
  }

  /**
   * Installs the application using from a PKG or by copying the file or application bundle.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   *
   * @throws DmgMountException|ZipExtractException
   */
  protected function executeInstall(InputInterface $input, OutputInterface $output)
  {
    $retval = self::CONTINUE;
    $source = $this->getSource();
    $errors = null;
    if ($source instanceof MacApplication)
    {
      $this->io()->msg('Installing APP Bundle', 50);
      if (!$this->installAppFile($source, $errors))
      {
        $retval = self::EXIT_ERROR;
      }
    }
    elseif ($source instanceof PkgFile)
    {
      $this->io()->msg('Installing from PKG', 50);
      if (!$this->installPkgFile($source, $errors))
      {
        $retval = self::EXIT_ERROR;
      }
    }
    elseif (is_file($source))
    {
      $this->io()->msg('Copying File to Destination', 50);
      if (!$this->installFile($source, $errors))
      {
        $retval = self::EXIT_ERROR;
      }
    }
    else
    {
      $retval = self::EXIT_ERROR;
      $errors = 'No Source File Found';
    }

    if (!empty($errors))
    {
      $this->errorbg('ERROR');
      $errors = explode("\n", $errors);
      foreach ($errors as $error)
      {
        $this->io()->writeln('  '.$error);
      }
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    return $retval;
  }
}
