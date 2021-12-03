<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\MacDevice;

/**
 * Abstract class to extend for configured installers used in ConfiguredInstallCommand.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 * @package DevCoding\Jss\Easy\Object\Installer
 */
abstract class BaseInstaller
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
    return (new MacApplication($this->getPath()))->getShortVersion()->__toString();
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
    $ver = str_replace('.', '_', (string)$this->getDevice()->getOs()->getVersion());

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

  abstract public function getName();

  abstract public function getPath();

  abstract public function getDownloadUrl();

  abstract public function getDestinationUrl();

  abstract public function getInstallerType();

  abstract public function getCurrentVersion();
}
