<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ZipInstallCommand
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 * @package DevCoding\Jss\Easy\Command\Download
 */
class ZipInstallCommand extends AbstractDownloadConsole
{
  protected function isTargetOption()
  {
    return true;
  }

  /**
   * @return string
   */
  protected function getDownloadExtension()
  {
    return 'zip';
  }

  protected function configure()
  {
    parent::configure();

    $this->setName('install:zip')->addArgument('url', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Check Vs. Current if Provided
    $retval = $this->executeUpgradeCheck($input, $output);
    if (self::CONTINUE !== $retval)
    {
      return $retval;
    }

    // Download & Install
    $retval = $this->executeDownload($input, $output);
    if (self::CONTINUE !== $retval)
    {
      return $retval;
    }

    // Mount the DMG
    $this->io()->msg('Uncompressing ZIP File', 50);
    $zipFile = $this->getDownloadFile();
    if (!$unzip = $this->unzip($zipFile, $error))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  '.$error);

      $retval = self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    if (self::CONTINUE === $retval)
    {
      $this->io()->msg('Checking ZIP for File', 50);
      if ($File = $this->getDestinationFromDir($unzip))
      {
        $this->successbg('FOUND');
        if ($File instanceof MacApplication)
        {
          // Verify we don't have a version mismatch
          $offer  = $File->getShortVersion();
          $target = $this->getTargetVersion();
          if ($offer && $target)
          {
            if ($target->eq($offer))
            {
              $retval = self::CONTINUE;
            }
            else
            {
              $this->io()->msg('Comparing Versions', 50);
              $this->errorbg('NO MATCH');
              $retval = self::EXIT_ERROR;
            }
          }
          elseif ($offer)
          {
            $this->setTargetVersion($offer);
            $this->io()->msg('Is Update Needed?', 50);
            $retval = $this->isInstallNeeded($offer);
            $badge  = self::CONTINUE == $retval ? 'yes' : 'no';
            $this->successbg($badge);
          }
          else
          {
            $this->io()->msg('Comparing Versions', 50);
            $this->errorbg('ERROR');
            $this->io()->write('  Could not determine version within DMG.');
            $retval = self::EXIT_ERROR;
          }
        }
        else
        {
          $retval = $this->executeOverwriteCheck($input, $output);
        }
      }
      else
      {
        $this->errorbg('NOT FOUND');

        $retval = self::EXIT_ERROR;
      }
    }

    // Perform Installation
    if (self::CONTINUE === $retval && isset($File))
    {
      if ($File instanceof MacApplication)
      {
        $this->io()->msg('Installing APP Bundle', 50);
        if (!$this->installAppFile($File, $errors))
        {
          $retval = self::EXIT_ERROR;
        }
        else
        {
          $retval = self::EXIT_SUCCESS;
        }
      }
      elseif ($File instanceof PkgFile)
      {
        $this->io()->msg('Installing from PKG', 50);
        if (!$this->installPkgFile($File, $errors))
        {
          $retval = self::EXIT_ERROR;
        }
        else
        {
          $retval = self::EXIT_SUCCESS;
        }
      }
      else
      {
        $this->io()->msg('Copying File to Destination', 50);
        if (!$this->installFile($File, $errors))
        {
          $retval = self::EXIT_ERROR;
        }
        else
        {
          $retval = self::EXIT_SUCCESS;
        }
      }

      if (self::EXIT_ERROR === $retval)
      {
        $this->errorbg('ERROR');
        if (!empty($errors))
        {
          $errors = explode("\n", $errors);
          foreach ($errors as $error)
          {
            $this->io()->writeln('  '.$error);
          }
        }
      }
      else
      {
        $this->successbg('SUCCESS');
      }
    }

    // Verify Installation
    $this->io()->msg('Verifying Installation', 50);
    $target = $this->getTargetVersion();
    if (!$this->isInstalled())
    {
      $this->errorbg('error');
      $this->io()->writeln('  Application not found at destination: '.$this->getDestination());
    }
    elseif ($target && !$this->isVersionMatch($target))
    {
      $retval = self::EXIT_ERROR;

      $this->errorbg('error');
      if ($new = $this->getAppVersion($this->getDestination()))
      {
        $this->io()->writeln(sprintf('  New Version (%s) != Target Version (%s)!', $new, $target));
      }
      else
      {
        $this->io()->writeln('  Cannot read new version number!');
      }
    }
    else
    {
      $retval = self::EXIT_SUCCESS;

      $this->successbg('SUCCESS');
    }

    // Clean Up
    $this->io()->msg('Cleaning Up', 50);
    if (file_exists($zipFile) && !unlink($zipFile))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  Download: '.$zipFile);
      $this->io()->write('  Download File could not be removed.');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    return $retval;
  }

  protected function isInstallNeeded($version)
  {
    return !$this->isInstalled() || $this->isOverwrite() || $this->isVersionGreater($version);
  }

  /**
   * @param string $volume
   *
   * @return PkgFile|MacApplication|string|null
   */
  protected function getDestinationFromDir($volume)
  {
    if (!$file = $this->getAppFromDir($volume))
    {
      if (!$file = $this->getPkgFromDir($volume))
      {
        $file = $this->getMatchFromDir($volume);
      }
    }

    return $file;
  }

  /**
   * @param string $volume
   *
   * @return PkgFile|null
   */
  protected function getPkgFromDir($volume)
  {
    $pkgFiles = glob($volume.'/*.pkg');
    if (1 == count($pkgFiles))
    {
      $path = reset($pkgFiles);

      return new PkgFile($path);
    }

    return null;
  }

  /**
   * @param string $dir
   *
   * @return MacApplication|null
   */
  protected function getAppFromDir($dir)
  {
    if ($srcFile = $this->getMatchFromDir($dir))
    {
      if ($this->isAppBundle($srcFile))
      {
        return new MacApplication($srcFile);
      }
    }

    return null;
  }

  /**
   * @param string $dir
   *
   * @return mixed|null
   */
  protected function getMatchFromDir($dir)
  {
    $destFile = pathinfo($this->getDestination(), PATHINFO_BASENAME);

    foreach (glob($dir.'/*') as $file)
    {
      if ($destFile == pathinfo($file, PATHINFO_BASENAME))
      {
        return $file;
      }
    }

    return null;
  }

  /**
   * @param string $zipFile
   * @param string $error
   *
   * @return false|string
   */
  protected function unzip($zipFile, &$error)
  {
    $dest    = tempnam($this->getCacheDir(), pathinfo($zipFile, PATHINFO_FILENAME).'-');
    unlink($dest);
    $cmd     = sprintf('/usr/bin/unzip "%s" -d "%s"', $zipFile, $dest);
    $Process = $this->getProcessFromShellCommandLine($cmd);
    $Process->run();

    if (!$Process->isSuccessful())
    {
      $error = $Process->getErrorOutput();
    }
    else
    {
      return $dest;
    }

    return false;
  }
}
