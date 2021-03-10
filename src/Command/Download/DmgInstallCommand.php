<?php

namespace DevCoding\Jss\Helper\Command\Download;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DmgInstallCommand extends AbstractDownloadConsole
{
  protected function isTargetOption()
  {
    return true;
  }

  /**
   * @return false|string
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
    $checks = $this->executeChecks($input, $output);
    if (self::CONTINUE !== $checks)
    {
      return $checks;
    }

    // Download & Install
    $download = $this->executeDownload($input, $output);
    if (self::CONTINUE !== $download)
    {
      return $download;
    }

    // Mount the DMG
    $this->io()->msg('Mounting DMG File', 50);
    $dmgFile = $this->getDownloadFile();
    if (!$mount = $this->mount($dmgFile, $error))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  '.$error);

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    $volume = $mount['volume'];
    $device = $mount['dev'];
    if ($srcFile = $this->getDestinationFileFromVolume($volume))
    {
      $this->io()->write('Copying File to Destination');
      // Copy the File
      if (!copy($srcFile, $this->getDestination()))
      {
        $this->errorbg('ERROR');

        return self::EXIT_ERROR;
      }
    }
    elseif ($pkgFile = $this->getPkgFromVolume($volume))
    {
      $this->io()->msg('Installing from PKG');
      // Install the PKG
      if (!$this->installPkgFile($pkgFile, $errors))
      {
        $this->errorbg('error');
        $aErrors = explode("\n", $errors);
        foreach ($aErrors as $error)
        {
          $this->io()->write('  '.$error);
        }

        return self::EXIT_ERROR;
      }
    }

    // Verify Installation
    $target = $this->getTargetVersion();
    if (!$this->isInstalled())
    {
      $this->errorbg('error');
      $this->io()->write('  Application not found at destination: '.$this->getDestination());

      return self::EXIT_ERROR;
    }
    elseif ($target && !$this->isVersionMatch($target))
    {
      $this->errorbg('error');
      if ($new = $this->getAppVersion($this->getDestination()))
      {
        $this->io()->write(sprintf('  New Version (%s) != Target Version (%s)!', $new, $target));
      }
      else
      {
        $this->io()->write('  Cannot read new version number!');
      }
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    // Unmount
    $this->io()->msg('Unmounting Volume');
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
    $this->io()->msg('Cleaning Up');
    if (file_exists($dmgFile) && !unlink($dmgFile))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  Download could not be removed.');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    return self::EXIT_SUCCESS;
  }

  protected function getPkgFromVolume($volume)
  {
    foreach (glob($volume.'/*.pkg') as $pkgFile)
    {
      return $pkgFile;
    }

    return null;
  }

  protected function getDestinationFileFromVolume($volume)
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
    $cmd     = sprintf('/usr/bin/hdiutil detach "%s" -quiet', $mount['dev']);
    $Process = Process::fromShellCommandline($cmd);
    $Process->run();

    if (!$Process->isSuccessful())
    {
      $error = $Process->getErrorOutput();

      return false;
    }

    $x = 0;
    do
    {
      ++$x;
      if ($x > 30)
      {
        $error = 'Volume still exists after unmount.';

        return false;
      }

      sleep(1);
    } while (is_dir($mount['volume']));

    return true;
  }

  protected function mount($dmgFile, &$error)
  {
    $cmd     = sprintf('/usr/bin/hdiutil attach "%s" -nobrowse', $dmgFile);
    $Process = Process::fromShellCommandline($cmd);
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
          return ['dev' => $matches[2], 'volume' => $matches[3]];
        }
      }

      $error = 'Could not determine mount point!';
    }

    return false;
  }
}
