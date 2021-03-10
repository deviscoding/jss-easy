<?php

namespace DevCoding\Jss\Helper\Command;

use DevCoding\Mac\Command\AbstractMacConsole;

abstract class AbstractInfoConsole extends AbstractMacConsole
{
  protected function isAllowUserOption(): bool
  {
    return false;
  }

  protected function isJson()
  {
    return $this->io()->getOption('json');
  }

  protected function getSubkey($key)
  {
    return ($pos = strpos($key, '.')) ? substr($key, $pos + 1) : null;
  }

  protected function renderOutput($data, $prefix = null)
  {
    foreach ($data as $key => $value)
    {
      if (is_array($value))
      {
        $this->renderOutput($value, $key);
      }
      else
      {
        if (false === $value)
        {
          $value = 'false';
        }
        elseif (true === $value)
        {
          $value = 'true';
        }
        elseif (null === $value)
        {
          $value = 'null';
        }

        $pKey = ($prefix) ? $prefix.'.'.$key : $key;
        
        $this->io()->info($pKey.':', 50)->writeln($value);
      }


    }
  }
}
