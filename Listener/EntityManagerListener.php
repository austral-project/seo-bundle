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
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
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
   * @param UrlParameterManagement $urlParametersManagement
   */
  public function __construct(UrlParameterManagement $urlParametersManagement)
  {
    $this->urlParametersManagement = $urlParametersManagement;
  }

  protected bool $isCreateDomain = false;

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @return void
   */
  public function createDomain(EntityManagerEvent $entityManagerEvent)
  {
    if($entityManagerEvent->getObject() instanceof DomainInterface)
    {
      $this->isCreateDomain = true;
    }
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @return void
   * @throws \Exception
   */
  public function generateUrlParameter(EntityManagerEvent $entityManagerEvent)
  {
    $object = $entityManagerEvent->getObject();
    if($this->isCreateDomain && $object instanceof DomainInterface)
    {
      /** @var DomainInterface $domain */
      $domain = $entityManagerEvent->getObject();
      $this->urlParametersManagement->addUrlParametersByDomainAllLanguages($domain)
        ->generateAllUrlParameters($domain->getId());
    }
    else if(!$object instanceof UrlParameterInterface && $this->urlParametersManagement->hasEntityMappingByObjectClassname($object->getClassnameForMapping()))
    {
      $this->urlParametersManagement->generateUrlParameter($object);
    }
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
    elseif($object instanceof UrlParameterInterface)
    {
      $this->urlParametersManagement->updateUrlParameter($object);
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