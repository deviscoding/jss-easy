<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

/**
 * Installer recipe class for Hyper.
 *
 * @see     https://github.com/vercel/hyper
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Hyper extends AbstractGitHubRecipe
{
  protected function getRepo()
  {
    return 'vercel/hyper';
  }

  protected function getFile()
  {
    $arch = $this->isAppleSilicon() ? 'arm64' : 'x64';
    $base = 'Hyper-%s-mac-%s.dmg';

    return sprintf($base, $this->getCurrentVersion(), $arch);
  }

  public function getName()
  {
    return 'Hyper';
  }

  public function getPath()
  {
    return '/Applications/Hyper.app';
  }
}
