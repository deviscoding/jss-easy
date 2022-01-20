<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Exception\DmgMountException;
use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to install an application or binary file from a DMG. Verifies that the DMG should be downloaded, then
 * downloads, mounts, verifies source, installs, unmounts, and cleans up.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
class DmgInstallCommand extends AbstractArchiveInstallConsole
{
  /** @var array An array of mounted DMG files */
  protected $mounts;

  /**
   * {@inheritDoc}
   */
  protected function isTargetOption()
  {
    return true;
  }

  /**
   * {@inheritDoc}
   */
  protected function getDownloadExtension()
  {
    return 'dmg';
  }

  /**
   * Sets the command name and adds the URL argument.
   *
   * @return void
   */
  protected function configure()
  {
    parent::configure();

    $this->setName('install:dmg')->addArgument('url', InputArgument::REQUIRED);
  }

  /**
   * {@inheritDoc}
   */
  protected function executeCleanup(InputInterface $input, OutputInterface $output)
  {
    $this->io()->msg('Cleaning Up', 50);
    $retval  = self::EXIT_SUCCESS;
    $dmgFile = $this->getDownloadFile();
    if (!$this->unmount($dmgFile, $error))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  '.$error);

      $retval = self::EXIT_ERROR;
    }

    if (file_exists($dmgFile) && !unlink($dmgFile))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  Download: '.$dmgFile);
      $this->io()->write('  Download File could not be removed.');

      $retval = self::EXIT_ERROR;
    }

    if (self::EXIT_SUCCESS === $retval)
    {
      $this->successbg('SUCCESS');
    }

    return $retval;
  }

  /**
   * {@inheritDoc}
   */
  protected function executeExtract(InputInterface $input, OutputInterface $output)
  {
    $this->io()->msg('Mounting DMG File', 50);
    try
    {
      $mount  = $this->mount($this->getDownloadFile());
      $volume = $mount['volume'];

      $this->successbg('SUCCESS');
    }
    catch (DmgMountException $e)
    {
      $this->errorbg('ERROR');
      $this->io()->write('  ', $e->getMessage());

      return self::EXIT_ERROR;
    }

    $this->io()->msg('Checking DMG for File', 50);
    if (!$this->getSource())
    {
      $this->errorbg('NOT FOUND');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('FOUND');
    }

    return self::CONTINUE;
  }

  /**
   * Returns the source application bundle, package file, or path, as determined from the extracted download file.
   *
   * @return PkgFile|MacApplication|string|null
   *
   * @throws DmgMountException
   */
  protected function getSource()
  {
    $mount = $this->mount($this->getDownloadFile());

    return $this->getSourceFromVolume($mount['volume']);
  }

  /**
   * Attempts to find a PKG installer file, application bundle, or other destination file match within the given volume.
   *
   * @param string $volume
   *
   * @return PkgFile|MacApplication|string|null
   */
  protected function getSourceFromVolume($volume)
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
   * Attempts to find a PKG installer file in the given volume, and returns a PkgFile object.
   *
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
   * Attempts to find an application bundle that matches the destination in the given volume, and returns the object.
   *
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

  /**
   * Attempts to find the destination filename in the given volume, and returns the absolute path.
   *
   * @param string $volume
   *
   * @return string|null
   */
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

  /**
   * Evaluates whether the given DMG file is mounted, then unmounts the DMG if needed.
   *
   * @param string $dmgFile the absolute path to the DMG
   * @param string $error   any error encountered during the unmount process
   *
   * @return bool TRUE if successful, FALSE if not
   */
  protected function unmount($dmgFile, &$error)
  {
    if (!empty($this->mounts[$dmgFile]))
    {
      $mount = $this->mounts[$dmgFile];
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
    }

    return true;
  }

  /**
   * Evaluates whether the given DMG file is already mounted, then mounts the DMG if needed.  Returns the volume and
   * device in an array of ['dev' => <device>, 'volume' => <volume>]
   *
   * @param string $dmgFile
   *
   * @return array
   *
   * @throws DmgMountException
   */
  protected function mount($dmgFile)
  {
    if (empty($this->mounts[$dmgFile]))
    {
      $cmd     = sprintf('/usr/bin/hdiutil attach "%s" -nobrowse', $dmgFile);
      $Process = $this->getProcessFromShellCommandLine($cmd);
      $Process->run();

      if (!$Process->isSuccessful())
      {
        throw new DmgMountException($dmgFile, $Process->getErrorOutput() ?? $Process->getOutput());
      }
      else
      {
        $output = explode("\n", $Process->getOutput());
        foreach ($output as $line)
        {
          if (preg_match('/^\/dev\/([^\s]+)\s+([^\/]+)(\/Volumes\/(.*))$/', $line, $matches))
          {
            $this->mounts[$dmgFile] = ['dev' => $matches[1], 'volume' => $matches[3]];
          }
        }

        if (empty($this->mounts[$dmgFile]))
        {
          throw new DmgMountException($dmgFile, 'Could not determine mount point!');
        }
      }
    }

    return $this->mounts[$dmgFile];
  }
}
