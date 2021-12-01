<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Mozilla Firefox Developer Edition
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class FirefoxDeveloper extends BaseInstaller
{
  protected $download;

  public function getName()
  {
    return 'Mozilla Firefox';
  }

  public function getPath()
  {
    return '/Applications/Firefox Developer Edition.app';
  }

  public function getDownloadUrl()
  {
    if (empty($this->download))
    {
      $base = 'https://download.mozilla.org/?product=firefox-devedition-latest-ssl&os=osx&lang='.$this->getLanguage();

      $this->download = (new DownloadHelper())->getRedirectUrl($base, $this->getUserAgent());
    }

    return $this->download;
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
    $uag = $this->getUserAgent();
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

  protected function getUserAgent()
  {
    $ver = str_replace('.', '_', (string) $this->getDevice()->getOs()->getVersion());

    return sprintf('Mozilla/5.0 (Macintosh; Intel Mac OS X %s) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2', $ver);
  }

  protected function getLanguage()
  {
    return 'en-US';
  }
}
