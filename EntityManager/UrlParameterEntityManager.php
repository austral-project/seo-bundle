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

use Austral\SeoBundle\Repository\UrlParameterRepository;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral UrlParameter Entity Manager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class UrlParameterEntityManager extends EntityManager
{

  /**
   * @var UrlParameterRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return UrlParameterInterface
   */
  public function create(array $values = array()): UrlParameterInterface
  {
    return parent::create($values);
  }

  /**
   * @param $entityName
   * @param $entityId
   * @param $language
   *
   * @return UrlParameterInterface
   * @throws NonUniqueResultException
   */
  public function retreiveByEntity($entityName, $entityId, $language): UrlParameterInterface
  {
    return $this->repository->retreiveByEntity($entityName, $entityId, $language);
  }

}
