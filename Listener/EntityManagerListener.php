<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Listener;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Event\EntityManagerEvent;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\SeoBundle\Services\UrlParameterManagement;

/**
 * Austral EntityManager Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class EntityManagerListener
{

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParametersManagement;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @param Mapping $mapping
   * @param UrlParameterManagement $urlParametersManagement
   */
  public function __construct(Mapping $mapping, UrlParameterManagement $urlParametersManagement)
  {
    $this->urlParametersManagement = $urlParametersManagement;
    $this->mapping = $mapping;
  }

  /**
   * @param EntityInterface $object
   *
   * @return bool
   */
  protected function hasUrlParameterMapping(EntityInterface $object): bool
  {
    return (bool) $this->mapping->getEntityClassMapping($object->getClassnameForMapping(),UrlParameterMapping::class);
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @throws \Exception
   */
  public function duplicateUrlParameter(EntityManagerEvent $entityManagerEvent)
  {
    if($this->hasUrlParameterMapping($entityManagerEvent->getSourceObject()))
    {
      $this->urlParametersManagement->duplicateUrlParameter($entityManagerEvent->getSourceObject(), $entityManagerEvent->getObject());
    }
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @return void
   * @throws \Exception
   */
  public function updateUrlParameter(EntityManagerEvent $entityManagerEvent)
  {
    $object = $entityManagerEvent->getObject();
    if($this->hasUrlParameterMapping($object))
    {
      $this->urlParametersManagement->generateUrlParameter($object);
    }
    elseif($object instanceof UrlParameterInterface)
    {
      $this->urlParametersManagement->updateUrlParameterWithParent($object);
    }
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @return void
   */
  public function deleteUrlParameter(EntityManagerEvent $entityManagerEvent)
  {
    if($this->hasUrlParameterMapping($entityManagerEvent->getObject()))
    {
      $this->urlParametersManagement->deleteUrlParameter($entityManagerEvent->getObject());
    }
  }

}