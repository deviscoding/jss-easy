<?php

namespace DevCoding\Jss\Easy\Command\Preferences\CC;

use DevCoding\Mac\Objects\AdobeApplication;
use DevCoding\Mac\Objects\MacUser;

/**
 * Trait with shared function for CLI commands related to Adobe Preferences.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
trait BackupTrait
{
  /**
   * @return MacUser
   */
  abstract protected function getUser();

  /**
   * @param string $dir
   *
   * @return $this
   */
  abstract protected function rrmdir($dir);

  /**
   * @param string $slug
   * @param string $year
   *
   * @return AdobeApplication|null
   */
  protected function getAdobeApplication($slug, $year = null)
  {
    return AdobeApplication::fromSlug($slug, $year);
  }

  /**
   * @param AdobeApplication $app
   *
   * @return string
   */
  protected function getBackupPath($app)
  {
    if ($year = $app->getYear())
    {
      return sprintf('%s/Preferences/MDM Easy/Adobe/%s/%s', $this->getUser()->getLibrary(), $app->getSlug(), $year);
    }
    else
    {
      return sprintf('%s/Preferences/MDM Easy/Adobe/%s', $this->getUser()->getLibrary(), $app->getSlug());
    }
  }

  /**
   * @param AdobeApplication $ccApp
   * @param string           $dest
   *
   * @return bool
   *
   * @throws \Exception
   */
  protected function doPreferenceBackup($ccApp, $dest)
  {
    $dest = $dest.'/tmp';
    if (!is_dir($dest))
    {
      if (!file_exists($dest))
      {
        @mkdir($dest, 0777, true);
      }
      else
      {
        throw new \Exception('Could not create backup destination directory');
      }
    }

    $userDir = $this->getUser()->getDir();
    if ($prefDirs = $ccApp->getPreferencePaths())
    {
      foreach ($prefDirs as $preference)
      {
        $path = sprintf('%s/%s', $userDir, $preference);
        if (file_exists($path))
        {
          $cmd = sprintf('rsync -aP --ignore-times "%s" "%s/"', $path, $dest);
          exec($cmd, $output, $retval);
          if (0 !== $retval)
          {
            throw new \Exception('An error was encountered backing up preferences: ');
          }
        }
      }

      $date    = date('Ymd-Hi');
      $zipFile = sprintf('backup-%s.zip', $date);
      $zipPath = dirname($dest).'/'.$zipFile;
      $cmd     = sprintf('cd "%s" && zip -r "%s" ./* && cd -', $dest, $zipPath);
      exec($cmd, $output, $retval);
      if ($retval || !file_exists($zipPath))
      {
        throw new \Exception('Could not compress the backup after copying.');
      }
      else
      {
        $this->rrmdir($dest);
      }
    }
    else
    {
      throw new \Exception('Could not find preferences for '.$ccApp->getName());
    }

    return true;
  }
}
