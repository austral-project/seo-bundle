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
use Austral\HttpBundle\Configuration\HttpConfiguration;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Austral AustralRouting.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralRouting
{
  /**
   * @var RouterInterface
   */
  private RouterInterface $router;

  /**
   * @var UrlParameterManagement
   */
  private UrlParameterManagement $urlParameterManagement;

  /**
   * @var DomainsManagement
   */
  private DomainsManagement $domainsManagement;

  /**
   * @var HttpConfiguration
   */
  private HttpConfiguration $httpConfiguration;

  /**
   * UrlParameterMigrate constructor.
   *
   * @param RouterInterface $router
   * @param DomainsManagement $domainsManagement
   * @param UrlParameterManagement $urlParameterManagement
   * @param HttpConfiguration $httpConfiguration
   */
  public function __construct(RouterInterface $router, DomainsManagement $domainsManagement, UrlParameterManagement $urlParameterManagement, HttpConfiguration $httpConfiguration)
  {
    $this->router = $router;
    $this->urlParameterManagement = $urlParameterManagement;
    $this->domainsManagement = $domainsManagement;
    $this->httpConfiguration = $httpConfiguration;
  }

  /**
   * @param string $route
   * @param EntityInterface|null $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $relative
   *
   * @return string|null
   */
  public function getPath(string $route, ?EntityInterface $object = null, array $parameters = [], ?string $domainId = "current", bool $relative = false): ?string
  {
    return $this->generate($route, $object, $parameters, $domainId, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
  }

  /**
   * @param string $route
   * @param EntityInterface|null $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $schemeRelative
   *
   * @return string|null
   */
  public function getUrl(string $route, ?EntityInterface $object = null, array $parameters = [], ?string $domainId = "current", bool $schemeRelative = false): ?string
  {
    return $this->generate($route, $object, $parameters, $domainId, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
  }

  /**
   * @param string $routeName
   * @param EntityInterface|null $object
   * @param array $parameters
   * @param string|null $domainId
   * @param int $referenceType
   *
   * @return string|null
   */
  public function generate(string $routeName, ?EntityInterface $object = null, array $parameters = [], ?string $domainId = "current", int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
  {
    $slugIsRequired = false;
    if($route = $this->router->getRouteCollection()->get($routeName))
    {
      $slugIsRequired = $route->hasRequirement("slug");
    }

    if($object instanceof UrlParameterInterface)
    {
      $domainId = $domainId === "current" ? $object->getDomainId() : $domainId;
    }
    $domain = $domainId === "current" ? $this->domainsManagement->getCurrentDomain() : $this->domainsManagement->getDomainById($domainId);
    $domainId = $domain->getId();
    if($object)
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
        if($slugIsRequired) {
          $parameters["slug"] = $urlParameter->getPath();
        }
      }
    }

    $domainContext = $domain->getDomainByEnv($this->httpConfiguration->get("env.current"));
    /** @var RequestContext $requestContext */
    $requestContext = $this->domainsManagement->getRequestContextByDomainId($domainContext->getId(), false);
    if($requestContext && $requestContext->getHost() !== $this->router->getContext()->getHost())
    {
      $this->router->setContext($requestContext);
    }
    if($slugIsRequired && (!array_key_exists("slug", $parameters) || !$parameters["slug"])) {
      return "";
    }

    if($route = $this->router->getRouteCollection()->get($routeName))
    {
      if(str_contains($route->getPath(), "{_locale}"))
      {
        $parameters['_locale'] = array_key_exists("_locale", $parameters) ? $parameters['_locale'] : $this->domainsManagement->getHttpRequest()->getLanguage();
      }
    }

    return $this->router->generate($routeName, $parameters, $referenceType);
  }


}