<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\MacApplication;

/**
 * Installer configuration class for Google Chrome
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class GoogleChrome extends BaseInstaller
{
  protected $current;

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

  /**
   * Override to provide the raw version number, direct from CFShortVersionString
   *
   * @return string|null
   */
  public function getInstalledVersion()
  {
    return (new MacApplication($this->getPath()))->getShortVersion()->getRaw();
  }

  public function getCurrentVersion()
  {
    if (!isset($this->current))
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
                $this->current = str_replace('-', '.', $version['current_version']);
              }
            }
          }
        }
      }
    }

    return $this->current;
  }
}
