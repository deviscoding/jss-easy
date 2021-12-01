<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\MacApplication;
/**
 * Abstract Installer configuration class for Microsoft applications.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
abstract class MicrosoftInstaller  extends BaseInstaller
{
  use ShellTrait;

  protected $destination;

  /**
   * @return int
   */
  abstract protected function getLinkId();

  abstract protected function getPackageName();

  public function getDownloadUrl()
  {
    return sprintf('https://go.microsoft.com/fwlink/?linkid=%s', $this->getLinkId());
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
    $v = $this->getShellExec(sprintf("curl -fs https://macadmins.software/latest.xml | xpath -q -e '//latest/package[id=\"%s\"]/version'", $this->getPackageName()));

    if (preg_match('#([0-9]+.[0-9]+.[0-9]+)#', $v, $matches))
    {
      return $matches[1];
    }

    return null;
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getDestinationUrl()
  {
    if (empty($this->destination))
    {
      $this->destination = (new DownloadHelper())->getRedirectUrl($this->getDownloadUrl());
    }

    return $this->destination;
  }
}
