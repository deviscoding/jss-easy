<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Platypus
 *
 * @see     https://github.com/sveinbjornt/Platypus
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Recipe
 */
class Platypus extends AbstractGitHubRecipe
{
  protected function getRepo()
  {
    return 'sveinbjornt/Platypus';
  }

  protected function getFile()
  {
    $v = $this->getCurrentVersion();

    if (0 === $v->getRevision())
    {
      $v = $v->getMajor().'.'.$v->getMinor();
    }

    return sprintf('Platypus%s.zip', $v);
  }

  public function getName()
  {
    return 'Platypus';
  }

  public function getPath()
  {
    return '/Applications/Platypus.app';
  }
}
