<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Adobe Acrobat Reader DC.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class AdobeReaderDc extends BaseInstaller
{
  use ShellTrait;

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
    $v = str_replace('.', '', $this->getCurrentVersion());

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
   * @see https://community.jamf.com/t5/jamf-pro/acrobat-dc-read-script-curl-broken/m-p/238622/highlight/true
   *
   * @return string|null
   */
  public function getCurrentVersion()
  {
    $url = 'https://armmf.adobe.com/arm-manifests/mac/AcrobatDC/reader/current_version.txt';
    if ($res = (new DownloadHelper())->getUrl($url, null, null, $this->getUserAgent()))
    {
      return $res['body'];
    }

    return null;
  }

  protected function getUserAgent()
  {
    $ver = str_replace('.', '_', (string) $this->getDevice()->getOs()->getVersion());

    return sprintf('Mozilla/5.0 (Macintosh; Intel Mac OS X %s) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2', $ver);
  }
}
