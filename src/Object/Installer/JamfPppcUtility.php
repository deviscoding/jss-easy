<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Jamf PPPC Utility.
 *
 * @see     https://github.com/jamf/PPPC-Utility
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class JamfPppcUtility extends AbstractGitHubRecipe
{
  protected function getRepo()
  {
    return 'jamf/PPPC-Utility';
  }

  protected function getFile()
  {
    return 'PPPC.Utility.zip';
  }

  public function getName()
  {
    return 'PPPC Utility';
  }

  public function getPath()
  {
    return '/Applications/PPPC Utility.app';
  }
}
