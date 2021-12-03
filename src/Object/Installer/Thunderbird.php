<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Thunderbird. Current version methodology and download URL adapted from
 * the Firefox installer script by Joe Farage, which can no longer be linked to on the Jamf website.
 *
 * @see     https://thunderbird.net
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Installer
 */
class Thunderbird extends BaseInstaller
{
  public function getName()
  {
    return 'Thunderbird';
  }

  public function getPath()
  {
    return '/Applications/Thunderbird.app';
  }

  public function getDownloadUrl()
  {
    $ver = $this->getCurrentVersion();

    return sprintf(
        'https://download-installer.cdn.mozilla.net/pub/thunderbird/releases/%s/mac/%s/%s',
        $ver,
        $this->getLanguage(),
        sprintf('Thunderbird%s%s.dmg', '%20', $ver)
    );
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

  public function getCurrentVersion()
  {
    $lng = $this->getLanguage();
    $url = sprintf('https://www.thunderbird.net/%s/thunderbird/all/', $lng);
    $uag = $this->getUserAgent();
    $res = (new DownloadHelper())->getUrl($url, null, null, $uag);

    if (!empty($res['body']))
    {
      $lines = explode("\n", $res['body']);
      foreach ($lines as $line)
      {
        if (preg_match('#data-thunderbird-version="(?<version>[^"]+)"#', $line, $m))
        {
          return $m['version'];
        }
      }
    }

    return null;
  }

  protected function getLanguage()
  {
    return 'en-US';
  }
}
