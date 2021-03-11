<?php

namespace DevCoding\Jss\Helper\Command\Info;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HardwareCommand extends AbstractInfoConsole
{
  const CPU       = 'cpu';
  const CPU_APPLE = 'apple';
  const CPU_CORES = 'cores';
  const CPU_INTEL = 'intel';
  const CPU_NAME  = 'name';
  const CPU_SPEED = 'speed';
  const CPU_TYPE  = 'type';

  const MODEL            = 'model';
  const MODEL_IDENTIFIER = 'id';
  const MODEL_NAME       = 'name';
  const MODEL_SERIAL     = 'serial';

  const NETWORK            = 'network';
  const NETWORK_INTERFACES = 'interface';
  const NETWORK_IPV4       = 'ipv4';
  const NETWORK_WIRED      = 'wired';
  const NETWORK_WIFI       = 'wifi';
  const NETWORK_BLUETOOTH  = 'bluetooth';
  const NETWORK_VPN        = 'vpn';

  protected function configure()
  {
    $this
        ->setName('info:hardware')
        ->setAliases(['hardware'])
        ->addArgument('key', InputArgument::OPTIONAL)->addOption('json', 'j', InputOption::VALUE_NONE);
  }

  protected function isAllowUserOption(): bool
  {
    return false;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $theKey = $this->io()->getArgument('key');

    if ($this->isJson())
    {
      $this->io()->getOutput()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    if ($theKey)
    {
      $subKey = $this->getSubkey($theKey);
      if (false !== strpos($theKey, self::NETWORK))
      {
        $data = $this->getNetwork($subKey);
      }
      elseif (false === strpos($theKey, self::MODEL))
      {
        $data = $this->getModel($subKey);
      }
      elseif (false === strpos($theKey, self::CPU))
      {
        $data = $this->getCpu($subKey);
      }
      else
      {
        $this->io()->errorln('Unrecognized key.');

        return self::EXIT_ERROR;
      }
    }
    else
    {
      $data = $this->getSummary();
    }

    if ($this->isJson())
    {
      $this->io()->writeln(json_encode($data, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT), null, false, OutputInterface::VERBOSITY_QUIET);
    }
    elseif (is_array($data))
    {
      $this->renderOutput($data);
    }
    else
    {
      $this->io()->writeln($data);
    }

    return self::EXIT_SUCCESS;
  }

  protected function getSummary()
  {
    return [
        self::MODEL   => $this->getModel(),
        self::CPU     => $this->getCpu(),
        self::NETWORK => $this->getNetwork(),
    ];
  }

  // region //////////////////////////////////////////////// Information Methods

  protected function getModel($key = null)
  {
    $subKeys = [self::MODEL_NAME, self::MODEL_IDENTIFIER, self::MODEL_SERIAL];

    if (self::MODEL_NAME === $key)
    {
      return $this->getDevice()->getModelName();
    }
    elseif (self::MODEL_IDENTIFIER === $key)
    {
      return $this->getDevice()->getModelIdentifier();
    }
    elseif (self::MODEL_SERIAL === $key)
    {
      return $this->getDevice()->getSerialNumber();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getModel($subKey);
      }

      return $retval;
    }

    return null;
  }

  protected function getCpu($key = null)
  {
    $subKeys = [self::CPU_NAME, self::CPU_SPEED, self::CPU_CORES, self::CPU_APPLE, self::CPU_INTEL];

    if (self::CPU_TYPE === $key)
    {
      return $this->getDevice()->getCpuType();
    }
    elseif (self::CPU_NAME === $key)
    {
      return $this->getDevice()->getProcessorName();
    }
    elseif (self::CPU_SPEED === $key)
    {
      return $this->getDevice()->getProcessorSpeed();
    }
    elseif (self::CPU_CORES === $key)
    {
      return $this->getDevice()->getCpuCores();
    }
    elseif (self::CPU_APPLE === $key)
    {
      return $this->getDevice()->isAppleChip();
    }
    elseif (self::CPU_INTEL === $key)
    {
      return $this->getDevice()->isIntelChip();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getCpu($subKey);
      }

      return $retval;
    }

    return null;
  }

  /**
   * @param null $key
   *
   * @return string[]|bool|null
   */
  protected function getNetwork($key = null)
  {
    $subKeys = [self::NETWORK_INTERFACES, self::NETWORK_IPV4, self::NETWORK_WIRED, self::NETWORK_WIFI, self::NETWORK_VPN];

    if (false !== strpos($key, self::NETWORK_INTERFACES))
    {
      if (self::NETWORK_INTERFACES === $key)
      {
        return $this->getInterfaces();
      }
      else
      {
        return $this->getInterfaces($this->getSubkey($key));
      }
    }
    elseif (false !== strpos($key, self::NETWORK_IPV4))
    {
      if (self::NETWORK_IPV4 === $key)
      {
        return $this->getIpV4();
      }
      else
      {
        return $this->getIpV4($this->getSubkey($key));
      }
    }
    elseif (self::NETWORK_WIRED === $key)
    {
      return $this->getDevice()->getNetwork()->isActiveEthernet();
    }
    elseif (self::NETWORK_WIFI === $key)
    {
      return $this->getDevice()->getNetwork()->isActiveWifi();
    }
    elseif (self::NETWORK_VPN === $key)
    {
      return $this->getDevice()->getNetwork()->isActiveVpn();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getNetwork($subKey);
      }

      return $retval;
    }

    return null;
  }

  /**
   * @param null $key
   *
   * @return string[]|string|null
   */
  protected function getIpV4($key = null)
  {
    $subKeys = [self::NETWORK_VPN];

    if (self::NETWORK_VPN === $key)
    {
      return $this->getDevice()->getNetwork()->getVpnIp();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getIpV4($subKey);
      }

      return $retval;
    }

    return null;
  }

  /**
   * @param null $key
   *
   * @return string[]|string|null
   */
  protected function getInterfaces($key = null)
  {
    $subKeys = [self::NETWORK_WIRED, self::NETWORK_WIFI];

    if (self::NETWORK_WIRED === $key)
    {
      return $this->getDevice()->getNetwork()->getWiredInterfaces();
    }
    if (self::NETWORK_WIFI === $key)
    {
      return $this->getDevice()->getNetwork()->getWiFiInterfaces();
    }
    if (self::NETWORK_VPN === $key)
    {
      return $this->getDevice()->getNetwork()->getVpnInterfaces();
    }
    if (self::NETWORK_BLUETOOTH === $key)
    {
      return $this->getDevice()->getNetwork()->getBluetoothInterfaces();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getInterfaces($subKey);
      }

      return $retval;
    }

    return null;
  }
}
