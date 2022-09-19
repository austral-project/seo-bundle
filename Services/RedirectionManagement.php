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
use Austral\SeoBundle\Configuration\SeoConfiguration;
use Austral\SeoBundle\Entity\Interfaces\RedirectionInterface;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\EntityManager\RedirectionEntityManager;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral RedirectionManagement service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class RedirectionManagement
{

  /**
   * @var RedirectionEntityManager
   */
  protected RedirectionEntityManager $redirectionManager;

  /**
   * @var SeoConfiguration
   */
  protected SeoConfiguration $SeoConfiguration;

  /**
   * @var array
   */
  protected array $redirectionsUpdate = array();

  /**
   * RedirectionManagement constructor.
   *
   * @param SeoConfiguration $SeoConfiguration
   * @param RedirectionEntityManager $redirectionManager
   */
  public function __construct(SeoConfiguration $SeoConfiguration, RedirectionEntityManager $redirectionManager)
  {
    $this->redirectionManager = $redirectionManager;
    $this->SeoConfiguration = $SeoConfiguration;
  }

  /**
   * @return array
   */
  public function getRedirectionsUpdate(): array
  {
    return $this->redirectionsUpdate;
  }


  /**
   * @param bool $flush
   *
   * @return RedirectionManagement
   */
  public function flush(bool $flush = true): RedirectionManagement
  {
    foreach ($this->redirectionsUpdate as $redirection)
    {
      $this->redirectionManager->update($redirection, false);
    }
    if($flush)
    {
      $this->redirectionManager->flush();
    }
    return $this;
  }

  /**
   * @param UrlParameterInterface|EntityInterface $urlParameter
   * @param string|null $newRefUrl
   * @param string|null $oldRefUrl
   *
   * @return RedirectionManagement
   * @throws NonUniqueResultException
   */
  public function generateRedirectionAuto(UrlParameterInterface $urlParameter, string $newRefUrl = null, string $oldRefUrl = null): RedirectionManagement
  {
    if($this->SeoConfiguration->get('redirection.auto'))
    {
      /** @var RedirectionInterface|null $redirection */
      if($redirectionUrlDestination = $this->redirectionManager->retreiveByUrlDestination($newRefUrl, $urlParameter->getDomainIdReel(), $urlParameter->getLanguage()))
      {
        $redirectionUrlDestination->setIsActive(false);
        $this->redirectionsUpdate[$redirectionUrlDestination->getId()] = $redirectionUrlDestination;
      }

      /** @var RedirectionInterface|null $redirection */
      $redirection = $this->redirectionManager->retreiveByUrlSource($newRefUrl, $urlParameter->getDomainIdReel(), $urlParameter->getLanguage());
      if($redirection && ($redirection->getRelationEntityName() !== $urlParameter->getClassnameForMapping() || $redirection->getRelationEntityId() == $urlParameter->getId()))
      {
        $redirectionOther = $redirection;
        $redirectionOther->setIsActive(false);
        $this->redirectionsUpdate[$redirectionOther->getId()] = $redirectionOther;
        $redirection = null;
      }
      if(!$redirection)
      {
        $redirection = $this->redirectionManager->create();
        $redirection->setIsActive(true);
        $redirection->setIsAutoGenerate(true);
      }
      $redirection->setRelationEntityName($urlParameter->getClassnameForMapping());
      $redirection->setRelationEntityId($urlParameter->getId());
      $redirection->setUrlSource($oldRefUrl);
      $redirection->setUrlDestination($newRefUrl);
      $redirection->setDomainId($urlParameter->getDomainId());
      $redirection->setLanguage($urlParameter->getLanguage());
      $this->redirectionsUpdate[$redirection->getId()] = $redirection;
    }
    return $this;
  }


}