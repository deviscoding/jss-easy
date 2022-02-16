<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer recipe class for Slack.
 *
 * @see     https://slack.com
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Slack extends AbstractRecipe
{
  public function getName()
  {
    return 'Visual Studio Code';
  }

  public function getPath()
  {
    return '/Applications/Slack.app';
  }

  /**
   * Returns the proper download URL for the architecture.
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    if ($this->isAppleSilicon())
    {
      return 'https://slack.com/ssb/download-osx-silicon';
    }
    else
    {
      return 'https://slack.com/ssb/download-osx';
    }
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
    return $this->getVersionFromUrl($this->getDestinationUrl());
  }
}
