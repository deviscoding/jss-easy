<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Mac\Command\AbstractMacConsole;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class AbstractWaitConsole extends AbstractMacConsole
{
  const TESTS = [
      'cpu'       => 'CPU',
      'filevault' => 'FileVault Encryption',
      'power'     => 'AC Power',
      'screen'    => 'Screen Availability',
      'user'      => 'User Logout',
  ];

  protected function configure()
  {
    $this->addArgument('seconds');
    $this->addOption('json', null, InputOption::VALUE_NONE, 'Output results in JSON');

    foreach (static::TESTS as $key => $value)
    {
      $this->addOption($key, null, InputOption::VALUE_NONE, 'Wait for '.$value);
    }

    parent::configure();
  }

  /**
   * @return false
   */
  protected function isAllowUserOption()
  {
    return false;
  }

  /**
   * @param bool[] $wait An array of testKey => isEnabled. Values are modified by reference.
   *
   * @return bool TRUE if the system should wait on one or more of the given tests, else FALSE
   */
  protected function isWaiting(&$wait)
  {
    $isWaiting = false;
    foreach ($wait as $key => $value)
    {
      if (false !== $value)
      {
        $wait[$key] = $this->is($key);

        if ($wait[$key])
        {
          $isWaiting = true;
        }
      }
    }

    return $isWaiting;
  }

  /**
   * Evaluates whether the system is waiting on the given condition.
   *
   * @param string $key
   *
   * @return bool|null
   */
  protected function is($key)
  {
    if ('cpu' == $key)
    {
      return $this->isLoadHigh();
    }
    elseif ('filevault' == $key)
    {
      return $this->isEncryptingFileVault();
    }
    elseif ('power' == $key)
    {
      return $this->isBatteryPowered();
    }
    elseif ('screen' == $key)
    {
      return $this->isDisplaySleepPrevented();
    }
    elseif ('user' == $key)
    {
      return !empty($this->getConsoleUser());
    }

    return null;
  }

  /**
   * Returns an array of requested tests (testKey => isEnabled), taken from the array of given key possibilities.
   *
   * @param string[] $keys The possible test keys to enable
   *
   * @return bool[] An array of the enabled tests (testKey => isEnabled)
   */
  protected function getTests($keys = ['cpu', 'user', 'power', 'screen', 'filevault'])
  {
    // Determine which tests to use
    $tests = array_fill_keys($keys, null);
    $isAll = true;

    foreach ($keys as $key)
    {
      if ($this->io()->getOption($key))
      {
        // Since one specific test was requested, do not use all tests
        $isAll = false;
        // Set the key for this test to TRUE
        $tests[$key] = true;
      }
    }

    // If all tests should be use, return an array with all keys set to true.  Otherwise, return the tests array.
    return $isAll ? array_fill_keys($keys, true) : $tests;
  }
}
