<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Mozilla Firefox.  Current version methodology and download URL adapted from
 * a script by Joe Farage, the link to which no longer functions on the Jamf website.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Firefox extends BaseInstaller
{
  public function getName()
  {
    return 'Mozilla Firefox';
  }

  public function getPath()
  {
    return '/Applications/Firefox.app';
  }

  public function getDownloadUrl()
  {
    $ver = $this->getCurrentVersion();

    return sprintf(
        'https://download-installer.cdn.mozilla.net/pub/firefox/releases/%s/mac/%s/%s',
        $ver,
        $this->getLanguage(),
        sprintf('Firefox%s%s.dmg', '%20', $ver)
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
    $url = sprintf('https://www.mozilla.org/%s/firefox/new/', $lng);
    $ver = str_replace('.', '_', (string) $this->getDevice()->getOs()->getVersion());
    $uag = sprintf('Mozilla/5.0 (Macintosh; Intel Mac OS X %s) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2', $ver);
    $res = (new DownloadHelper())->getUrl($url, null, null, $uag);

    if (!empty($res['body']))
    {
      $lines = explode("\n", $res['body']);
      foreach ($lines as $line)
      {
        if (preg_match('#data-latest-firefox="(?<version>[^"]+)"#', $line, $m))
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
