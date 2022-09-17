<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Event;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Pages Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class PagesSelectObjectsEvent extends Event
{

  const EVENT_SELECT_OBJECTS = "austral.seo.select_objects";

  /**
   * @var EntityManagerInterface
   */
  private EntityManagerInterface $entityManager;

  /**
   * @var string
   */
  private string $classname;

  /**
   * @var bool
   */
  private bool $queryByStatus;

  /**
   * @var AbstractQuery|null
   */
  private ?AbstractQuery $query = null;

  /**
   * PagesEvent constructor.
   *
   * @param EntityManagerInterface $entityManager
   * @param string $classname
   * @param bool $queryByStatus
   */
  public function __construct(EntityManagerInterface $entityManager, string $classname, bool $queryByStatus = true)
  {
    $this->entityManager = $entityManager;
    $this->classname = $classname;
    $this->queryByStatus = $queryByStatus;
  }

  /**
   * @return string
   */
  public function getClassname(): string
  {
    return $this->classname;
  }

  /**
   * @return EntityManagerInterface
   */
  public function getEntityManager(): EntityManagerInterface
  {
    return $this->entityManager;
  }

  /**
   * @return bool
   */
  public function getQueryByStatus(): bool
  {
    return $this->queryByStatus;
  }

  /**
   * @return AbstractQuery|null
   */
  public function getQuery(): ?AbstractQuery
  {
    return $this->query;
  }

  /**
   * @param AbstractQuery $query
   *
   * @return PagesSelectObjectsEvent
   */
  public function setQuery(AbstractQuery $query): PagesSelectObjectsEvent
  {
    $this->query = $query;
    return $this;
  }

}