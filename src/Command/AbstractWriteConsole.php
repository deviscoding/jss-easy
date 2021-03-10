<?php

namespace DevCoding\Jss\Helper\Command;

use DevCoding\Command\Base\IOHelper;
use DevCoding\Mac\Command\AbstractMacConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractUpdateConsole.
 *
 * @author  Aaron M Jones <aaron@jonesiscoding.com>
 *
 * @package DevCoding\Mac\Update\Command
 */
class AbstractWriteConsole extends AbstractMacConsole
{
  const FORMATS = ['pass', 'fail', IOHelper::FORMAT_SUCCESS, IOHelper::FORMAT_ERROR, IOHelper::FORMAT_COMMENT, IOHelper::FORMAT_MSG, IOHelper::FORMAT_INFO];

  // region //////////////////////////////////////////////// Symfony Command Methods

  protected function configure()
  {
    foreach (self::FORMATS as $FORMAT)
    {
      if (!empty($FORMAT))
      {
        $this->addOption($FORMAT, null, InputOption::VALUE_NONE);
      }
    }
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $this->setFormatOption($input);
  }

  protected function setFormatOption(InputInterface $input)
  {
    foreach (self::FORMATS as $FORMAT)
    {
      if ($input->hasOption($FORMAT) && $input->getOption($FORMAT))
      {
        if ('pass' == $FORMAT)
        {
          $FORMAT = IOHelper::FORMAT_SUCCESS;
        }
        elseif ('fail' == $FORMAT)
        {
          $FORMAT = IOHelper::FORMAT_ERROR;
        }

        $input->setOption('format', $FORMAT);

        return $this;
      }
    }

    return $this;
  }

  protected function isAllowUserOption()
  {
    return false;
  }

  // endregion ///////////////////////////////////////////// End Symfony Command Methods
}
