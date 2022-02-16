<?php

namespace DevCoding\Jss\Easy\Driver;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Object\Mac\MacUpdate;
use DevCoding\Mac\Objects\MacDevice;

class SoftwareUpdateParser
{
  use ShellTrait;

  const PATTERN_DETAILS = '#^(.*), ([0-9]+)K\s?(.*)$#';

  /** @var MacDevice */
  protected $_Device;

  /**
   * @param MacDevice $Device
   */
  public function __construct(MacDevice $Device)
  {
    $this->_Device = $Device;
  }

  // region //////////////////////////////////////////////// Public Methods

  public function parse($output)
  {
    $updates = [];
    $isCatUp = $this->isCatalinaUp();
    if (!empty($output))
    {
      $count = count($output);
      for ($x = 0; $x < $count; ++$x)
      {
        unset($Update);
        if (preg_match('#\*\s(.*)$#', trim($output[$x]), $m))
        {
          if (!empty($m[1]))
          {
            $xx = $x + 1;
            if (!empty($output[$xx]))
            {
              $Update = $isCatUp ? $this->fromCatalina($m[1], $output[$xx]) : $this->fromMojave($m[1], $output[$xx]);
            }

            if (isset($Update))
            {
              $updates[] = $Update;
            }
          }
        }
      }
    }

    return $updates;
  }

  // endregion ///////////////////////////////////////////// Public Methods

  // region //////////////////////////////////////////////// Helper Methods

  /**
   * @param string $label
   * @param string $details
   *
   * @return MacUpdate|null
   */
  protected function fromCatalina($label, $details)
  {
    if (preg_match('#^Label:\s(.*)$#', trim($label), $lParts))
    {
      $Update = new MacUpdate($lParts[1]);

      if (preg_match_all('#([A-Z][a-z]+):\s([^,]+),#', $details, $parts, PREG_SET_ORDER))
      {
        foreach ($parts as $part)
        {
          $key = $part[1];
          $val = $part[2];

          if ('Title' == $key)
          {
            $Update->setName($val);
          }
          if ('Size' == $key)
          {
            $Update->setSize($val);
          }
          elseif ('Recommended' == $key && 'YES' == $val)
          {
            $Update->setRecommended(true);
          }
          elseif ('Action' == $key && 'restart' == $val)
          {
            $Update->setRestart(true);

            if ($this->isBridgeOs($Update))
            {
              $Update->setBridgeOs(true);
            }
          }
          elseif ('Action' == $key && 'shut down' == $val)
          {
            $Update->setHalt(true);
          }
        }
      }

      return $Update;
    }

    return null;
  }

  /**
   * @param string $label
   * @param string $details
   *
   * @return \DevCoding\Jss\Easy\Object\Mac\MacUpdate|null
   */
  protected function fromMojave($label, $details)
  {
    $Update = new MacUpdate(trim($label));
    if (preg_match(self::PATTERN_DETAILS, $details, $parts))
    {
      if (!empty($parts[1]))
      {
        $Update->setName(trim($parts[1]));
      }

      if (!empty($parts[2]))
      {
        $Update->setSize($parts[2]);
      }

      if (!empty($parts[3]))
      {
        if (false !== strpos($parts[3], 'recommended'))
        {
          $Update->setRecommended(true);
        }

        if (false !== strpos($parts[3], 'restart'))
        {
          $Update->setRestart(true);

          if ($this->isBridgeOs($Update))
          {
            $Update->setBridgeos(true);
          }
        }
        elseif (false !== strpos($parts[3], 'halt') || false !== strpos($parts[3], 'shut down'))
        {
          $Update->setHalt(true);
        }
      }
    }

    return $Update;
  }

  /**
   * @return MacDevice
   */
  protected function getDevice()
  {
    return $this->_Device;
  }

  /**
   * @param \DevCoding\Jss\Easy\Object\Mac\MacUpdate $Update
   *
   * @return bool
   */
  protected function isBridgeOs($Update)
  {
    $Device = $this->getDevice();
    if ($Device->isSecurityChip())
    {
      $search = str_replace('-'.$Device->getOs()->getVersion(), '', $Update->getId());
      $result = $this->getShellExec(sprintf('cat /var/log/install.log | grep "%s" | tail -1', $search));

      if (preg_match('#^\s*([^|(]*)\(?[A-Z]?\)?\s*|#', $result, $matches))
      {
        $log   = '/var/log/install.log';
        $index = trim($matches[1]);
        $date  = substr(date('Y-m-d H:i'), -1);
        $cmd   = sprintf('cat %s | grep "%s" | grep "requires bridgeOS update" | grep "%s"', $log, $index, $date);
        $grep  = $this->getShellExec($cmd);

        if (!empty($grep))
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Determines if the OS version is Catalina or greater.
   *
   * @return bool
   */
  protected function isCatalinaUp()
  {
    if ($v = $this->getDevice()->getOs()->getVersion())
    {
      if (11 == $v->getMajor() || 10 == $v->getMajor() && $v->getMinor() >= 15)
      {
        return true;
      }
    }

    return false;
  }

  // endregion ///////////////////////////////////////////// Helper Methods
}
