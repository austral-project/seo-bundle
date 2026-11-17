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

use Austral\SeoBundle\Attribute\UrlParameterObject;
use Austral\SeoBundle\Attribute\UrlParameterAction;
use Austral\SeoBundle\Mapping\UrlParameterMapping;

use Austral\EntityBundle\EntityAttributes\EntityAttributes;
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
   * @param EntityMappingEvent $entityMappingEvent
   *
   * @return void
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityMappingEvent): void
  {
    /**
     * @var EntityAttributes $entityAttribute
     */
    foreach($entityMappingEvent->getEntitiesAttributes()->all() as $entityAttribute)
    {
      $classAttributes = $entityAttribute->getClassAttributes();
      if(array_key_exists(UrlParameterObject::class, $classAttributes) || array_key_exists(UrlParameterAction::class, $classAttributes))
      {
        if(!$entityMapping = $entityMappingEvent->getMapping()->getEntityMapping($entityAttribute->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAttribute->getClassname(), $entityAttribute->getSlugger());
        }
        $urlParameterMapping = new UrlParameterMapping();
        if(array_key_exists(UrlParameterObject::class, $classAttributes))
        {
          /** @var UrlParameterObject $urlParameterObjectAttribute */
          $urlParameterObjectAttribute = $classAttributes[UrlParameterObject::class];

          $urlParameterMapping->setMethodGenerateLastPath($urlParameterObjectAttribute->methodGenerateLastPath);
          $urlParameterMapping->setMethodUrlName($urlParameterObjectAttribute->methodUrlName);
          if(!$keyForObjectLink = $urlParameterObjectAttribute->keyForObjectLink)
          {
            $keyForObjectLink = (new \ReflectionClass($entityMapping->entityClass))->getShortName();
          }
          $urlParameterMapping->setKeyForObjectLink($keyForObjectLink);
        }
        $entityMapping->addEntityClassMapping($urlParameterMapping);
        $entityMappingEvent->getMapping()->addEntityMapping($entityAttribute->getClassname(), $entityMapping);
      }
    }
  }

}
