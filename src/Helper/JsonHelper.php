<?php

namespace DevCoding\Jss\Easy\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsonHelper
{
  /** @var InputInterface */
  protected $input;
  /** @var OutputInterface */
  protected $output;
  /** @var array */
  protected $data = [];

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  public function __construct(InputInterface $input, OutputInterface $output)
  {
    $this->input  = $input;
    $this->output = $output;
  }

  /**
   * @param $data
   *
   * @return $this
   */
  public function append($data)
  {
    $this->data = $this->merge($this->data, $data);

    return $this;
  }

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function get($key)
  {
    return $data[$key] ?? null;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function has($key)
  {
    return array_key_exists($key, $this->data);
  }

  /**
   * @return bool
   */
  public function isEmpty()
  {
    return empty($this->data);
  }

  /**
   * @return $this
   */
  public function output()
  {
    if ($this->input->hasOption('json') && $this->input->getOption('json'))
    {
      $this->output->writeln(json_encode($this->data, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT), OutputInterface::VERBOSITY_QUIET);

      $this->data = [];
    }

    return $this;
  }

  /**
   * This function merges arrays the way array_merge_recursive() _should_ work.
   *
   * It differs in that if a key has a scalar value in both arrays, the value
   * from the right-most array (in the args) will take precedence.
   *
   * NOTE: This was borrowed from the comments thread of array_merge_recursive()
   *       on php.net. The author called it array_merge_recursive_distinct().
   */
  private function merge()
  {
    $arrays = func_get_args();
    $base   = array_shift($arrays);
    if (!is_array($base))
    {
      $base = empty($base) ? [] : [$base];
    }
    foreach ($arrays as $append)
    {
      if (!is_array($append))
      {
        // right side is not an array. since right side takes precedence,
        // squash whatever was on the left and make it a scalar value.
        $base = $append;
        continue;
      }
      foreach ($append as $key => $value)
      {
        if (!array_key_exists($key, $base) and !is_numeric($key))
        {
          $base[$key] = $value;
          continue;
        }
        if (is_array($value) or (isset($base[$key]) && is_array($base[$key])))
        {
          if (!isset($base[$key]))
          {
            $base[$key] = [];
          }
          $base[$key] = $this->merge($base[$key], $value);
        }
        elseif (is_numeric($key))
        {
          if (!in_array($value, $base))
          {
            $base[] = $value;
          }
        }
        else
        {
          $base[$key] = $value;
        }
      }
    }

    return $base;
  }
}
