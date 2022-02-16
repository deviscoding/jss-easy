<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Installer recipe class for Mozilla Firefox Developer Edition
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class FirefoxDeveloper extends AbstractRecipe
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
    $ver = $this->getVersionFromUrl($this->getDestinationUrl(), '#Firefox%20(?<version>.*).dmg#');

    return (new SemanticVersion($ver))->__toString();
  }

  protected function getLanguage()
  {
    return 'en-US';
  }
}
