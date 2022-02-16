<?php

namespace DevCoding\Jss\Easy\Object\Mac;

class MacUpdate implements \JsonSerializable
{
  /** @var bool */
  protected $bridgeOs = false;
  /** @var bool */
  protected $halt = false;
  /** @var string */
  protected $id;
  /** @var string */
  protected $name;
  /** @var bool */
  protected $recommended = false;
  /** @var bool */
  protected $restart = false;
  /** @var int */
  protected $size;

  /**
   * @param string $id
   */
  public function __construct($id)
  {
    $this->id = $id;
  }

  // region //////////////////////////////////////////////// Public Methods

  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return int
   */
  public function getSize()
  {
    return $this->size;
  }

  public function isBridgeOs()
  {
    return $this->bridgeOs;
  }

  /**
   * @return bool
   */
  public function isHalt(): bool
  {
    return $this->halt;
  }

  /**
   * @return bool
   */
  public function isRecommended()
  {
    return $this->recommended;
  }

  /**
   * @return bool
   */
  public function isRestart()
  {
    return $this->restart;
  }

  public function jsonSerialize()
  {
    return [
        'id'          => $this->getId(),
        'name'        => $this->getName(),
        'size'        => $this->getSize(),
        'recommended' => $this->isRecommended(),
        'restart'     => $this->isRestart(),
        'shutdown'    => $this->isBridgeos(),
    ];
  }

  /**
   * @param bool $bridgeOs
   *
   * @return MacUpdate
   */
  public function setBridgeOs($bridgeOs): MacUpdate
  {
    $this->bridgeOs = $bridgeOs;

    return $this;
  }

  /**
   * @param bool $halt
   *
   * @return MacUpdate
   */
  public function setHalt(bool $halt): MacUpdate
  {
    $this->halt = $halt;

    return $this;
  }

  /**
   * @param string $id
   *
   * @return MacUpdate
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  /**
   * @param string $name
   *
   * @return MacUpdate
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * @param bool $recommended
   *
   * @return MacUpdate
   */
  public function setRecommended($recommended)
  {
    $this->recommended = $recommended;

    return $this;
  }

  /**
   * @param bool $restart
   *
   * @return MacUpdate
   */
  public function setRestart($restart)
  {
    $this->restart = $restart;

    return $this;
  }

  /**
   * @param int $size
   *
   * @return MacUpdate
   */
  public function setSize($size)
  {
    $this->size = (int) str_replace('K', '', $size);

    return $this;
  }

  // endregion ///////////////////////////////////////////// Public Methods
}
