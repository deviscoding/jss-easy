<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\MacDevice;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Abstract class to extend for configured installers used in ConfiguredInstallCommand.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Recipe
 */
abstract class AbstractRecipe
{
  /** @var MacDevice */
  protected $device;

  /**
   * @param MacDevice $device
   */
  public function __construct(MacDevice $device)
  {
    $this->device = $device;
  }

  /**
   * @return MacApplication|null
   */
  public function getApplication()
  {
    if ($this->isInstalled())
    {
      return new MacApplication($this->getPath());
    }

    return null;
  }

  /**
   * @return MacDevice
   */
  public function getDevice(): MacDevice
  {
    return $this->device;
  }

  /**
   * @return string
   */
  public function getInstalledVersion()
  {
    if (file_exists($this->getPath().'/Contents/Info.plist'))
    {
      return (new MacApplication($this->getPath()))->getShortVersion()->__toString();
    }

    return null;
  }

  /**
   * Evaluates whether the application this installer represents is currently installed at the default path.
   *
   * @return bool
   */
  public function isInstalled()
  {
    return file_exists($this->getPath());
  }

  /**
   * Evaluates whether the installed version is current or better.
   *
   * @return bool
   */
  public function isCurrent()
  {
    if ($this->isInstalled())
    {
      if ($current = $this->getCurrentVersion())
      {
        $installed = $this->getInstalledVersion();

        // If the strings match, good enough
        if ($current != $installed)
        {
          // Otherwise, we'll break them down and compare them.
          $cVer = new SemanticVersion($current);
          $iVer = new SemanticVersion($installed);

          if ($iVer->lt($cVer))
          {
            // It's ok if the installed version is greater than current version,
            // though shouldn't really happen.
            return false;
          }
        }

        return true;
      }
    }

    // Default to False
    return false;
  }

  protected function getInstallerTypeFromUrl($url)
  {
    if ($parsed = parse_url($url))
    {
      return strtolower(pathinfo($parsed['path'], PATHINFO_EXTENSION));
    }

    return null;
  }

  protected function getUserAgent()
  {
    $ver = str_replace('.', '_', (string) $this->getDevice()->getOs()->getVersion());

    return sprintf('Mozilla/5.0 (Macintosh; Intel Mac OS X %s) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2',
        $ver);
  }

  /**
   * @param string $url
   * @param string $pattern
   *
   * @return string|null
   */
  protected function getVersionFromUrl($url, $pattern = '#.*/[a-zA-Z-]*(?<version>[0-9.]*)-#')
  {
    if (preg_match($pattern, $url, $matches))
    {
      return str_replace('_', '.', $matches['version']);
    }

    return null;
  }

  /**
   * @return bool
   */
  protected function isAppleSilicon()
  {
    return $this->getDevice()->isAppleChip();
  }

  abstract public function getName();

  abstract public function getPath();

  abstract public function getDownloadUrl();

  abstract public function getDestinationUrl();

  abstract public function getInstallerType();

  abstract public function getCurrentVersion();
}
