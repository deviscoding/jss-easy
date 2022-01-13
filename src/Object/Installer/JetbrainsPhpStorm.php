<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;
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
    $dist = $this->isAppleSilicon() ? 'macM1' : 'mac';

    return sprintf(static::BASEURL, 'PS', $dist);
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
    return $this->getVersionFromUrl($this->getDestinationUrl(), '#PhpStorm-(?<version>[0-9.]+)-?(?<arch>[a-z0-9]+)?\.#');
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getDestinationUrl()
  {
    if (empty($this->destination))
    {
      $this->destination = (new DownloadHelper())->getRedirectUrl($this->getDownloadUrl(), $this->getUserAgent());
    }

    return $this->destination;
  }
}
