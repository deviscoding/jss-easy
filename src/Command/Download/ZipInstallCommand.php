<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Exception\ZipExtractException;
use DevCoding\Jss\Easy\Object\File\PkgFile;
use DevCoding\Mac\Objects\MacApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to install an application or binary file from a ZIP. Verifies that the ZIP should be downloaded, then
 * downloads, extracts, verifies source, installs, and cleans up both extracted files and downloaded files.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
class ZipInstallCommand extends AbstractArchiveInstallConsole
{
  /**
   * @var string[]
   */
  /** @var string[] an array of unzipped files and extract destinations */
  protected $unzipped;

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
    return 'zip';
  }

  /**
   * Sets the command name and adds the URL argument.
   *
   * @return void
   */
  protected function configure()
  {
    parent::configure();

    $this->setName('install:zip')->addArgument('url', InputArgument::REQUIRED);
  }

  /**
   * {@inheritDoc}
   */
  protected function executeExtract(InputInterface $input, OutputInterface $output)
  {
    $this->io()->msg('Decompressing ZIP File', 50);

    try
    {
      $this->unzip($this->getDownloadFile());
      $this->successbg('SUCCESS');

      return self::CONTINUE;
    }
    catch (ZipExtractException $e)
    {
      $this->errorbg('ERROR');
      $this->io()->write('  '.$e->getMessage());

      return self::EXIT_ERROR;
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function executeCleanup(InputInterface $input, OutputInterface $output)
  {
    $this->io()->msg('Cleaning Up', 50);
    $retval  = self::EXIT_SUCCESS;
    $zipFile = $this->getDownloadFile();
    if (!empty($this->unzipped[$zipFile]))
    {
      try
      {
        $this->rrmdir($this->unzipped[$zipFile]);
      }
      catch (\Exception $e)
      {
        $this->errorbg('ERROR');
        $this->io()->write('  '.$e->getMessage());

        $retval = self::EXIT_ERROR;
      }
    }

    if (file_exists($zipFile) && !unlink($zipFile))
    {
      $this->errorbg('ERROR');
      $this->io()->write('  Download: '.$zipFile);
      $this->io()->write('  Download File could not be removed.');

      $retval = self::EXIT_ERROR;
    }

    if (self::EXIT_SUCCESS === $retval)
    {
      $this->successbg('SUCCESS');
    }
  }

  /**
   * Returns the source application bundle, package file, or path, as determined from the extracted download file.
   *
   * @return string|null
   * @throws ZipExtractException
   */
  protected function getSource()
  {
    $unzip = $this->unzip($this->getDownloadFile());

    return $this->getDestinationFromDir($unzip);
  }

  /**
   * Returns the source application bundle, package file, or path from the given directory.
   *
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
   * Attempts to find a PKG installer file in the given volume, and returns a PkgFile object.
   *
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
   * Attempts to find an application bundle that matches the destination in the given volume, and returns the object.
   *
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
   * Attempts to find the destination filename in the given volume, and returns the absolute path.
   *
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
   * Unzips the given zipfile into a temporary directory, and returns the path to that directory.  If the zip file has
   * already been extracted during this command run, returns the path without re-extraction.
   *
   * @param string $zipFile
   *
   * @return string|null
   *
   * @throws ZipExtractException
   */
  protected function unzip($zipFile)
  {
    if (!isset($this->unipped[$zipFile]))
    {
      $dest = tempnam($this->getCacheDir(), pathinfo($zipFile, PATHINFO_FILENAME).'-');
      unlink($dest);
      $cmd     = sprintf('/usr/bin/unzip "%s" -d "%s"', $zipFile, $dest);
      $Process = $this->getProcessFromShellCommandLine($cmd);
      $Process->run();

      if (!$Process->isSuccessful())
      {
        throw new ZipExtractException($zipFile, $Process->getErrorOutput() ?? $Process->getOutput());
      }
      else
      {
        return $this->unzipped[$zipFile] = $dest;
      }
    }

    return $this->unzipped[$zipFile];
  }
}
