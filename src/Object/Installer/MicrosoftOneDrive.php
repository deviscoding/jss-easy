<?php

namespace DevCoding\Jss\Easy\Object\Installer;

/**
 * Installer configuration class for Microsoft OneDrive. Credit to MacAdmins.software for the link id, current version
 * information, and package name.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class MicrosoftOneDrive extends MicrosoftInstaller
{
  public function getName()
  {
    return 'Microsoft OneDrive';
  }

  public function getPath()
  {
    return '/Applications/OneDrive.app';
  }

  protected function getLinkId()
  {
    return 823060;
  }

  protected function getPackageName()
  {
    return 'com.microsoft.onedrive.standalone';
  }
}
