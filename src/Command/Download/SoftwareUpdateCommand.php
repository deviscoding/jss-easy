<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Driver\SoftwareUpdateDriver;
use DevCoding\Jss\Easy\Driver\SoftwareUpdateParser;
use DevCoding\Jss\Easy\Helper\JsonHelper;
use DevCoding\Jss\Easy\Object\Mac\MacUpdate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SoftwareUpdateCommand extends AbstractWaitConsole
{
  const OPTION_TIMEOUT = 'timeout';
  const CONTINUE       = -1;

  /** @var bool */
  protected $jamf;
  /** @var string */
  protected $_SoftwareUpdateBinary;
  /** @var MacUpdate[] */
  protected $_Updates;
  /** @var JsonHelper */
  protected $_JsonHelper;

  protected function configure()
  {
    $this->setName('softwareupdate');
    $this->addOption('no-scan', null, InputOption::VALUE_NONE, 'Do not scan when listing or installing updates (use available updates previously scanned)');
    $this->addOption('download', null, InputOption::VALUE_NONE, 'Only Download Updates');
    $this->addOption('install', null, InputOption::VALUE_NONE, 'Install Updates');
    $this->addOption('list', null, InputOption::VALUE_NONE, 'List Updates');
    $this->addOption('count', null, InputOption::VALUE_NONE, 'Count Updates');
    $this->addOption('summary', null, InputOption::VALUE_NONE, 'Show Summary');
    $this->addOption('json', null, InputOption::VALUE_NONE, 'Show Output in JSON');
    $this->addOption(self::OPTION_TIMEOUT, null, InputOption::VALUE_REQUIRED, 'Software Update timeout in seconds.', $this->getDefaultTimeout());

    foreach (static::TESTS as $key => $value)
    {
      $this->addOption('--skip-'.$key, null, InputOption::VALUE_NONE, 'Do Not Wait for '.$value);
    }

    $this->addOption('wait', null, InputOption::VALUE_REQUIRED, 'Seconds to Wait (for conditions)', '60');

    if ($this->isJamf())
    {
      $this->addOption('install-policy', null, InputOption::VALUE_REQUIRED, 'Install Policy Trigger or ID');
    }
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    if ($this->getDevice()->isAppleChip())
    {
      $input->setOption('skip-user', true);
    }

    parent::interact($input, $output);
  }

  /**
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set Verbosity for JSON Output
    if ($this->isJson())
    {
      $this->io()->getOutput()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    $this->json()->append(['apple_silicon' => $this->getDevice()->isAppleChip()]);

    // Check for Updates unless --noscan
    if (!$this->isNoScan())
    {
      $this->io()->msg('Finding available software', 60);
      try
      {
        $Updates = $this->getUpdateList();
        $count   = count($Updates);
      }
      catch (\Exception $e)
      {
        $this->io()->errorln('[ERROR]');

        $this->json()->append(['scan' => true, 'count' => false, 'error' => true]);

        return self::EXIT_ERROR;
      }

      // Add data to JSON
      $this->json()->append(['scan' => true, 'count' => $count]);

      $this->io()->successln('[SUCCESS]');
    }
    else
    {
      try
      {
        $Updates = $this->getUpdateList();
        $count   = count($Updates);
      }
      catch (\Exception $e)
      {
        $this->io()->errorln('An error was encountered verifying the updates available for installation.');

        // Add data to JSON
        $this->json()->append(['scan' => false, 'count' => false, 'error' => true]);

        return self::EXIT_ERROR;
      }

      // Add data to JSON
      $this->json()->append(['scan' => null, 'count' => $count]);
    }

    // Route to the appropriate action
    if ($this->isSummary())
    {
      return $this->executeSummary($input, $output);
    }
    if ($this->isList())
    {
      return $this->executeList($input, $output);
    }
    if ($this->isCount())
    {
      return $this->executeCount($input, $output);
    }
    else
    {
      // These only run if there are updates...
      if (0 != $count)
      {
        if ($this->isDownload())
        {
          // Run the download and return
          return $this->executeDownload($input, $output);
        }

        // Installations are only run after the wait conditions clear...
        if ($this->isInstall())
        {
          // Wait for the wait conditions
          $wait = $this->executeWait($input, $output);
          if (self::CONTINUE !== $wait)
          {
            return $wait;
          }

          // Do the installations
          $install = $this->executeInstall($input, $output);
          if (self::CONTINUE !== $install)
          {
            if ($this->isJson())
            {
              $this->json()->output();
            }

            return $install;
          }

          // If continue was returned, then we need a restart or halt
          return $this->executeRestart($input, $output);
        }

        $this->io()->writeln('No action was requested.');

        return self::EXIT_ERROR;
      }
      else
      {
        $this->io()->msgln('No new software available.');

        $this->json()->output();
      }
    }

    return self::EXIT_SUCCESS;
  }

  protected function executeWait(InputInterface $input, OutputInterface $output)
  {
    $tests = $this->getTests();
    $wait  = $tests;

    $this->io()->msg('Checking Wait Conditions', 60);

    // Wait for X seconds for wait conditions...
    $seconds   = (int) $this->io()->getOption('wait') ?? 0;
    $isWaiting = false;
    while ($seconds > 0)
    {
      $this->io()->write($seconds.'..', null, null, OutputInterface::VERBOSITY_VERBOSE);
      sleep(1);

      if ($isWaiting = $this->isWaiting($wait))
      {
        --$seconds;
      }
      else
      {
        $seconds = 0;
      }
    }

    if ($isWaiting)
    {
      $this->io()->errorln('[FAIL]');

      if ($this->isJson())
      {
        $retval = [];
        foreach ($tests as $key => $value)
        {
          $retval[$key] = $value ? ($wait[$key] ?? false) : null;
        }

        $this->json()->append(['wait' => $retval]);
      }
      else
      {
        foreach ($wait as $key => $value)
        {
          if ($value)
          {
            $name = self::TESTS[$key] ?? strtoupper($key);
            $this->io()->commentln(sprintf('  Waiting for %s', $name));
          }
        }
      }

      return self::EXIT_ERROR;
    }
    else
    {
      $this->io()->successln('[SUCCESS]');
    }

    return self::CONTINUE;
  }

  /**
   * Executes the command, returning a list of pending updates including various details.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int returns 0 or 1 for success or failure
   *
   * @throws \Exception If the macOS softwareupdate binary is not present
   */
  protected function executeList(InputInterface $input, OutputInterface $output)
  {
    $this->io()->blankln();

    $Updates = $this->getUpdateList();

    if ($this->isJson())
    {
      echo json_encode($Updates ?? []);
    }
    else
    {
      if (!empty($Updates))
      {
        foreach ($Updates as $macUpdate)
        {
          $line = sprintf('  %s (%s) [%s]', $macUpdate->getName(), $macUpdate->getId(), $macUpdate->getSize());

          if ($macUpdate->isRecommended())
          {
            $line .= '[Recommended]';
          }

          if ($macUpdate->isBridgeOs())
          {
            $line .= '[BridgeOS]';
          }

          if ($macUpdate->isRestart())
          {
            $line .= '[Restart]';
          }

          if ($macUpdate->isHalt())
          {
            $line .= '[Shutdown]';
          }

          $output->writeln($line);
        }
      }
      else
      {
        $output->writeln('No new software available.');
      }
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * Executes the command, returning the count of the number of pending updates.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int returns 0 or 1 for success or failure
   *
   * @throws \Exception If the macOS softwareupdate binary is not present
   */
  protected function executeCount(InputInterface $input, OutputInterface $output)
  {
    $count = count($this->getUpdateList());
    if ($this->isJson())
    {
      echo json_encode($count);
    }
    else
    {
      $this->io()->blankln()->writeln(sprintf('There are %s updates pending.', $count));
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * @throws \Exception
   */
  protected function executeDownload(InputInterface $input, OutputInterface $output)
  {
    $this->json()->append(['action' => 'download']);
    if ($this->getDevice()->isAppleChip())
    {
      $error = 'Apple Silicon devices do not support automated downloads via softwareupdate.';
      $this->io()->msg('Starting Download', 60);
      $this->io()->errorln('ERROR');
      $this->io()->write('  '.$error);

      $retval = self::EXIT_ERROR;

      if ($this->isJson())
      {
        foreach ($this->getUpdateList() as $macUpdate)
        {
          $this->json()->append(['updates' => [$macUpdate->getName() => $error]]);
        }
      }
    }
    else
    {
      $Updates = $this->getUpdateList();
      $retval  = self::EXIT_SUCCESS;
      foreach ($Updates as $macUpdate)
      {
        $this->io()->msg('Downloading '.$macUpdate->getName(), 60);

        $SU = $this->getSoftwareUpdateDriver(['no-scan' => true, 'download' => $macUpdate->getId()]);
        $SU->run();

        if (!$SU->isSuccessful())
        {
          $this->io()->error('[ERROR]');

          // Get Error Output
          $errors = $SU->getErrorOutput(true);
          // Add to output array
          $this->json()->append(['download' => [$macUpdate->getName() => $errors]]);

          foreach ($errors as $line)
          {
            $this->io()->writeln('  '.$line);
          }

          $retval = self::EXIT_ERROR;
        }
        else
        {
          $this->io()->successln('[SUCCESS]');

          // Add to output array
          $this->json()->append(['download' => [$macUpdate->getName() => true]]);
        }
      }
    }

    if ($this->isJson())
    {
      $this->json()->output();
    }

    return $retval;
  }

  /**
   * @throws \Exception
   */
  protected function executeSummary(InputInterface $input, OutputInterface $output)
  {
    $summary = $this->getSummary($this->getUpdateList());
    if ($this->isJson())
    {
      $this->io()->writeln(json_encode($summary, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT), null, false, OutputInterface::VERBOSITY_QUIET);
    }
    else
    {
      $this->io()->info('Total Updates', 60)->msgln($summary['count']);
      $this->io()->info('Recommended Updates', 60)->msgln($summary['recommended']);
      $this->io()->info('Updates Requiring Restart', 60)->msgln($summary['restart']);
      $this->io()->info('Updates Requiring Shutdown', 60)->msgln($summary['shutdown']);
      $this->io()->blankln();
      $this->io()->info('Console Username', 60)->msgln($summary['console_user']);
      $this->io()->info('Content Cache', 60)->msgln(!empty($summary['content_cache']) ? implode(',', $summary['content_cache']) : 'None');
      $this->io()->info('SUS Url', 60)->msgln($summary['sus_url'] ?: 'None');
      $this->io()->info('Free Disk Space', 60)->msgln($summary['disk_space'].'GiB');
      $this->io()->blankln();

      // Battery Minutes
      $this->io()->info('Battery Remaining', 60);

      if ($summary['battery_minutes'] && $summary['battery_minutes'] > 60)
      {
        $this->io()->successln($summary['battery_minutes'].'mins');
      }
      elseif ($summary['battery_minutes'])
      {
        $suffix = ($summary['battery_minutes'] > 1) ? ' mins' : ' min';
        $this->io()->errorln($summary['battery_minutes'].$suffix);
      }
      else
      {
        $this->io()->msgln('N/A');
      }

      // Battery Percentage
      $this->io()->info('Battery Percentage', 60);
      if ($summary['battery_percent'] && $summary['battery_percent'] < 33)
      {
        $this->io()->errorln($summary['battery_percent'].'%');
      }
      elseif ($summary['battery_percent'])
      {
        $this->io()->successln($summary['battery_percent'].'%');
      }
      else
      {
        $this->io()->msgln('N/A');
      }

      // On Battery Power?
      $this->io()->info('On Battery Power?', 60);
      if ($summary['battery'])
      {
        $this->io()->errorln('Yes');
      }
      else
      {
        $this->io()->successln('No');
      }
      $this->io()->blankln();

      // Encryption in Progress
      $this->io()->info('Encryption in Progress?', 60);
      if ($summary['encrypting'])
      {
        $this->io()->errorln('Yes');
      }
      else
      {
        $this->io()->successln('No');
      }

      // Presentation in Progress
      $this->io()->info('Screen Sleep Prevented?', 60);
      if ($summary['prevent_sleep'])
      {
        $this->io()->errorln('Yes');
      }
      else
      {
        $this->io()->successln('No');
      }

      // SUS Available?
      $this->io()->info('SUS Offline?', 60);
      if ($summary['sus_offline'])
      {
        $this->io()->errorln('Yes');
      }
      else
      {
        $this->io()->successln('No');
      }
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * For systems with Apple Silicon, opens the Software Update preference pane for the current console user if possible.
   *
   * Otherwise, if the system is running JAMF and the 'install-policy' option was given, runs that policy to trigger
   * the installations.  If the system is not running JAMF, or no 'install-policy' was given, runs softwareupdate
   * directly.  Upon success, if a restart or shutdown is required, returns -1.  If no restart or shutdown is required,
   * returns 0.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int -1 if shutdown or restart is required, otherwise 0 if successful, 1 if errors occur
   *
   * @throws \Exception
   */
  protected function executeInstall(InputInterface $input, OutputInterface $output)
  {
    if ($this->getDevice()->isAppleChip())
    {
      $this->io()->msg('Opening Software Update Preference Pane', 60);
      // Apple M1 Systems require authentication from the user to install updates, therefore the best we can do is
      // trigger the preference pane to open.
      if ($user = $this->getConsoleUser())
      {
        $this->openAsConsoleUser('/System/Library/PreferencePanes/SoftwareUpdate.prefPane');

        $this->io()->successln('[SUCCESS]');
      }
      else
      {
        // This only means a user was not logged in.  Not really an error.
        $this->io()->errorln('[ERROR]');
      }

      // Set the JSON results
      $this->json()->append(['action' => ['preference_pane' => $user ?? false]]);
      foreach ($this->getUpdateList() as $macUpdate)
      {
        $this->json()->append(['install' => [$macUpdate->getName() => null]]);
      }

      // Return and do not trigger restarts
      return self::EXIT_SUCCESS;
    }
    elseif ($this->isJamf() && $policy = $this->getInstallPolicy())
    {
      $flag = is_numeric($policy) ? 'id' : 'trigger';
      $data = escapeshellarg($policy);
      $cmd  = $this->getAtCommand(sprintf('/usr/local/bin/jamf policy --%s %s', $flag, $data));

      if ($this->isJson())
      {
        exec($cmd, $out, $retval);
      }
      else
      {
        passthru($cmd, $retval);
      }

      // Set the JSON results
      $this->json()->append(['action' => ['jamf_policy' => $policy]]);
      foreach ($this->getUpdateList() as $macUpdate)
      {
        $this->json()->append(['install' => [$macUpdate->getName() => null]]);
      }

      return $retval;
    }
    else
    {
      return $this->executeSoftwareUpdate($input, $output);
    }
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   *
   * @throws \Exception
   */
  protected function executeRestart(InputInterface $input, OutputInterface $output)
  {
    if ($this->isHaltRequired())
    {
      $this->json()->append(['halt' => true]);
      $this->io()->msg('Triggering System Shutdown', 60);
      // Trigger Restart w/ Delay to Finish & Log
      $cmd = $this->getAtCommand('shutdown -h +2m');
    }
    else
    {
      $this->json()->append(['restart' => true]);
      $this->io()->msg('Triggering System Restart', 60);

      // Trigger Restart w/ Delay to Finish & Log
      $cmd = $this->getAtCommand('shutdown -r +2m');
    }

    exec($cmd, $output, $retval);
    if (0 === $retval)
    {
      $this->io()->successln('[SUCCESS]');

      $retval = self::EXIT_SUCCESS;
    }
    else
    {
      $this->io()->errorln('[ERROR]');

      if ($this->json()->has('shutdown'))
      {
        $this->json()->append(['shutdown' => false]);
      }
      elseif ($this->json()->has('halt'))
      {
        $this->json()->append(['halt' => false]);
      }

      $retval = self::EXIT_ERROR;
    }

    if ($this->isJson())
    {
      $this->json()->output();
    }

    return $retval;
  }

  /**
   * @throws \Exception
   */
  protected function executeSoftwareUpdate(InputInterface $input, OutputInterface $output)
  {
    $Updates   = $this->getUpdateList();
    $isRestart = false;
    $retval    = self::EXIT_SUCCESS;
    $remains   = [];

    foreach ($Updates as $macUpdate)
    {
      if (!$macUpdate->isRestart() && !$macUpdate->isHalt() && !$macUpdate->isBridgeOs())
      {
        $this->io()->msg('Installing '.$macUpdate->getName(), 60);
        $flags = ['no-scan' => true, 'install' => $macUpdate->getId()];

        $SU = $this->getSoftwareUpdateDriver($flags);
        $SU->run();

        if (!$SU->isSuccessful())
        {
          $retval = self::EXIT_ERROR;
          // Get the error text
          $err = $SU->getErrorOutput(true);
          // Display error badge
          $this->io()->errorln('[ERROR]');
          // Add errors to json output array
          $this->json()->append(['install' => [$macUpdate->getName() => $err]]);
          foreach ($err as $e)
          {
            $this->io()->writeln('  '.$e);
          }
        }
        else
        {
          $this->io()->successln('[SUCCESS]');
          $this->json()->append(['install' => [$macUpdate->getName() => true]]);
        }
      }
      else
      {
        $remains[] = $macUpdate->getName();
        $isRestart = true;
      }
    }

    if (self::EXIT_SUCCESS === $retval && $isRestart)
    {
      $this->io()->msg('Installing Remaining Updates', 60);
      $flags = ['no-scan' => true, 'install' => true, 'all' => true];

      $SU = $this->getSoftwareUpdateDriver($flags);
      $SU->run();

      if (!$SU->isSuccessful())
      {
        $err = $SU->getErrorOutput(true);
        $this->io()->errorln('[ERROR]');
        $retval = self::EXIT_ERROR;
        foreach ($err as $e)
        {
          $this->io()->writeln('  '.$e);
        }

        $this->json()->append(['install' => array_fill_keys($remains, $err)]);
      }
      else
      {
        $this->io()->successln('[SUCCESS]');
        $this->json()->append(['install' => array_fill_keys($remains, true)]);

        if (OutputInterface::VERBOSITY_VERBOSE === $this->io()->getVerbosity())
        {
          $this->io()->writeln($SU->getOutput(false));
        }

        // Will continue to a restart
        $retval = self::CONTINUE;
      }
    }

    return $retval;
  }

  /**
   * Adds the 'sus' test to the array of requested test keys.
   *
   * @param string[] $keys
   *
   * @return bool[]
   */
  protected function getTests($keys = ['cpu', 'user', 'power', 'screen', 'filevault', 'sus'])
  {
    $tests = array_fill_keys($keys, true);

    if ($this->io()->getOption('skip-cpu'))
    {
      $tests['cpu'] = false;
    }

    if ($this->io()->getOption('skip-power'))
    {
      $tests['power'] = false;
    }

    if ($this->io()->getOption('skip-filevault'))
    {
      $tests['filevault'] = false;
    }

    if ($this->io()->getOption('install'))
    {
      if ($this->io()->getOption('skip-user'))
      {
        $tests['user'] = false;
      }

      if ($this->io()->getOption('skip-screen'))
      {
        $tests['screen'] = false;
      }
    }

    return $tests;
  }

  /**
   * Evaluates whether the system is waiting on the given condition, adding the 'sus' key to other available keys.
   *
   * @param string $key
   *
   * @return bool|null
   */
  protected function is($key)
  {
    if ('sus' === $key)
    {
      $susUrl = $this->getDevice()->getOs()->getSoftwareUpdateCatalogUrl();

      return !$this->isSusAvailable($susUrl);
    }
    else
    {
      return parent::is($key);
    }
  }

  /**
   * Returns the ID of the user currently logged into the macOS GUI.
   *
   * @return string|null
   */
  protected function getConsoleUserId()
  {
    return $this->getUserId($this->getConsoleUser());
  }

  /**
   * Returns the default number of seconds that softwareupdate should be allowed to run before determining that
   * it has stalled.  This can be overridden with the --timeout option.
   *
   * @return int
   */
  protected function getDefaultTimeout()
  {
    return 7200; // 2 Hours
  }

  /**
   * @param MacUpdate[] $Updates
   *
   * @return array
   */
  protected function getSummary($Updates)
  {
    $susUrl = $this->getDevice()->getOs()->getSoftwareUpdateCatalogUrl();
    $onBatt = $this->isBatteryPowered();
    $isBatt = $this->getDevice()->getBattery()->isInstalled();

    $output = [
        'count'           => count($Updates),
        'recommended'     => 0,
        'restart'         => 0,
        'shutdown'        => 0,
        'bridgeos'        => 0,
        'battery'         => $onBatt,
        'battery_percent' => $isBatt ? $this->getDevice()->getBattery()->getPercentage() : null,
        'battery_minutes' => $onBatt ? $this->getDevice()->getBattery()->getUntilEmpty('%i') : null,
        'console_user'    => $this->getConsoleUser(),
        'console_userid'  => $this->getConsoleUserId(),
        'disk_space'      => $this->getDevice()->getFreeDiskSpace(),
        'encrypting'      => $this->isEncryptingFileVault(),
        'prevent_sleep'   => $this->isDisplaySleepPrevented(),
        'sus_offline'     => !$this->isSusAvailable($susUrl),
        'sus_url'         => $this->getDevice()->getOs()->getSoftwareUpdateCatalogUrl(),
        'content_cache'   => $this->getDevice()->getOs()->getSharedCaches(),
    ];

    foreach ($Updates as $Update)
    {
      if ($Update->isRecommended() && !$Update->isRestart() && !$Update->isHalt() && !$Update->isBridgeOs())
      {
        ++$output['recommended'];
      }

      if ($Update->isRestart())
      {
        ++$output['restart'];
      }

      if ($Update->isBridgeOs())
      {
        ++$output['bridgeos'];
      }

      if ($Update->isHalt())
      {
        ++$output['shutdown'];
      }
    }

    return $output;
  }

  /**
   * Returns the default number of seconds that softwareupdate should be allowed to run before determining that
   * it has stalled.  Set with the --timeout option, which provides a default based on the getDefaultTimeout method.
   *
   * @throws \Exception if for some reason, the value of the timeout option is falsy
   */
  private function getTimeout()
  {
    if (!$timeout = $this->io()->getOption('timeout'))
    {
      throw new \Exception('A default timeout for softwareupdate was not provided.');
    }

    return (is_numeric($timeout) && $timeout > 0) ? $timeout : null;
  }

  /**
   * @return MacUpdate[]
   *
   * @throws \Exception
   */
  protected function getUpdateList()
  {
    if (!isset($this->_Updates))
    {
      $this->_Updates = [];
      if ($this->getSoftwareUpdate())
      {
        // Create Process
        $noscan = $this->isNoScan() ? ['no-scan' => true] : [];
        $flags  = array_merge(['list' => true, 'all' => true], $noscan);

        $Parser  = new SoftwareUpdateParser($this->getDevice());
        $Process = $this->getSoftwareUpdateDriver($flags);
        // Run the Process
        $Process->run();

        // Check for Success
        if (!$Process->isSuccessful())
        {
          throw new ProcessFailedException($Process);
        }

        $output = $Process->getOutput(true);
        if (!empty($output))
        {
          $this->_Updates = $Parser->parse($output);
        }
      }
      else
      {
        throw new \Exception('The "softwareupdate" binary could not be located.');
      }
    }

    return $this->_Updates;
  }

  /**
   * If the 'no-scan' flag was used for this command instance.
   *
   * @return bool
   */
  protected function isNoScan()
  {
    if ($this->io()->getInput()->hasOption('no-scan'))
    {
      return (bool) $this->io()->getOption('no-scan');
    }

    return false;
  }

  /**
   * @return string The path to the softwareupdate Binary
   *
   * @throws \Exception
   */
  protected function getSoftwareUpdate()
  {
    if (empty($this->_SoftwareUpdateBinary))
    {
      $this->_SoftwareUpdateBinary = $this->getBinaryPath('softwareupdate');
    }

    return $this->_SoftwareUpdateBinary;
  }

  /**
   * @param $cmd
   *
   * @return string
   *
   * @throws \Exception
   */
  protected function getAtCommand($cmd)
  {
    if (file_exists('/usr/bin/at'))
    {
      return sprintf('echo "%s" | %s now + 2 minutes >/dev/null 2>&1', $cmd, '/usr/bin/at');
    }

    throw new \Exception('The AT binary was not found at /usr/bin/at');
  }

  /**
   * @param string[] $flags
   *
   * @return SoftwareUpdateDriver
   *
   * @throws \Exception
   */
  protected function getSoftwareUpdateDriver($flags)
  {
    $driver = SoftwareUpdateDriver::fromFlags($flags);

    if ($timeout = $this->getTimeout())
    {
      if (is_numeric($timeout))
      {
        $driver->setTimeout($timeout)->setIdleTimeout($timeout);
      }
      else
      {
        throw new \Exception('The value of the timeout option must be a number.');
      }
    }

    return $driver;
  }

  /**
   * @return bool
   */
  protected function isRestartRequired()
  {
    try
    {
      $Updates = $this->getUpdateList();
      foreach ($Updates as $macUpdate)
      {
        if ($macUpdate->isRestart() || ($macUpdate->isBridgeOs() && !empty($this->getRestartPolicy())))
        {
          return true;
        }
      }
    }
    catch (\Exception $e)
    {
      return false;
    }

    return false;
  }

  /**
   * @return bool
   */
  protected function isHaltRequired()
  {
    try
    {
      $Updates = $this->getUpdateList();
      foreach ($Updates as $macUpdate)
      {
        if ($macUpdate->isHalt() || ($macUpdate->isBridgeOs() && empty($this->getRestartPolicy())))
        {
          return true;
        }
      }
    }
    catch (\Exception $e)
    {
      return false;
    }

    return false;
  }

  /**
   * @param string|null $sus
   *
   * @return bool
   */
  protected function isSusAvailable($sus = '_NONE_')
  {
    $tSus = '_NONE_' === $sus ? $this->getOs()->getSoftwareUpdateCatalogUrl() : $sus;
    if (!empty($tSus))
    {
      $ua = sprintf('Darwin/%s', $this->getShellExec('uname -r'));
      if ($rs = $this->getShellExec(sprintf('curl --user-agent %s %s -I -s | grep 200', $ua, $sus)))
      {
        return false !== strpos($rs, '200');
      }
    }

    return true;
  }

  /**
   * @return bool
   */
  protected function isJamf()
  {
    if (!isset($this->jamf))
    {
      $this->jamf = is_executable('/usr/local/bin/jamf');
    }

    return $this->jamf;
  }

  /**
   * @return bool
   */
  protected function isJson()
  {
    return $this->io()->getOption('json');
  }

  /**
   * @return bool
   */
  protected function isInstall()
  {
    return $this->io()->getOption('install');
  }

  /**
   * @return bool
   */
  protected function isList()
  {
    return $this->io()->getOption('list');
  }

  /**
   * @return bool
   */
  protected function isSummary()
  {
    return $this->io()->getOption('summary');
  }

  /**
   * @return bool
   */
  protected function isCount()
  {
    return $this->io()->getOption('summary');
  }

  /**
   * @return bool
   */
  protected function isDownload()
  {
    return $this->io()->getOption('download');
  }

  /**
   * @return string|null
   */
  protected function getInstallPolicy()
  {
    $input = $this->io()->getInput();

    if ($input->hasOption('install-policy'))
    {
      return $input->getOption('install-policy') ?? null;
    }

    return null;
  }

  /**
   * @return string|null
   */
  protected function getRestartPolicy()
  {
    $input = $this->io()->getInput();

    if ($input->hasOption('restart-policy'))
    {
      return $input->getOption('restart-policy') ?? null;
    }

    return null;
  }

  /**
   * @return JsonHelper
   */
  public function json()
  {
    if (!isset($this->_JsonHelper))
    {
      $this->_JsonHelper = new JsonHelper($this->io()->getInput(), $this->io()->getOutput());
    }

    return $this->_JsonHelper;
  }
}
