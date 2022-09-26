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

use Austral\SeoBundle\Annotation\UrlParameterObject;
use Austral\SeoBundle\Annotation\UrlParameterAction;
use Austral\SeoBundle\Mapping\UrlParameterMapping;

use Austral\EntityBundle\EntityAnnotation\EntityAnnotations;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityMapping Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityMappingListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * @param EntityMappingEvent $entityAnnotationEvent
   *
   * @return void
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityAnnotationEvent)
  {
    $initialiseEntitesAnnotations = $entityAnnotationEvent->getEntitiesAnnotations();

    /**
     * @var EntityAnnotations $entityAnnotation
     */
    foreach($initialiseEntitesAnnotations->all() as $entityAnnotation)
    {
      if(array_key_exists(UrlParameterObject::class, $entityAnnotation->getClassAnnotations()) || array_key_exists(UrlParameterAction::class, $entityAnnotation->getClassAnnotations()))
      {
        if(!$entityMapping = $entityAnnotationEvent->getMapping()->getEntityMapping($entityAnnotation->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAnnotation->getClassname(), $entityAnnotation->getSlugger());
        }
        $urlParameterMapping = new UrlParameterMapping();
        if(array_key_exists(UrlParameterObject::class, $entityAnnotation->getClassAnnotations()))
        {
          /** @var UrlParameterObject $urlParameterObjectAnnotation */
          $urlParameterObjectAnnotation = $entityAnnotation->getClassAnnotations()[UrlParameterObject::class];

          $urlParameterMapping->setMethodGenerateLastPath($urlParameterObjectAnnotation->methodGenerateLastPath);
          $urlParameterMapping->setMethodUrlName($urlParameterObjectAnnotation->methodUrlName);
          if(!$keyForObjectLink = $urlParameterObjectAnnotation->keyForObjectLink)
          {
            $keyForObjectLink = (new \ReflectionClass($entityMapping->entityClass))->getShortName();
          }
          $urlParameterMapping->setKeyForObjectLink($keyForObjectLink);
        }
        $entityMapping->addEntityClassMapping($urlParameterMapping);
        $entityAnnotationEvent->getMapping()->addEntityMapping($entityAnnotation->getClassname(), $entityMapping);
      }
    }
  }

}
