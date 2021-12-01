<?php

namespace DevCoding\Jss\Easy\Object\Installer;

/**
 * Installer configuration class for Hyper.
 *
 * @see     https://github.com/vercel/hyper
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Hyper extends GitHubInstaller
{
  protected function getRepo()
  {
    return 'vercel/hyper';
  }

  protected function getFile()
  {
    $base = 'Hyper-%s-mac-%s.dmg';
    if ($arch = $this->getDevice()->getCpuType())
    {
      if ('apple' === $arch)
      {
        return sprintf($base, $this->getCurrentVersion(), 'arm64');
      }
    }

    return sprintf($base, $this->getCurrentVersion(), 'x64');
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
