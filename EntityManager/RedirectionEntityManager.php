<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\EntityManager;

use Austral\SeoBundle\Repository\RedirectionRepository;
use Austral\SeoBundle\Entity\Interfaces\RedirectionInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral Redirection Entity Manager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class RedirectionEntityManager extends EntityManager
{

  /**
   * @var RedirectionRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return RedirectionInterface
   */
  public function create(array $values = array()): RedirectionInterface
  {
    return parent::create($values);
  }

  /**
   * @param $entityName
   * @param $entityId
   * @param $language
   *
   * @return RedirectionInterface
   * @throws NonUniqueResultException
   */
  public function retreiveByEntity($entityName, $entityId, $language): RedirectionInterface
  {
    return $this->repository->retreiveByEntity($entityName, $entityId, $language);
  }

  /**
   * @param string $urlSource
   * @param string|null $domainId
   * @param string|null $language
   *
   * @return int|mixed|string|null
   * @throws NonUniqueResultException
   */
  public function retreiveByUrlSource(string $urlSource, string $domainId = null, string $language = null)
  {
    return $this->repository->retreiveByUrlSource($urlSource, $domainId, $language);
  }

  /**
   * @param string $urlDestination
   * @param string|null $domainId
   * @param string|null $language
   *
   * @return int|mixed|string|null
   * @throws NonUniqueResultException
   */
  public function retreiveByUrlDestination(string $urlDestination, string $domainId = null, string $language = null)
  {
    return $this->repository->retreiveByUrlDestination($urlDestination, $domainId, $language);
  }

}
