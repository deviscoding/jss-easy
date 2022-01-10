<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;

/**
 * Installer configuration class for Citrix GotoMeeting
 *
 * @see     https://gotomeeting.com
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Installer
 */
class GoToMeeting extends BaseInstaller
{
  use ShellTrait;

  protected $destination;

  public function getDownloadUrl()
  {
    return 'https://link.gotomeeting.com/latest-dmg';
  }

  public function getName()
  {
    return 'GoToMeeting';
  }

  public function getPath()
  {
    return '/Applications/GoToMeeting.app';
  }

  /**
   * As there does not seem to be a method to retrieve the current version number from GotoMeeting's website,
   * we are returning FALSE to indicate that this information is omitted.
   *
   * @return false
   */
  public function getCurrentVersion()
  {
    return false;
  }

  public function getInstallerType()
  {
    return 'dmg';
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }
}
