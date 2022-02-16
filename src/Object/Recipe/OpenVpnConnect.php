<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Command\Base\Traits\ShellTrait;

/**
 * Installer recipe class for OpenVPN Connect
 *
 * @see     https://openvpn.net/vpn-client/
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Object\Recipe
 */
class OpenVpnConnect extends AbstractRecipe
{
  use ShellTrait;

  protected $destination;

  public function getDownloadUrl()
  {
    return 'https://openvpn.net/downloads/openvpn-connect-v3-macos.dmg';
  }

  public function getName()
  {
    return 'OpenVPN Connect';
  }

  public function getPath()
  {
    return '/Applications/OpenVPN Connect/OpenVPN Connect.app';
  }

  /**
   * As there does not seem to be a method to retrieve the current version number from OpenVPN's website,
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
    if (empty($this->destination))
    {
      $location = $this->getShellExec(sprintf('curl -fsIL "%s" | grep -i "location" | tail -1', $this->getDownloadUrl()));

      $this->destination = str_replace('location: ', '', $location);
    }

    return $this->destination;
  }
}
