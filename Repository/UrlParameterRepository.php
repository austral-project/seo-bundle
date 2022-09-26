<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Repository;

use Austral\EntityBundle\Repository\EntityRepository;
use Austral\HttpBundle\Services\DomainsManagement;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use phpDocumentor\Reflection\Types\Collection;

/**
 * Austral UrlParameter Repository.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterRepository extends EntityRepository
{


  /**
   * @param string $objectRelation
   * @param string $language
   * @param string|null $domainId
   *
   * @return int|mixed|string|null
   * @throws NonUniqueResultException
   */
  public function retreiveByObjectRelation(string $objectRelation, string $language, ?string $domainId = null)
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->where('root.objectRelation = :objectRelation')
      ->andWhere('root.language = :language')
      ->setParameter("objectRelation", $objectRelation)
      ->setParameter("language", $language)
      ->setMaxResults(1)
      ->orderBy("root.updated", "DESC");

      if($domainId) {
        $queryBuilder->andWhere("root.domainId = :domainId")
          ->setParameter("domainId", $domainId);
      }
      else {
        $queryBuilder->andWhere("root.domainId IS NULL");
      }
      $query = $queryBuilder->getQuery();
    try {
      $object = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
      $object = null;
    }
    return $object;
  }


  /**
   * @param string $language
   * @param string|null $domainId
   * @param bool $isMaster
   *
   * @return Collection|array
   * @throws QueryException
   */
  public function selectUrlsParametersByDomainId(string $language, string $domainId = null, bool $isMaster = false)
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->where('root.language = :language')
      ->setParameter("language", $language)
      ->indexBy("root", "root.id")
      ->orderBy("root.path", "ASC")
      ->addOrderBy("root.updated", "DESC");

    if($domainId && $isMaster)
    {
      $queryBuilder->andWhere("root.domainId = :domainId OR root.domainId = :domainMasterId")
        ->setParameter("domainId", $domainId)
        ->setParameter("domainMasterId", DomainsManagement::DOMAIN_ID_MASTER);
    }
    elseif($domainId)
    {
      $queryBuilder->andWhere("root.domainId = :domainId")
        ->setParameter("domainId", $domainId);
    }
    else
    {
      $queryBuilder->andWhere("root.domainId IS NULL");
    }
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (\Doctrine\Orm\NoResultException $e) {
      $objects = null;
    }
    return $objects;
  }


  /**
   * @return Collection|array
   * @throws QueryException
   */
  public function selectUrlsParameters()
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->indexBy("root", "root.id")
      ->orderBy("root.path", "ASC")
      ->addOrderBy("root.updated", "DESC");
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (\Doctrine\Orm\NoResultException $e) {
      $objects = null;
    }
    return $objects;
  }



}
