<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Google Chrome
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class GoogleChrome extends BaseInstaller
{
  public function getName()
  {
    return 'Google Chrome';
  }

  public function getPath()
  {
    return '/Applications/Google Chrome.app';
  }

  public function getDownloadUrl()
  {
    return 'https://dl.google.com/chrome/mac/universal/stable/gcem/GoogleChrome.pkg';
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
    $res = (new DownloadHelper())->getUrl('https://omahaproxy.appspot.com/json', null, null, $this->getUserAgent());

    if (!empty($res['body']))
    {
      $data = json_decode($res['body'], true);

      foreach ($data as $datum)
      {
        if ('mac' === $datum['os'])
        {
          foreach ($datum['versions'] as $version)
          {
            if ('stable' === $version['channel'])
            {
              return $version['current_version'];
            }
          }
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
}
