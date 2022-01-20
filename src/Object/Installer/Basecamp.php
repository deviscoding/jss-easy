<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Command\Base\Traits\ShellTrait;

/**
 * Installer recipe class for Basecamp3
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Recipe
 */
class Basecamp extends AbstractRecipe
{
  use ShellTrait;

  /**
   * @see    https://basecamp.com/via
   * @return string
   */
  public function getDownloadUrl()
  {
    return 'https://bc3-desktop.s3.amazonaws.com/mac/basecamp3.dmg';
  }

  public function getName()
  {
    return 'Basecamp 3';
  }

  public function getPath()
  {
    return '/Applications/Basecamp 3.app';
  }

  /**
   * As there does not seem to be a method to retrieve the current version number from Basecamp's website,
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
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }
}
