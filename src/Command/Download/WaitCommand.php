<?php

namespace DevCoding\Jss\Easy\Command\Download;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class WaitCommand extends AbstractWaitConsole
{
  protected function configure()
  {
    $this->setName('wait');

    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $seconds = $this->io()->getArgument('seconds');
    $isJson  = $this->io()->getOption('json');
    $tests   = $this->getTests();
    $wait    = $tests;

    // Set Verbosity for JSON Output
    if ($isJson)
    {
      $this->io()->getOutput()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    // Wait for Time
    $this->io()->blankln(1, OutputInterface::VERBOSITY_VERBOSE);
    $this->io()->info('Counting Down... ', 40, OutputInterface::VERBOSITY_VERBOSE);
    while ($seconds > 0)
    {
      $this->io()->write($seconds.'..', null, null, OutputInterface::VERBOSITY_VERBOSE);
      sleep(1);

      if ($this->isWaiting($wait))
      {
        --$seconds;
      }
      else
      {
        $seconds = 0;
      }
    }

    if ($this->io()->getOption('json'))
    {
      $summary = [];
      foreach ($tests as $key => $value)
      {
        $summary[$key] = $wait[$key] ?? false;
      }

      $this->io()->writeln(json_encode($summary, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT), null, false, OutputInterface::VERBOSITY_QUIET);
    }
    else
    {
      $friendly = self::TESTS;
      foreach ($tests as $key => $value)
      {
        if ($value)
        {
          $this->io()->info('Waiting on '.$friendly[$key] ?? strtoupper($key), 50);

          if ($wait[$key] ?? false)
          {
            $this->io()->errorln('[YES]');
          }
          else
          {
            $this->io()->successln('[NO]');
          }
        }
      }
    }

    return !empty(array_keys(array_filter($wait))) ? self::EXIT_ERROR : self::EXIT_SUCCESS;
  }
}
