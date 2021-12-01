<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DmgInstallCommand extends AbstractDownloadConsole
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
    return 'dmg';
  }

  protected function configure()
  {
    parent::configure();

    $this->setName('install:dmg')->addArgument('url', InputArgument::REQUIRED);
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
    $this->io()->msg('Mounting DMG File', 50);
    $dmgFile = $this->getDownloadFile();
    if (!$mount = $this->mount($dmgFile, $error))
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
      $this->io()->msg('Checking DMG for File', 50);
      if ($File = $this->getDestinationFromVolume($mount['volume']))
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

    // Unmount
    $this->io()->msg('Unmounting Volume', 50);
    if (!$this->unmount($mount, $error))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  '.$error);
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    // Clean Up
    $this->io()->msg('Cleaning Up', 50);
    if (file_exists($dmgFile) && !unlink($dmgFile))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  Download: '.$dmgFile);
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
  protected function getDestinationFromVolume($volume)
  {
    if (!$file = $this->getAppFromVolume($volume))
    {
      if (!$file = $this->getPkgFromVolume($volume))
      {
        $file = $this->getMatchFromVolume($volume);
      }
    }

    return $file;
  }

  /**
   * @param string $volume
   *
   * @return PkgFile|null
   */
  protected function getPkgFromVolume($volume)
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
   * @param string $volume
   *
   * @return MacApplication|null
   */
  protected function getAppFromVolume($volume)
  {
    if ($srcFile = $this->getMatchFromVolume($volume))
    {
      if ($this->isAppBundle($srcFile))
      {
        return new MacApplication($srcFile);
      }
    }

    return null;
  }

  protected function getMatchFromVolume($volume)
  {
    $destFile = pathinfo($this->getDestination(), PATHINFO_BASENAME);

    foreach (glob($volume.'/*') as $file)
    {
      if ($destFile == pathinfo($file, PATHINFO_BASENAME))
      {
        return $file;
      }
    }

    return null;
  }

  protected function unmount($mount, &$error)
  {
    if (is_dir($mount['volume']))
    {
      $cmd     = sprintf('/usr/bin/hdiutil detach "%s"', $mount['dev']);
      $Process = $this->getProcessFromShellCommandLine($cmd);
      $Process->run();

      if (!$Process->isSuccessful())
      {
        $error = 'volume: '.$mount['volume']."\ndevice: ".$mount['dev']."\n";
        $error .= $Process->getErrorOutput();

        return false;
      }

      $x = 0;
      do
      {
        ++$x;
        if ($x > 30)
        {
          $error = 'volume: '.$mount['volume']."\ndevice: ".$mount['dev']."\n";
          $error .= 'Volume still exists after unmount.';

          return false;
        }

        sleep(1);
      } while (is_dir($mount['volume']));
    }

    return true;
  }

  protected function mount($dmgFile, &$error)
  {
    $cmd     = sprintf('/usr/bin/hdiutil attach "%s" -nobrowse', $dmgFile);
    $Process = $this->getProcessFromShellCommandLine($cmd);
    $Process->run();

    if (!$Process->isSuccessful())
    {
      $error = $Process->getErrorOutput();
    }
    else
    {
      $output = explode("\n", $Process->getOutput());
      foreach ($output as $line)
      {
        if (preg_match('/^\/dev\/([^\s]+)\s+([^\/]+)(\/Volumes\/(.*))$/', $line, $matches))
        {
          return ['dev' => $matches[1], 'volume' => $matches[3]];
        }
      }

      $error = 'Could not determine mount point!';
    }

    return false;
  }
}
