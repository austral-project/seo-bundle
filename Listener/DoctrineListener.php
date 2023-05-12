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
use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Entity\Redirection;
use Austral\SeoBundle\Entity\Traits\UrlParameterTrait;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\SeoBundle\Services\RedirectionManagement;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral Doctrine Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener implements EventSubscriber
{

  /**
   * @var mixed
   */
  protected $name;

  /**
   * @var bool
   */
  protected bool $postFlush = false;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var RedirectionManagement
   */
  protected RedirectionManagement $redirectionManagement;

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParametersManagement;

  /**
   * DoctrineListener constructor.
   */
  public function __construct(Mapping $mapping, UrlParameterManagement $urlParametersManagement, RedirectionManagement $redirectionManagement)
  {
    $parts = explode('\\', $this->getNamespace());
    $this->name = end($parts);
    $this->mapping = $mapping;
    $this->redirectionManagement = $redirectionManagement;
    $this->urlParametersManagement = $urlParametersManagement;
  }

  /**
   * @return array
   */
  public function getSubscribedEvents()
  {
    return array(
      'postLoad',
      'preUpdate',
      "postFlush"
    );
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @throws \Exception
   */
  public function postLoad(LifecycleEventArgs $args)
  {
    /** @var EntityInterface|UrlParameterTrait $object */
    $object = $args->getObject();
    if($this->mapping->getEntityClassMapping($object->getClassnameForMapping(), UrlParameterMapping::class))
    {
      $urlParameters = array();
      if($object instanceof TranslateMasterInterface)
      {
        /** @var TranslateChildInterface $translate */
        foreach ($object->getTranslates() as $translate)
        {
          $urlParametersSelected = $this->urlParametersManagement->getUrlParametersByObject($object, $translate->getLanguage());
          /** @var UrlParameter $urlParameter */
          foreach ($urlParametersSelected as $urlParameter)
          {
            $urlParameters[$urlParameter->getLanguage()][] = $urlParameter;
          }
        }
      }
      else
      {
        $urlParametersSelected = $this->urlParametersManagement->getUrlParametersByObject($object);
        /** @var UrlParameter $urlParameter */
        foreach ($urlParametersSelected as $urlParameter)
        {
          $urlParameters[$urlParameter->getLanguage()][] = $urlParameter;
        }
      }
      $object->setUrlParameters($urlParameters);
    }
  }

  /**
   * @param PreUpdateEventArgs $args
   *
   * @throws NonUniqueResultException
   * @throws \Exception
   */
  public function preUpdate(PreUpdateEventArgs $args)
  {
    /** @var EntityInterface $object */
    $object = $args->getObject();
    if($object instanceof UrlParameterInterface)
    {
      if($args->hasChangedField("path"))
      {
        $this->redirectionManagement->generateRedirectionAuto($object, $args->getNewValue("path"), $args->getOldValue("path"));
      }
    }
  }

  /**
   * @param PostFlushEventArgs $args
   *
   */
  public function postFlush(PostFlushEventArgs $args)
  {
    if(!$this->postFlush)
    {
      $this->postFlush = true;
      $entityManager = $args->getObjectManager();
      /** @var Redirection $redirection */
      foreach($this->redirectionManagement->getRedirectionsUpdate() as $redirection)
      {
        if(!$redirection->getIsActive() && $redirection->getIsAutoGenerate())
        {
          $entityManager->remove($redirection);
        }
        else
        {
          $entityManager->persist($redirection);
        }
      }
      $entityManager->flush();
    }
  }

  /**
   * Get an event adapter to handle event specific
   * methods
   *
   * @param EventArgs $args
   *
   * @return EventArgs
   */
  protected function getEventAdapter(EventArgs $args): EventArgs
  {
    return $args;
  }

  /**
   * @return string
   */
  protected function getNamespace(): string
  {
    return __NAMESPACE__;
  }

}