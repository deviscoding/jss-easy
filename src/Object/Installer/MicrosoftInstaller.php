<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Abstract Installer configuration class for Microsoft applications.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
abstract class MicrosoftInstaller extends BaseInstaller
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

  /**
   * @throws \Exception
   */
  public function getCurrentVersion()
  {
    $result = (new DownloadHelper())->getUrl('https://macadmins.software/latest.xml');
    $body   = $result['body'] ?? null;

    if ($body)
    {
      $xpath = sprintf('/latest/package[id="%s"]/version', $this->getPackageName());

      try
      {
        if ($xml = (new \SimpleXMLElement($body))->xpath($xpath))
        {
          $data = (string) $xml[0];

          if (preg_match('#([0-9]+.[0-9]+.[0-9]+)#', $data, $m))
          {
            return (new SemanticVersion($m[1]))->__toString();
          }
        }
      }
      catch (\Exception $e)
      {
        return null;
      }
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
