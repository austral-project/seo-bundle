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
use Austral\HttpBundle\Mapping\DomainFilterMapping;
use Austral\EntityBundle\EntityManager\EntityManagerORMInterface;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Event\UrlParameterEvent;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Model\UrlParametersByDomain;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Services\Debug;
use Doctrine\ORM\QueryBuilder;
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
  protected DomainsManagement $domainsManagement;

  /**
   * @var EntityManagerORMInterface
   */
  protected EntityManagerORMInterface $entityManager;

  /**
   * @var UrlParameterEntityManager
   */
  protected UrlParameterEntityManager $urlParameterEntityManager;

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * @var UrlParameterMigrate
   */
  protected UrlParameterMigrate $urlParameterMigrate;

  /**
   * @var Debug
   */
  protected Debug $debug;

  /**
   * @var string
   */
  private string $debugContainer;

  /**
   * @var array
   */
  protected array $urlParametersByDomains = array();

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
  protected array $domainIdByUrlParameterId = array();

  /**
   * @var UrlParametersByDomain|null
   */
  protected ?UrlParametersByDomain $urlParametersByDomainsForAll = null;


  /**
   * @param Mapping $mapping
   * @param EventDispatcherInterface $dispatcher
   * @param EntityManagerORMInterface $entityManager
   * @param UrlParameterEntityManager $urlParameterEntityManager
   * @param DomainsManagement $domainsManagement
   * @param UrlParameterMigrate $urlParameterMigrate
   * @param Debug|null $debug
   */
  public function __construct(Mapping $mapping,
    EventDispatcherInterface $dispatcher,
    EntityManagerORMInterface $entityManager,
    UrlParameterEntityManager $urlParameterEntityManager,
    DomainsManagement $domainsManagement,
    UrlParameterMigrate $urlParameterMigrate,
    Debug $debug
  )
  {
    $this->mapping = $mapping;
    $this->dispatcher = $dispatcher;
    $this->entityManager = $entityManager;
    $this->urlParameterEntityManager = $urlParameterEntityManager;
    $this->domainsManagement = $domainsManagement;
    $this->urlParameterMigrate = $urlParameterMigrate;
    $this->debug = $debug;
    $this->debugContainer = self::class;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function refresh(): UrlParameterManagement
  {
    $this->initialize(true);
    return $this;
  }

  /**
   * @param bool $reload
   *
   * @return $this
   * @throws \Exception
   */
  public function initialize(bool $reload = false): UrlParameterManagement
  {
    $this->debug->stopWatchStart("austral.url_parameter_management.initialize", $this->debugContainer);

    $entitiesMappingForAllDomain = array();
    $urlPathWithLastPathForAllDomain = array();
    $keysForObjectLinkForAllDomain = array();
    $objectsForAllDomain = array();
    if((count($this->entitiesMapping) == 0) || $reload)
    {
      /** @var EntityMapping $entityMapping */
      foreach($this->mapping->getEntitiesMapping() as $entityMapping)
      {
        /** @var UrlParameterMapping $urlParameterMapping */
        if($urlParameterMapping = $entityMapping->getEntityClassMapping(UrlParameterMapping::class))
        {
          $this->entitiesMapping[$entityMapping->entityClass] = $entityMapping;
          $this->keysForObjectLink[$urlParameterMapping->getKeyForObjectLink()] = $entityMapping->entityClass;

          /** @var DomainFilterMapping $domainFilterMapping */
          if($domainFilterMapping = $entityMapping->getEntityClassMapping(DomainFilterMapping::class))
          {
            if($domainFilterMapping->getForAllDomainEnabled() && $this->domainsManagement->getEnabledDomainWithoutVirtual())
            {
              $entitiesMappingForAllDomain[$entityMapping->entityClass] = $entityMapping;
              $keysForObjectLinkForAllDomain[$urlParameterMapping->getKeyForObjectLink()] = $entityMapping->entityClass;
              if(!$domainFilterMapping->getAutoDomainId())
              {
                $urlPathWithLastPathForAllDomain[] = $entityMapping->entityClass;
              }
              else
              {
                $objects = $this->entityManager->getRepository($entityMapping->entityClass)
                  ->selectByClosure(function(QueryBuilder $queryBuilder) {
                    $queryBuilder->where("root.domainId = :domainId")
                      ->setParameter("domainId", DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS);
                });
                /** @var EntityInterface $object */
                foreach($objects as $object)
                {
                  $objectsForAllDomain["{$object->getClassnameForMapping()}::{$object->getId()}"] = $object;
                }
              }
            }
          }
        }
      }
    }

    $this->debug->stopWatchStop("austral.url_parameter_management.initialize");
    $urlParametersEntityByDomain = array();

    /** @var DomainInterface $domain */
    foreach($this->domainsManagement->getDomainsWithoutVirtual() as $domain)
    {
      $urlParametersEntityByDomain[$domain->getId()] = array();
    }
    if($this->domainsManagement->getEnabledDomainWithoutVirtual())
    {
      $urlParametersEntityByDomain[DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS] = array();
    }

    // TODO Check for 1 domain with multi language
    $urlsParametersAll = $this->entityManager->getRepository(UrlParameterInterface::class)->selectUrlsParameters($this->domainsManagement->getCurrentLanguage());
    /** @var UrlParameterInterface $urlParameter */
    foreach ($urlsParametersAll as $urlParameter)
    {
      $urlParametersEntityByDomain[$urlParameter->getDomainId()][$urlParameter->getId()] = $urlParameter;

      if($this->domainsManagement->getEnabledDomainWithoutVirtual())
      {
        if(array_key_exists($urlParameter->getObjectClass(), $entitiesMappingForAllDomain))
        {
          $path = null;
          if(in_array($urlParameter->getObjectClass(), $urlPathWithLastPathForAllDomain))
          {
            $path = $urlParameter->getPathLast();
          }
          elseif(array_key_exists($urlParameter->getObjectRelation(), $objectsForAllDomain))
          {
            $path = $urlParameter->getPath();
          }
          if($path && !array_key_exists($urlParameter->getPath(), $urlParametersEntityByDomain[DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS]))
          {
            $urlParameterForAllDomain = $this->urlParameterEntityManager->create();
            $urlParameterForAllDomain->setId("{$urlParameter->getObjectClassShort()}_{$urlParameter->getObjectId()}");
            $urlParameterForAllDomain->setIsVirtual(true);
            $urlParameterForAllDomain->setName($urlParameter->getName());
            $urlParameterForAllDomain->setPath($path);
            $urlParameterForAllDomain->setPathLast($path);
            $urlParameterForAllDomain->setObjectRelation($urlParameter->getObjectRelation());
            $urlParametersEntityByDomain[DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS][$urlParameterForAllDomain->getId()] = $urlParameterForAllDomain;
          }
        }
      }
    }

    $urlParameterEvent = new UrlParameterEvent($this);
    $this->dispatcher->dispatch($urlParameterEvent, UrlParameterEvent::EVENT_START);

    /** @var DomainInterface $domain */
    foreach($this->domainsManagement->getDomainsWithoutVirtual() as $domain)
    {
      if((!array_key_exists($domain->getId(), $this->urlParametersByDomains) && $domain->getId() !== DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS) || $reload)
      {
        $this->addUrlParametersByDomain($domain, $urlParametersEntityByDomain);
      }
    }

    if($this->domainsManagement->getEnabledDomainWithoutVirtual())
    {
      $urlParametersByDomain = (new UrlParametersByDomain(
        $this->dispatcher,
        $this->domainsManagement->getDomainForAll(),
        $this->entityManager,
        $this->urlParameterEntityManager,
        $this->urlParameterMigrate,
        $this->domainsManagement->getCurrentLanguage(),
        $entitiesMappingForAllDomain,
        $keysForObjectLinkForAllDomain
      ))->build($urlParametersEntityByDomain[DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS]);
      $this->urlParametersByDomainsForAll = $urlParametersByDomain;
    }
    $this->dispatcher->dispatch($urlParameterEvent, UrlParameterEvent::EVENT_END);
    return $this;
  }

  /**
   * @param DomainInterface $domain
   * @param array $urlParametersEntityByDomain
   *
   * @return $this
   */
  public function addUrlParametersByDomain(DomainInterface $domain, array $urlParametersEntityByDomain = array()): UrlParameterManagement
  {
    $this->debug->stopWatchStart("austral.url_parameter.build", $this->debugContainer);
    $urlParametersByDomain = (new UrlParametersByDomain(
      $this->dispatcher,
      $domain,
      $this->entityManager,
      $this->urlParameterEntityManager,
      $this->urlParameterMigrate,
      $this->domainsManagement->getCurrentLanguage(),
      $this->entitiesMapping,
      $this->keysForObjectLink
    ))->build(AustralTools::getValueByKey($urlParametersEntityByDomain, $domain->getId(), array()));
    $this->urlParametersByDomains[$domain->getId()] = $urlParametersByDomain;
    $this->domainIdByUrlParameterId = array_merge($this->domainIdByUrlParameterId, $urlParametersByDomain->getDomainIdByUrlParameterId());
    $this->debug->stopWatchStop("austral.url_parameter.build");
    return $this;
  }

  /**
   * @return array
   */
  public function getUrlParametersByDomainsWithTree(): array
  {
    $urlParametersByDomainsWithTree = array();
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach($this->urlParametersByDomains as $urlParametersByDomain)
    {
      $urlParametersByDomainsWithTree[] = array(
        "domain"  =>  $urlParametersByDomain->getDomain(),
        "urls"    =>  $urlParametersByDomain->getTreeUrlParameters()
      );
    }
    if($this->domainsManagement->getEnabledDomainWithoutVirtual())
    {
      $urlParametersByDomainsWithTree[] = array(
        "domain"  =>  $this->urlParametersByDomainsForAll->getDomain(),
        "urls"    =>  $this->urlParametersByDomainsForAll->getTreeUrlParameters()
      );
    }
    return $urlParametersByDomainsWithTree;
  }

  /**
   * @param string $classname
   *
   * @return bool
   */
  public function hasEntityMappingByObjectClassname(string $classname): bool
  {
    return array_key_exists($classname, $this->entitiesMapping);
  }

  /**
   * @param string|null $domainIdOrKey
   *
   * @return string|null
   */
  protected function getReelDomainId(?string $domainIdOrKey = DomainsManagement::DOMAIN_ID_MASTER): ?string
  {
    return $this->domainsManagement->getReelDomainId($domainIdOrKey);
  }

  /**
   * @return array
   */
  public function getNameByKeyLinks(): array
  {
    $nameByKeysLinks = [];
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach($this->getUrlParametersByDomains() as $urlParametersByDomain)
    {
      $nameByKeysLinks = array_merge($nameByKeysLinks, $urlParametersByDomain->getNameByKeyLinks());
    }
    return $nameByKeysLinks;
  }

  /**
   * @param string $classname
   *
   * @return string
   */
  protected function getObjectReelClassname(string $classname): string
  {
    return array_key_exists($classname, $this->keysForObjectLink) ? $this->keysForObjectLink[$classname] : $classname;
  }

  /**
   * @param string $objectClassname
   * @param string $objectId
   *
   * @return UrlParameterInterface|null
   */
  public function getUrlParameterByObjectClassnameAndId(string $objectClassname, string $objectId): ?UrlParameterInterface
  {
    $urlParameter = null;
    $objectClassname = $this->getObjectReelClassname($objectClassname);
    if($objectClassname === "UrlParameter" || AustralTools::usedClass($objectClassname, UrlParameterInterface::class))
    {
      $domainId = array_key_exists($objectId, $this->domainIdByUrlParameterId) ? $this->domainIdByUrlParameterId[$objectId] : null;
      if($domainId && ($urlParametersByDomain = $this->getUrlParametersByDomain($domainId)))
      {
        $urlParameter = $urlParametersByDomain->getUrlParameterById($objectId);
      }
    }
    else
    {
      if($urlParametersByDomain = $this->getUrlParametersByDomain($this->domainsManagement->getCurrentDomain()->getId()))
      {
        /** @var UrlParameterInterface $urlParameter */
        $urlParameter = $urlParametersByDomain->getUrlParameterByObjectClassnameAndId($objectClassname, $objectId);
      }
      if(!$urlParameter) {
        /** @var UrlParametersByDomain $urlParametersByDomain */
        foreach ($this->getUrlParametersByDomains() as $urlParametersByDomain)
        {
          if($urlParameter = $urlParametersByDomain->getUrlParameterByObjectClassnameAndId($objectClassname, $objectId))
          {
            break;
          }
        }
      }
    }
    return $urlParameter;
  }

  /**
   * @param EntityInterface $object
   *
   * @return array
   * @throws \Exception
   */
  public function getUrlParametersByObject(EntityInterface $object): array
  {
    $urlParameters = [];
    /** @var DomainFilterMapping $domainFilterMapping */
    if($domainFilterMapping = $this->mapping->getEntityClassMapping($object->getClassnameForMapping(), DomainFilterMapping::class))
    {
      if($domainFilterMapping->getAutoDomainId())
      {
        if(!$domainFilterMapping->getForAllDomainEnabled() || $domainFilterMapping->getObjectValue($object, "domainId") !== DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS)
        {
          $urlParametersByDomain = $this->getUrlParametersByDomain($domainFilterMapping->getObjectValue($object, "domainId"));
          if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
          {
            $urlParameter = $urlParametersByDomain->recoveryOrCreateUrlParameterByObject($object);
            $urlParameters[] = $urlParameter;
          }
        }
        else
        {
          /** @var UrlParametersByDomain $urlParametersByDomain */
          foreach($this->urlParametersByDomains as $urlParametersByDomain)
          {
            if(!$urlParametersByDomain->getIsVirtual())
            {
              $urlParameter = $urlParametersByDomain->recoveryOrCreateUrlParameterByObject($object);
              $urlParameters[] = $urlParameter;
            }
          }
        }
      }
      elseif(!$domainFilterMapping->getForAllDomainEnabled())
      {
        foreach($domainFilterMapping->getObjectValue($object, "domainIds") as $domainId)
        {
          $urlParametersByDomain = $this->getUrlParametersByDomain($domainId);
          if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
          {
            $urlParameter = $urlParametersByDomain->recoveryOrCreateUrlParameterByObject($object);
            $urlParameters[] = $urlParameter;
          }
        }
        /** @var UrlParametersByDomain $urlParametersByDomain */
        foreach ($this->getUrlParametersByDomains() as $urlParametersByDomain)
        {
          if(!$urlParametersByDomain->getIsVirtual() && !$urlParametersByDomain->checkDomainIds($object))
          {
            if($urlParameter = $urlParametersByDomain->getUrlParameterByObject($object))
            {
              $urlParametersByDomain->deleteUrlParameter($urlParameter);
            }
          }
        }
      }
      else
      {
        /** @var UrlParametersByDomain $urlParametersByDomain */
        foreach ($this->getUrlParametersByDomains() as $urlParametersByDomain)
        {
          if(!$urlParametersByDomain->getIsVirtual())
          {
            $urlParameter = $urlParametersByDomain->recoveryOrCreateUrlParameterByObject($object);
            $urlParameters[] = $urlParameter;
          }
        }
      }
    }
    else
    {
      $urlParametersByDomain = $this->getUrlParametersByDomain();
      if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
      {
        $urlParameter = $urlParametersByDomain->recoveryOrCreateUrlParameterByObject($object);
        $urlParameters[] = $urlParameter;
      }
    }
    return $urlParameters;
  }


  /**
   * @param EntityInterface $object
   * @param string|null $domainIdOrKey
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParametersByObjectAndDomainId(EntityInterface $object, ?string $domainIdOrKey = DomainsManagement::DOMAIN_ID_MASTER): ?UrlParameterInterface
  {
    /** @var UrlParametersByDomain $urlParametersByDomain */
    if($urlParametersByDomain = $this->getUrlParametersByDomain($domainIdOrKey))
    {
      return $urlParametersByDomain->getUrlParameterByObject($object);
    }
    return null;
  }

  /**
   * @return array
   */
  public function getUrlParametersByDomains(): array
  {
    return $this->urlParametersByDomains;
  }

  /**
   * @param string|null $domainIdOrKey
   *
   * @return UrlParametersByDomain|null
   */
  public function getUrlParametersByDomain(?string $domainIdOrKey = DomainsManagement::DOMAIN_ID_MASTER): ?UrlParametersByDomain
  {
    return AustralTools::getValueByKey($this->urlParametersByDomains, $this->getReelDomainId($domainIdOrKey), null);
  }

  /**
   * @param string|null $domainIdOrKey
   * @param string|null $slug
   * @param bool $objectInit
   *
   * @return UrlParameterInterface|null
   */
  public function retreiveUrlParameterByDomainIdAndSlug(?string $domainIdOrKey = DomainsManagement::DOMAIN_ID_MASTER, ?string $slug = null, bool $objectInit = false): ?UrlParameterInterface
  {
    /** @var UrlParametersByDomain $urlParametersByDomain */
    if($urlParametersByDomain = $this->getUrlParametersByDomain($domainIdOrKey))
    {
      return $urlParametersByDomain->getUrlParameterByPath($slug ?? "", $objectInit);
    }
    return null;
  }

  /**
   * @param EntityInterface $object
   * @param string|null $domainIdOrKey
   *
   * @return UrlParameterInterface|null
   */
  public function retreiveUrlParameterByObject(EntityInterface $object, ?string $domainIdOrKey = DomainsManagement::DOMAIN_ID_MASTER): ?UrlParameterInterface
  {
    /** @var UrlParametersByDomain $urlParametersByDomain */
    if($urlParametersByDomain = $this->getUrlParametersByDomain($domainIdOrKey))
    {
      return $urlParametersByDomain->getUrlParameterByObject($object, false);
    }
    return null;
  }

  /**
   * @param string $status
   *
   * @return int
   */
  public function getTotalUrlsByStatus(string $status): int
  {
    $count = 0;
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach ($this->urlParametersByDomains as $urlParametersByDomain)
    {
      if(!$urlParametersByDomain->getIsVirtual())
      {
        $count += $urlParametersByDomain->getNbUrlParametersByStatus($status);
      }
    }
    return $count;
  }

  /**
   * @return int
   */
  public function countUrlParametersConflict(): int
  {
    $count = 0;
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach ($this->urlParametersByDomains as $urlParametersByDomain)
    {
      $count += count($urlParametersByDomain->getUrlParametersConflict());
    }
    return $count;
  }

  /**
   * @param string $domainId
   * @param string $status
   *
   * @return int|null
   */
  public function getNbUrlsStatusByDomainAndStatus(string $domainId, string $status): ?int
  {
    if($urlParametersByDomain = $this->getUrlParametersByDomain($domainId))
    {
      return $urlParametersByDomain->getNbUrlParametersByStatus($status);
    }
    return 0;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return $this
   */
  public function remove(UrlParameterInterface $urlParameter): UrlParameterManagement
  {
    if($urlParametersByDomain = $this->getUrlParametersByDomain($urlParameter->getDomainId()))
    {
      $urlParametersByDomain->remove($urlParameter);
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function deleteUrlParameterByObject(EntityInterface $object): UrlParameterManagement
  {
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach($this->urlParametersByDomains as $urlParametersByDomain)
    {
      $urlParametersByDomain->deleteUrlParameterByObject($object);
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
  public function duplicateUrlParameterByObject(EntityInterface $objectSource, EntityInterface $object): UrlParameterManagement
  {
    $urlParametersDuplicate = array();
    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach($this->urlParametersByDomains as $urlParametersByDomain)
    {
      if($urlParameterDuplicate = $urlParametersByDomain->duplicateUrlParameterByObject($objectSource, $object))
      {
        $urlParametersDuplicate[] = $urlParameterDuplicate;
      }
    }
    foreach ($urlParametersDuplicate as $urlParameterDuplicate)
    {
      /** @var UrlParametersByDomain $urlParametersByDomain */
      if($urlParametersByDomain = $this->getUrlParametersByDomain($urlParameterDuplicate->getDomainId()))
      {
        $urlParametersByDomain->hydrate($urlParameterDuplicate);
      }
    }
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParameterInterface|null
   */
  public function updateUrlParameter(UrlParameterInterface $urlParameter): ?UrlParameterInterface
  {
    /** @var UrlParametersByDomain $urlParametersByDomain */
    if($urlParametersByDomain = $this->getUrlParametersByDomain($urlParameter->getDomainId()))
    {
      $urlParametersByDomain->updateUrlParameter($urlParameter);
    }
    return $urlParameter;
  }

  /**
   * @return $this
   * @throws \Doctrine\ORM\Query\QueryException
   */
  public function hydrateObjects(): UrlParameterManagement
  {
    $objectsByEntityClass = array();
    /** @var EntityMapping $entityMapping */
    foreach($this->entitiesMapping as $entityMapping)
    {
      if($entityMapping->getEntityClassMapping(UrlParameterMapping::class))
      {
        $this->entitiesMapping[$entityMapping->entityClass] = $entityMapping;

        $repository = $this->entityManager->getRepository($entityMapping->entityClass);
        $queryBuilder = $repository->createQueryBuilder("root");
        $queryBuilder->indexBy("root", "root.id");
        if($entityMapping->hasEntityClassMapping("Austral\EntityTranslateBundle\Mapping\EntityTranslateMapping"))
        {
          $queryBuilder->leftJoin("root.translates", "translates")->addSelect("translates");
        }
        $objectsByEntityClass[$entityMapping->entityClass] = $repository->selectByQueryBuilder($queryBuilder);
      }
    }

    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach ($this->urlParametersByDomains as $urlParametersByDomain)
    {
      if(!$urlParametersByDomain->getIsVirtual())
      {
        $urlParametersByDomain->hydrateObjects($objectsByEntityClass);
      }
    }
    return $this;
  }

  /**
   * @return void
   * @throws \Exception
   */
  public function generateAllUrlParameters(?string $domainId = null)
  {
    $this->hydrateObjects();

    /** @var UrlParametersByDomain $urlParametersByDomain */
    foreach ($this->urlParametersByDomains as $urlParametersByDomain)
    {
      if(!$urlParametersByDomain->getIsVirtual() && (!$domainId || $domainId === $urlParametersByDomain->getDomain()->getId()))
      {
        $urlParametersByDomain->generateAllUrlParameters();
      }
    }
    $this->entityManager->flush();
  }

  /**
   * @param EntityInterface $object
   *
   * @return $this
   * @throws \Exception
   */
  public function generateUrlParameter(EntityInterface $object): UrlParameterManagement
  {
    /** @var DomainFilterMapping $domainFilterMapping */
    if($domainFilterMapping = $this->mapping->getEntityClassMapping($object->getClassnameForMapping(), DomainFilterMapping::class))
    {
      if($domainFilterMapping->getAutoDomainId())
      {
        if(!$domainFilterMapping->getForAllDomainEnabled() || $domainFilterMapping->getObjectValue($object, "domainId") !== DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS)
        {
          $urlParametersByDomain = $this->getUrlParametersByDomain($domainFilterMapping->getObjectValue($object, "domainId"));
          if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
          {
            $urlParametersByDomain->generateUrlParameter($object);
            $this->recoveryValuesAustral30($urlParametersByDomain, $object);
          }
        }
        else
        {
          /** @var UrlParametersByDomain $urlParametersByDomain */
          foreach($this->urlParametersByDomains as $urlParametersByDomain)
          {
            if(!$urlParametersByDomain->getIsVirtual())
            {
              $urlParametersByDomain->generateUrlParameter($object);
              $this->recoveryValuesAustral30($urlParametersByDomain, $object);
            }
          }
        }
      }
      elseif(!$domainFilterMapping->getForAllDomainEnabled())
      {
        foreach($domainFilterMapping->getObjectValue($object, "domainIds") as $domainId)
        {
          $urlParametersByDomain = $this->getUrlParametersByDomain($domainId);
          if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
          {
            $urlParametersByDomain->generateUrlParameter($object);
            $this->recoveryValuesAustral30($urlParametersByDomain, $object);
          }
        }
      }
      else
      {
        /** @var UrlParametersByDomain $urlParametersByDomain */
        foreach($this->urlParametersByDomains as $urlParametersByDomain)
        {
          if(!$urlParametersByDomain->getIsVirtual())
          {
            $urlParametersByDomain->generateUrlParameter($object);
            $this->recoveryValuesAustral30($urlParametersByDomain, $object);
          }
        }
      }
    }
    else
    {
      $urlParametersByDomain = $this->getUrlParametersByDomain();
      if($urlParametersByDomain && !$urlParametersByDomain->getIsVirtual())
      {
        $urlParametersByDomain->generateUrlParameter($object);
        $this->recoveryValuesAustral30($urlParametersByDomain, $object);
      }
    }
    return $this;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  protected function recoveryValuesAustral30(UrlParametersByDomain $urlParametersByDomain, EntityInterface $object): UrlParameterManagement
  {
    if($urlParameter = $urlParametersByDomain->getUrlParameterByObject($object))
    {
      $this->urlParameterMigrate->recoverySeoValues($urlParameter, $object);
      $this->urlParameterMigrate->recoveryRobotValues($urlParameter, $object);
      $this->urlParameterMigrate->recoverySocialValues($urlParameter, $object);
    }
    return $this;
  }


}