<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Routing;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Austral AustralRouting.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralRouting
{
  /**
   * @var UrlGeneratorInterface
   */
  private UrlGeneratorInterface $generator;

  /**
   * @var UrlParameterManagement
   */
  private UrlParameterManagement $urlParameterManagement;

  /**
   * @var DomainsManagement
   */
  private DomainsManagement $domainsManagement;

  /**
   * UrlParameterMigrate constructor.
   *
   * @param UrlGeneratorInterface $generator
   * @param DomainsManagement $domainsManagement
   * @param UrlParameterManagement $urlParameterManagement
   */
  public function __construct(UrlGeneratorInterface $generator, DomainsManagement $domainsManagement, UrlParameterManagement $urlParameterManagement)
  {
    $this->generator = $generator;
    $this->urlParameterManagement = $urlParameterManagement;
    $this->domainsManagement = $domainsManagement;
  }

  /**
   * @param string $name
   * @param EntityInterface $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $relative
   *
   * @return string|null
   */
  public function getPath(string $name, EntityInterface $object, array $parameters = [], ?string $domainId = "current", bool $relative = false): ?string
  {
    return $this->generate($name, $object, $parameters, $domainId, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
  }

  /**
   * @param string $name
   * @param EntityInterface $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $schemeRelative
   *
   * @return string|null
   */
  public function getUrl(string $name, EntityInterface $object, array $parameters = [], ?string $domainId = "current", bool $schemeRelative = false): ?string
  {
    return $this->generate($name, $object, $parameters, $domainId, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
  }

  /**
   * @param string $name
   * @param EntityInterface $object
   * @param array $parameters
   * @param string|null $domainId
   * @param int $referenceType
   *
   * @return string
   */
  public function generate(string $name, EntityInterface $object, array $parameters = [], ?string $domainId = "current", int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
  {
    if(!$object instanceof UrlParameterInterface)
    {
      $urlParameter = $this->urlParameterManagement->getUrlParametersByObjectAndDomainId($object, $domainId);
    }
    else
    {
      $urlParameter = $object;
    }
    if($urlParameter) {
      /** @var RequestContext $requestContext */
      $requestContext = $this->domainsManagement->getRequestContextByDomainId($urlParameter->getDomainId());
      if($requestContext && $requestContext->getHost() !== $this->generator->getContext()->getHost())
      {
        $this->generator->setContext($requestContext);
      }
      $parameters["slug"] = $urlParameter->getPath();
      return $this->generator->generate($name, $parameters, $referenceType);
    }
    return null;
  }


}