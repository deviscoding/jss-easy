<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Installer configuration class for Adobe Acrobat Reader DC.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class AdobeReaderDc extends BaseInstaller
{
  use ShellTrait;

  /** @var string The version string listed in the Adobe Reader DC manifest URL */
  protected $manifest;

  public function getName()
  {
    return 'Adobe Reader DC';
  }

  public function getPath()
  {
    return '/Applications/Adobe Acrobat Reader DC.app';
  }

  /**
   * URL taken from script posted in JamfNation, the link to which is no longer valid.
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    $v = str_replace('.', '', $this->getManifestVersion());

    /* @noinspection HttpUrlsUsage */
    return sprintf('http://ardownload2.adobe.com/pub/adobe/reader/mac/AcrobatDC/%s/AcroRdrDC_%s_MUI.dmg', $v, $v);
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

  /**
   * Returns the current version, based on the manifest version, normalized.
   *
   * @return string|null
   */
  public function getCurrentVersion()
  {
    if ($v = $this->getManifestVersion())
    {
      return (new SemanticVersion($v))->__toString();
    }

    return null;
  }

  /**
   * Returns the non-normalized version listed as current by Adobe.
   *
   * @see https://community.jamf.com/t5/jamf-pro/acrobat-dc-read-script-curl-broken/m-p/238622/highlight/true
   * @return string
   */
  protected function getManifestVersion()
  {
    if (!isset($this->manifest))
    {
      $url = 'https://armmf.adobe.com/arm-manifests/mac/AcrobatDC/reader/current_version.txt';
      if ($res = (new DownloadHelper())->getUrl($url, null, null, $this->getUserAgent()))
      {
        return $res['body'];
      }
    }

    return null;
  }
}
