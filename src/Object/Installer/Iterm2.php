<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Mac\Objects\MacApplication;

/**
 * Installer recipe class for iTerm2.
 *
 * @see     https://iterm2.com/
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Iterm2 extends AbstractRecipe
{
  use ShellTrait;

  protected $destination;

  public function getDownloadUrl()
  {
    return 'https://iterm2.com/downloads/stable/latest';
  }

  public function getName()
  {
    return 'iTerm2';
  }

  public function getPath()
  {
    return '/Applications/iTerm.app';
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
    return $this->getVersionFromUrl($this->getDestinationUrl(), '#([A-Za-z0-9]+)-(?<version>[0-9_]+).#');
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getDestinationUrl()
  {
    if (empty($this->destination))
    {
      $location = $this->getShellExec(sprintf('curl -fsIL "%s" | grep -i "location" | tail -1', $this->getDownloadUrl()));

      $this->destination = str_replace('location: ', '', $location);
    }

    return $this->destination;
  }
}
