<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Mac\Objects\MacApplication;

/**
 * Installer configuration class for JetBrains PhpStorm.
 *
 * @see     https://www.jetbrains.com/phpstorm/
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class JetbrainsPhpStorm extends BaseInstaller
{
  use ShellTrait;

  protected $destination;

  const BASEURL = 'https://download.jetbrains.com/product?code=%s&latest&distribution=%s';

  public function getDownloadUrl()
  {
    if ($arch = $this->getDevice()->getCpuType())
    {
      $dist = 'apple' === $arch ? 'macM1' : 'mac';

      return sprintf(static::BASEURL, 'PS', $dist);
    }

    return null;
  }

  public function getName()
  {
    return 'JetBrains PhpStorm';
  }

  public function getPath()
  {
    return '/Applications/PhpStorm.app';
  }

  /**
   * @return string
   */
  public function getInstalledVersion()
  {
    return (new MacApplication($this->getPath()))->getShortVersion()->__toString();
  }

  public function getCurrentVersion()
  {
    return $this->getVersionFromUrl($this->getDestinationUrl());
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getDestinationUrl()
  {
    if (empty($this->destination))
    {
      $this->destination = $this->getShellExec(sprintf('curl -fsIL "%s" | grep -i "location" | tail -1', $this->getDownloadUrl()));
    }

    return $this->destination;
  }
}
