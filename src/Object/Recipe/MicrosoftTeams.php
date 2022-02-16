<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Microsoft Teams. Credit to MacAdmins.software for the link id, current version
 * information, and package name.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class MicrosoftTeams extends AbstractMicrosoftRecipe
{
  public function getName()
  {
    return 'Microsoft Teams';
  }

  public function getPath()
  {
    return '/Applications/Microsoft Teams.app';
  }

  protected function getLinkId()
  {
    return 869428;
  }

  protected function getPackageName()
  {
    return 'com.microsoft.teams.standalone';
  }
}
