<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Rectangle
 *
 * @see     https://github.com/rxhanson/Rectangle
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Recipe
 */
class Rectangle extends AbstractGitHubRecipe
{
  protected function getRepo()
  {
    return 'rxhanson/Rectangle';
  }

  protected function getFile()
  {
    $v = $this->getCurrentVersion();

    if (0 === $v->getRevision())
    {
      $v = $v->getMajor().'.'.$v->getMinor();
    }

    return sprintf('Rectangle%s.dmg', $v);
  }

  public function getName()
  {
    return 'Rectangle';
  }

  public function getPath()
  {
    return '/Applications/Rectangle.app';
  }
}
