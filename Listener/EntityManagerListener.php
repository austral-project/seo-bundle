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
   * @param UrlParameterManagement $urlParametersManagement
   */
  public function __construct(UrlParameterManagement $urlParametersManagement)
  {
    $this->urlParametersManagement = $urlParametersManagement;
  }

  /**
   * @param EntityInterface $object
   *
   * @return bool
   */
  protected function hasUrlParameterMapping(EntityInterface $object): bool
  {
    return $this->urlParametersManagement->hasEntityMappingByObjectClassname($object->getClassnameForMapping());
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
      $this->urlParametersManagement->duplicateUrlParameterByObject($entityManagerEvent->getSourceObject(), $entityManagerEvent->getObject());
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
    if($this->hasUrlParameterMapping($object) && !$object instanceof UrlParameterInterface)
    {
      $this->urlParametersManagement->generateUrlParameter($object);
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
      $this->urlParametersManagement->deleteUrlParameterByObject($entityManagerEvent->getObject());
    }
  }

}