<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Installer recipe class for Slack.
 *
 * @see     https://skype.com
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Skype extends AbstractRecipe
{
  public function getName()
  {
    return 'Skype';
  }

  public function getPath()
  {
    return '/Applications/Skype.app';
  }

  /**
   * Returns the proper download URL for the architecture.
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    return 'https://get.skype.com/go/getskype-skypeformac';
  }

  /**
   * Follows the redirected URL to find the destination URL so that we have the proper file extension.
   *
   * @return string|null
   */
  public function getDestinationUrl()
  {
    return (new DownloadHelper())->getRedirectUrl($this->getDownloadUrl(), $this->getUserAgent());
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  /**
   * Parses the update page for a darwin download URL, and retrieves the version from the URL.
   *
   * @return string|null
   */
  public function getCurrentVersion()
  {
    $version = $this->getVersionFromUrl($this->getDestinationUrl(), '#Skype-(?<version>.*).dmg#');
    $vobject = new SemanticVersion($version);

    return implode('.', [$vobject->getMajor(), $vobject->getMinor()]);
  }
}
