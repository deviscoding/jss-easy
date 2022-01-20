<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Exception\DmgMountException;
use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractArchiveInstallConsole extends AbstractInstallConsole
{
  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  abstract protected function executeExtract(InputInterface $input, OutputInterface $output);

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  abstract protected function executeCleanup(InputInterface $input, OutputInterface $output);

  /**
   * @return PkgFile|MacApplication|string|null
   *
   * @throws DmgMountException
   */
  abstract protected function getSource();

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
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   * @throws DmgMountException
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
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   * @throws DmgMountException
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
