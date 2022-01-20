<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Microsoft Edge. Credit to MacAdmins.software for the link id, current version
 * information, and package name.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class MicrosoftEdge extends AbstractMicrosoftRecipe
{
  public function getName()
  {
    return 'Microsoft Edge';
  }

  public function getPath()
  {
    return '/Applications/Microsoft Edge.app';
  }

  protected function getLinkId()
  {
    return 2093504;
  }

  protected function getPackageName()
  {
    return 'com.microsoft.edge';
  }

  public function getCurrentVersion()
  {
    return $this->getVersionFromUrl($this->getDestinationUrl(), '#[a-zA-Z-]*(?<version>[0-9.]*)\.pkg#');
  }
}
