<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Services;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\EntityBundle\Entity\Interfaces\TreePageInterface;
use Austral\EntityBundle\EntityManager\EntityManagerORMInterface;
use Austral\EntityBundle\Mapping\EntityClassMappingInterface;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\SeoBundle\Configuration\SeoConfiguration;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Event\UrlParameterEvent;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Austral Page url generator service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterManagement
{
  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domains;

  /**
   * @var EntityManagerORMInterface
   */
  protected EntityManagerORMInterface $entityManager;

  /**
   * @var UrlParameterEntityManager
   */
  protected UrlParameterEntityManager $parameterEntityManager;

  /**
   * @var SeoConfiguration
   */
  protected SeoConfiguration $SeoConfiguration;

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * @var UrlParameterMigrate
   */
  protected UrlParameterMigrate $urlParameterMigrate;

  /**
   * @var array
   */
  protected array $entitiesMapping = array();

  /**
   * @var array
   */
  protected array $keysForObjectLink = array();

  /**
   * @var array
   */
  protected array $objectUrlParameters = array();

  /**
   * @var array
   */
  protected array $urlsByDomains = array();

  /**
   * @var array
   */
  protected array $urlsByDomainsWithTree = array();

  /**
   * @var array
   */
  protected array $urlsByDomainAndObjectKey = array();

  /**
   * @var array
   */
  protected array $objectKeyLinkNames = array();

  /**
   * @var array
   */
  protected array $urlsConflictsByDomains = array();

  /**
   * @var array
   */
  protected array $nbUrlsStatusByDomains = array();

  /**
   * @var DomainInterface
   */
  private DomainInterface $domainForAll;

  /**
   * @param Mapping $mapping
   * @param EventDispatcherInterface $dispatcher
   * @param UrlParameterEntityManager $parameterEntityManager
   * @param EntityManagerORMInterface $entityManager
   * @param SeoConfiguration $SeoConfiguration
   * @param DomainsManagement $domains
   * @param UrlParameterMigrate $urlParameterMigrate
   *
   * @throws QueryException
   * @throws \Exception
   */
  public function __construct(Mapping $mapping,
    EventDispatcherInterface $dispatcher,
    UrlParameterEntityManager $parameterEntityManager,
    EntityManagerORMInterface $entityManager,
    SeoConfiguration $SeoConfiguration,
    DomainsManagement $domains,
    UrlParameterMigrate $urlParameterMigrate
  )
  {
    $this->mapping = $mapping;
    $this->dispatcher = $dispatcher;
    $this->entityManager = $entityManager;
    $this->parameterEntityManager = $parameterEntityManager;
    $this->SeoConfiguration = $SeoConfiguration;
    $this->domains = $domains->initialize();
    $this->domainForAll = $domains->getDomainForAll();
    $this->urlParameterMigrate = $urlParameterMigrate;
    $this->initEntitiesMapping();
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function refresh(): UrlParameterManagement
  {
    $this->initEntitiesMapping(true);
    return $this;
  }

  /**
   * @param bool $reload
   *
   * @return $this
   * @throws \Exception
   */
  public function initEntitiesMapping(bool $reload = false): UrlParameterManagement
  {
    if((count($this->entitiesMapping) == 0) || $reload)
    {
      /** @var EntityMapping $entityMapping */
      foreach($this->mapping->getEntitiesMapping() as $entityMapping)
      {
        /** @var UrlParameterMapping $urlParameterMapping */
        if($urlParameterMapping = $entityMapping->getEntityClassMapping(UrlParameterMapping::class))
        {
          $this->keysForObjectLink[$urlParameterMapping->getKeyForObjectLink()] = $entityMapping->entityClass;
          $this->entitiesMapping[$entityMapping->entityClass] = array(
            "mapping" => $entityMapping,
            "objects" =>  $this->entityManager
            ->getRepository($entityMapping->entityClass)
            ->selectByClosure(function(QueryBuilder $queryBuilder) use($entityMapping){
              if($entityMapping->getEntityClassMapping("Austral\EntityTranslateBundle\Mapping\EntityTranslateMapping"))
              {
                $queryBuilder->leftJoin("root.translates", "translates")->addSelect("translates");
              }
              $queryBuilder->indexBy("root", "root.id");
            })
          );
        }
      }
    }

    /** @var DomainInterface $domain */
    foreach($this->domains->getDomainsWithoutVirtual() as $domain)
    {
      $this->urlsByDomains[$domain->getId()] = array(
        "domain" =>  $domain,
        "urls"    =>  array()
      );
      $this->urlsByDomainsWithTree[$domain->getId()] = array(
        "domain" =>  $domain,
        "urls"    =>  array()
      );
      $this->urlsConflictsByDomains[$domain->getId()] = array();
      $this->urlsByDomainAndObjectKey[$domain->getId()] = array();
      $this->nbUrlsStatusByDomains[$domain->getId()] = array(
        UrlParameterInterface::STATUS_PUBLISHED =>  0,
        UrlParameterInterface::STATUS_DRAFT =>  0,
        UrlParameterInterface::STATUS_UNPUBLISHED =>  0,
      );
    }
    if($this->domains->getEnabledDomainWithoutVirtual() === 0)
    {

      $this->urlsByDomains[$this->domains->getCurrentDomain()->getId()] = array(
        "domain" =>  $this->domains->getCurrentDomain(),
        "urls"    =>  array()
      );
      $this->urlsByDomainsWithTree[$this->domains->getCurrentDomain()->getId()] = array(
        "domain" =>  $this->domains->getCurrentDomain(),
        "urls"    =>  array()
      );
      $this->urlsConflictsByDomains[$this->domains->getCurrentDomain()->getId()] = array();
      $this->urlsByDomainAndObjectKey[$this->domains->getCurrentDomain()->getId()] = array();
      $this->nbUrlsStatusByDomains[$this->domains->getCurrentDomain()->getId()] = array(
        UrlParameterInterface::STATUS_PUBLISHED =>  0,
        UrlParameterInterface::STATUS_DRAFT =>  0,
        UrlParameterInterface::STATUS_UNPUBLISHED =>  0,
      );
    }
    $this->urlsByDomains[$this->domainForAll->getId()] = array(
      "domain" =>  $this->domainForAll,
      "urls"    =>  array()
    );

    $urlParameterEvent = new UrlParameterEvent();
    $this->dispatcher->dispatch($urlParameterEvent, UrlParameterEvent::EVENT_START);

    $this->objectUrlParameters = $this->entityManager
      ->getRepository(UrlParameterInterface::class)
      ->selectUrlsParameters($this->domains->getCurrentLanguage());

    /** @var UrlParameterInterface $urlParameter */
    foreach($this->objectUrlParameters as $urlParameter)
    {
      $this->hydrateUrl($urlParameter);
    }

    if($domainMaster = $this->domains->getDomainMaster())
    {
      foreach($this->entitiesMapping as $valuesByEntity)
      {
        /** @var EntityInterface $object */
        foreach($valuesByEntity["objects"] as $object)
        {
          if(!$object instanceof FilterByDomainInterface || !$object->getDomainId())
          {
            /** @var UrlParameterInterface $defaultUrlParameters */
            if($defaultUrlParameters = $this->getUrlParameterByObject($object, $domainMaster->getId()))
            {
              if(!array_key_exists($defaultUrlParameters->getPath(), $this->urlsByDomains[$this->domainForAll->getId()]["urls"]))
              {
                $urlParameterForAllDomain = clone $defaultUrlParameters;
                $urlParameterForAllDomain->setId(Uuid::uuid4()->toString());
                /** @var UrlParameterMapping $urlParameterMapping */
                $urlParameterMapping = $valuesByEntity["mapping"]->getEntityClassMapping(UrlParameterMapping::class);
                $urlParameterForAllDomain->setKeyLink("{$urlParameterMapping->getKeyForObjectLink()}::{$object->getId()}");
                $this->urlsByDomains[$this->domainForAll->getId()]["urls"][$urlParameterForAllDomain->getPath()] = $urlParameterForAllDomain;
                $this->objectKeyLinkNames[$urlParameterForAllDomain->getKeyLink()] = $object->__toString();
              }
            }
          }
        }
      }
      ksort($this->urlsByDomains[$this->domainForAll->getId()]["urls"]);
    }
    $this->dispatcher->dispatch($urlParameterEvent, UrlParameterEvent::EVENT_END);

    /** @var DomainInterface $domain */
    foreach($this->urlsByDomains as $keyDomain => $urlsByDomain)
    {
      $this->urlsByDomainsWithTree[$keyDomain] = array(
        "domain"  =>  $urlsByDomain["domain"],
        "urls"    =>  $this->transformTree(AustralTools::arrayByFlatten($urlsByDomain["urls"], "/"))
      );
      ksort($this->urlsByDomainsWithTree[$keyDomain]["urls"]);
    }

    return $this;
  }

  /**
   * @param $values
   * @param array $urlsByDomainWithTree
   *
   * @return array|mixed
   */
  protected function transformTree($values, array $urlsByDomainWithTree = array())
  {
    foreach($values as $key => $value)
    {
      if(array_key_exists("element", $value))
      {
        $urlsByDomainWithTree[$key]["urlParameter"] = $value["element"];
        unset($value["element"]);
        $urlsByDomainWithTree[$key]["children"] = $this->transformTree($value);
      }
    }
    return $urlsByDomainWithTree;
  }

  /**
   * @param string $status
   *
   * @return int
   */
  public function getTotalUrlsByStatus(string $status): int
  {
    $count = 0;
    foreach ($this->nbUrlsStatusByDomains as $nbUrlsStatusByDomain)
    {
      $count += AustralTools::getValueByKey($nbUrlsStatusByDomain, $status, 0);
    }
    return $count;
  }

  /**
   * @param string $domainId
   *
   * @return array
   */
  public function getNbUrlsStatusByDomain(string $domainId): array
  {
    return AustralTools::getValueByKey($this->nbUrlsStatusByDomains, $domainId, array());
  }

  /**
   * @param string $domainId
   * @param string $status
   *
   * @return int|null
   */
  public function getNbUrlsStatusByDomainAndStatus(string $domainId, string $status): ?int
  {
    return AustralTools::getValueByKey($this->getNbUrlsStatusByDomain($domainId), $status, null);
  }

  /**
   * @return void
   */
  public function generateAllWithMapping()
  {
    foreach($this->entitiesMapping as $entityMapping)
    {
      foreach($entityMapping['objects'] as $object)
      {
        $this->generateUrlParameter($object);
      }
    }
    $this->entityManager->flush();
  }

  /**
   * @param EntityInterface $object
   *
   * @return EntityMapping|null
   */
  public function getEntityMappingByObject(EntityInterface $object): ?EntityMapping
  {
    return array_key_exists($object->getClassnameForMapping(), $this->entitiesMapping) ? $this->entitiesMapping[$object->getClassnameForMapping()]["mapping"] : null;
  }

  /**
   * @param EntityInterface $object
   *
   * @return EntityClassMappingInterface|null
   */
  protected function getObjectUrlParameterMapping(EntityInterface $object): ?EntityClassMappingInterface
  {
    if($entityMapping = $this->getEntityMappingByObject($object))
    {
      return $entityMapping->getEntityClassMapping(UrlParameterMapping::class);
    }
    return null;
  }

  /**
   * @return array
   */
  public function getObjectKeyLinkNames(): array
  {
    return $this->objectKeyLinkNames;
  }

  /**
   * @return array
   */
  public function getUrlParametersByDomains(): array
  {
    return $this->urlsByDomains;
  }

  /**
   * @return array
   */
  public function getUrlsConflictsByDomains(): array
  {
    return $this->urlsConflictsByDomains;
  }

  /**
   * @return array
   */
  public function getUrlParametersByDomainsWithTree(): array
  {
    return $this->urlsByDomainsWithTree;
  }

  /**
   * @param string $domainId
   *
   * @return string|null
   */
  protected function getReelDomainId(string $domainId = "current"): ?string
  {
    return $domainId === "current" ? $this->domains->getFilterDomainId() : $domainId;
  }

  /**
   * @param string $domainId
   *
   * @return array
   */
  public function getUrlParametersByDomain(string $domainId = "current"): array
  {
    $domainId = $this->getReelDomainId($domainId);
    return array_key_exists($domainId, $this->urlsByDomains) ? $this->urlsByDomains[$domainId]["urls"] : array();
  }

  /**
   * @param string $domainId
   *
   * @return array
   */
  public function getUrlParameterByObjectAndDomain(string $domainId = "current"): array
  {
    $domainId = $this->getReelDomainId($domainId);
    return array_key_exists($domainId, $this->urlsByDomainAndObjectKey) ? $this->urlsByDomainAndObjectKey[$domainId] : array();
  }

  /**
   * @param string|null $path
   * @param string $domainId
   *
   * @return ?UrlParameterInterface
   */
  public function retrieveUrlParameters(?string $path = null, string $domainId = "current"): ?UrlParameterInterface
  {
    return AustralTools::getValueByKey($this->getUrlParametersByDomain($domainId), $path);
  }

  /**
   * @param string $urlParameterId
   *
   * @return ?UrlParameterInterface
   */
  public function retrieveUrlParametersById(string $urlParameterId): ?UrlParameterInterface
  {
    return AustralTools::getValueByKey($this->objectUrlParameters, $urlParameterId, null);
  }

  /**
   * @param EntityInterface $object
   * @param string $domainId
   *
   * @return EntityInterface|null
   */
  public function getUrlParameterByObject(EntityInterface $object, string $domainId = "current"): ?EntityInterface
  {
    return $this->getUrlParameterByObjectClassnameAndId($object->getClassnameForMapping(), $object->getId(), $domainId);
  }

  /**
   * @param EntityInterface $object
   * @param string $domainId
   *
   * @return array
   */
  public function getUrlParametersByObject(EntityInterface $object, string $domainId = "current"): array
  {
    $urlsParameters = array();
    $classname = $object->getClassnameForMapping();
    if(array_key_exists($classname, $this->keysForObjectLink))
    {
      $classname = $this->keysForObjectLink[$classname];
    }
    $objectKey = "{$classname}::{$object->getId()}";
    foreach($this->urlsByDomainAndObjectKey as $urlsByObjectKey)
    {
      if(array_key_exists($objectKey, $urlsByObjectKey))
      {
        $urlsParameters[] = $urlsByObjectKey[$objectKey];
      }
    }
    return $urlsParameters;
  }

  /**
   * @param string $classname
   * @param string|int $objectId
   * @param string $domainId
   *
   * @return EntityInterface|null
   */
  public function getUrlParameterByObjectClassnameAndId(string $classname, $objectId, string $domainId = "current"): ?EntityInterface
  {
    if(array_key_exists($classname, $this->keysForObjectLink))
    {
      $classname = $this->keysForObjectLink[$classname];
    }
    return AustralTools::getValueByKey($this->getUrlParameterByObjectAndDomain($domainId), "{$classname}::{$objectId}");
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return EntityInterface|null
   */
  public function getObjectRelationByUrlParameter(UrlParameterInterface $urlParameter): ?EntityInterface
  {
    return $this->getObjectRelationByClassnameAndId($urlParameter->getObjectClass(), $urlParameter->getObjectId());
  }

  /**
   * @param string $classname
   * @param string|int $objectId
   *
   * @return EntityInterface|null
   */
  public function getObjectRelationByClassnameAndId(string $classname, $objectId): ?EntityInterface
  {
    if(array_key_exists($classname, $this->keysForObjectLink))
    {
      $classname = $this->keysForObjectLink[$classname];
    }
    $entityMapping = AustralTools::getValueByKey($this->entitiesMapping, $classname, array());
    $entityMappingObject = AustralTools::getValueByKey($entityMapping, "objects", array());
    return AustralTools::getValueByKey($entityMappingObject, $objectId);
  }

  /**
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function addObjectRelationByClassnameAndId(EntityInterface $object): UrlParameterManagement
  {
    if(array_key_exists($object->getClassnameForMapping(), $this->entitiesMapping))
    {
      $this->entitiesMapping[$object->getClassnameForMapping()]["objects"][$object->getId()] = $object;
    }
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return $this
   */
  public function hydrateUrl(UrlParameterInterface $urlParameter): UrlParameterManagement
  {
    $urlParameter->setDomain($this->domains->getDomainById($urlParameter->getDomainId()));
    if($urlParameter->getObjectRelation())
    {
      if($object = $this->getObjectRelationByUrlParameter($urlParameter))
      {
        $this->urlsByDomainAndObjectKey[$urlParameter->getDomainId()]["{$object->getClassnameForMapping()}::{$object->getId()}"] = $urlParameter;
        $urlParameter->setObject($object);
        if($this->getEntityMappingByObject($object)) {
          $urlParameter->setKeyLink("{$urlParameter->getClassname()}::{$urlParameter->getId()}");
          $this->objectKeyLinkNames[$urlParameter->getKeyLink()] = $object->__toString();
        }
      }
    }

    /** @var UrlParameterInterface $urlCompare */
    if($urlCompare = AustralTools::getValueByKey($this->urlsByDomains[$urlParameter->getDomainId()]["urls"], $urlParameter->getPath()))
    {
      if($urlCompare->getId() !== $urlParameter->getId())
      {
        if(!array_key_exists($urlParameter->getPath(), $this->urlsConflictsByDomains[$urlParameter->getDomainId()]))
        {
          $this->urlsConflictsByDomains[$urlParameter->getDomainId()][$urlParameter->getPath()] = array(
            $urlCompare
          );
        }
        $this->urlsConflictsByDomains[$urlParameter->getDomainId()][$urlParameter->getPath()][] = $urlParameter;
      }
    }
    else
    {
      $this->urlsByDomains[$urlParameter->getDomainId()]["urls"][$urlParameter->getPath()] = $urlParameter;
    }
    $this->nbUrlsStatusByDomains[$urlParameter->getDomainId()][$urlParameter->getStatus()]++;
    ksort($this->urlsByDomains[$urlParameter->getDomainId()]["urls"]);
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return $this
   */
  public function removeUrlPath(UrlParameterInterface $urlParameter): UrlParameterManagement
  {
    if(array_key_exists($urlParameter->getPath(), $this->urlsByDomains[$urlParameter->getDomainId()]["urls"]))
    {
      unset($this->urlsByDomains[$urlParameter->getDomainId()]["urls"][$urlParameter->getPath()]);
    }
    return $this;
  }


  /**
   * @param EntityInterface $objectSource
   * @param EntityInterface $object
   *
   * @return $this
   * @throws \Exception
   */
  public function duplicateUrlParameter(EntityInterface $objectSource, EntityInterface $object): UrlParameterManagement
  {
    $urlsParameters = $this->getUrlParametersByObject($objectSource);
    $this->addObjectRelationByClassnameAndId($object);
    /** @var UrlParameterInterface $urlParameter */
    foreach($urlsParameters as $urlParameterSource)
    {
      /** @var UrlParameterInterface|EntityInterface $urlParameter */
      $urlParameter = $this->entityManager->duplicate($urlParameterSource);
      $urlParameter->setId(Uuid::uuid4()->toString());
      $urlParameter->setLanguage($this->domains->getCurrentLanguage());

      $uniqueKey = AustralTools::random(4);
      $urlParameter->setPathLast("{$urlParameter->getPathLast()}-copy-{$uniqueKey}")
        ->setObjectRelation("{$object->getClassnameForMapping()}::{$object->getId()}")
        ->setObject($object)
        ->setStatus(UrlParameterInterface::STATUS_UNPUBLISHED)
        ->setKeyLink("{$urlParameter->getClassname()}::{$urlParameter->getId()}");

      if($object instanceof TreePageInterface && $object->getTreePageParent())
      {
        $urlParameterParent = $this->getUrlParameterParent($object->getTreePageParent(), $urlParameter->getDomainId());
        $urlParameter->setPath(($urlParameterParent->getPath() ? "{$urlParameterParent->getPath()}/" : null )."{$urlParameter->getPathLast()}");
      }
      else
      {
        $urlParameter->setPath("{$urlParameter->getPathLast()}");
      }
      $this->entityManager->update($urlParameter, false);
      $this->hydrateUrl($urlParameter);
    }
    return $this;
  }

  /**
   * @param EntityInterface|object $object
   *
   * @return $this
   */
  public function generateUrlParameter(EntityInterface $object): UrlParameterManagement
  {
    if($this->getObjectUrlParameterMapping($object))
    {
      if($object instanceof FilterByDomainInterface && $object->getDomainId())
      {
        $this->generateUrlParameterWithParent($object, $object->getDomainId());
      }
      elseif($this->domains->getEnabledDomainWithoutVirtual() > 0)
      {
        foreach ($this->domains->getDomainsWithoutVirtual() as $domain)
        {
          $this->generateUrlParameterWithParent($object, $domain->getId());
        }
      }
      else
      {
        $this->generateUrlParameterWithParent($object);
      }
      $this->generateChildrenUrlParameters($object);
    }
    return $this;
  }

  /**
   * @param EntityInterface|object $object
   *
   * @return $this
   */
  public function deleteUrlParameter(EntityInterface $object): UrlParameterManagement
  {
    $urlsParameters = $this->getUrlParametersByObject($object);

    /** @var UrlParameterInterface|EntityInterface $urlParameter */
    foreach($urlsParameters as $urlParameter)
    {
      $this->entityManager->delete($urlParameter, false);
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   * @param string $domainId
   *
   * @return UrlParameterInterface
   */
  public function getOrCreateUrlParameterByObject(EntityInterface $object, string $domainId = "current"): UrlParameterInterface
  {
    if(!$urlParameter = $this->getUrlParameterByObject($object, $domainId))
    {
      $urlParameter = $this->createUrlParameter($object, $domainId);
    }
    $urlParameter->setObject($object);
    return $urlParameter;
  }

  /**
   * @param EntityInterface $object
   * @param string $domainId
   *
   * @return UrlParameterInterface
   */
  protected function createUrlParameter(EntityInterface $object, string $domainId = "current"): UrlParameterInterface
  {
    $urlParameter = $this->parameterEntityManager->create();
    $urlParameter->setObjectRelation("{$object->getClassnameForMapping()}::{$object->getId()}");
    $urlParameter->setDomainId($this->getReelDomainId($domainId));
    $urlParameter->setDomain($this->domains->getDomainById($urlParameter->getDomainId()));
    return $urlParameter;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParameterManagement
   */
  public function generatePathWithUrlParameter(UrlParameterInterface $urlParameter): UrlParameterManagement
  {
    if(($object = $urlParameter->getObject()))
    {
      $method = "__toString";
      if($entityMapping = $this->getEntityMappingByObject($object))
      {
        /** @var UrlParameterMapping $urlParameterMapping */
        $urlParameterMapping = $entityMapping->getEntityClassMapping(UrlParameterMapping::class);
        if(method_exists($object, $urlParameterMapping->getMethodGenerateLastPath()))
        {
          $method = $urlParameterMapping->getMethodGenerateLastPath();
        }

        if(!$urlParameter->getKeyLink())
        {
          $urlParameter->setKeyLink("{$urlParameterMapping->getKeyForObjectLink()}::{$object->getId()}");
        }
      }
      if(!$urlParameter->getPathLast())
      {
        $this->urlParameterMigrate->recoveryRefUrlPathValue($urlParameter, $object);
        if(!$urlParameter->getPathLast())
        {
          $urlParameter->setPathLast($object->$method());
        }
      }
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   * @param string $domainId
   *
   * @return UrlParameterInterface
   * @throws \Exception
   */
  protected function generateUrlParameterWithParent(EntityInterface $object, string $domainId = "current"): UrlParameterInterface
  {
    $urlParameter = $this->getOrCreateUrlParameterByObject($object, $domainId);
    $this->generatePathWithUrlParameter($urlParameter);

    $urlParameter->setLanguage($this->domains->getCurrentLanguage());
    if($object instanceof TreePageInterface && $object->getTreePageParent())
    {
      $urlParameterParent = $this->getUrlParameterParent($object->getTreePageParent(), $domainId);
      $urlParameter->setPath(($urlParameterParent->getPath() ? "{$urlParameterParent->getPath()}/" : null )."{$urlParameter->getPathLast()}");
    }
    else
    {
      $urlParameter->setPath("{$urlParameter->getPathLast()}");
    }

    $this->urlParameterMigrate->recoverySeoValues($urlParameter, $object);
    $this->urlParameterMigrate->recoveryRobotValues($urlParameter, $object);
    $this->urlParameterMigrate->recoverySocialValues($urlParameter, $object);

    $this->entityManager->update($urlParameter, false);
    $this->hydrateUrl($urlParameter);
    return $urlParameter;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParameterInterface|null
   */
  public function updateUrlParameterWithParent(UrlParameterInterface $urlParameter): ?UrlParameterInterface
  {
    /** @var EntityInterface $object */
    if($object = $urlParameter->getObject())
    {
      if($object instanceof TreePageInterface && $object->getTreePageParent())
      {
        $urlParameterParent = $this->getUrlParameterByObject($object->getTreePageParent(), $urlParameter->getDomainId());
        $urlParameter->setPath(($urlParameterParent->getPath() ? "{$urlParameterParent->getPath()}/" : null )."{$urlParameter->getPathLast()}");
      }
      else
      {
        $urlParameter->setPath("{$urlParameter->getPathLast()}");
      }
    }
    return $urlParameter;
  }

  /**
   * @param TreePageInterface|EntityInterface $treePageParent
   * @param string $domainId
   *
   * @return UrlParameterInterface|null
   */
  protected function getUrlParameterParent(TreePageInterface $treePageParent, string $domainId = "current"): ?UrlParameterInterface
  {
    if(!$urlParameter = $this->getUrlParameterByObject($treePageParent, $domainId))
    {
      $urlParameter = $this->generateUrlParameterWithParent($treePageParent, $domainId);
    }
    return $urlParameter;
  }

  /**
   * @param EntityInterface $object
   *
   * @return UrlParameterManagement
   */
  protected function generateChildrenUrlParameters(EntityInterface $object): UrlParameterManagement
  {
    if(method_exists($object, "getChildren"))
    {
      foreach($object->getChildren() as $child)
      {
        $this->generateUrlParameter($child);
      }
    }
    if(method_exists($object, "getChildrenEntities"))
    {
      foreach($object->getChildrenEntities() as $child)
      {
        $this->generateUrlParameter($child);
      }
    }
    return $this;
  }

}