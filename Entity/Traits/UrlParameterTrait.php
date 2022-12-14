<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\SeoBundle\Entity\Traits;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral Seo UrlParameter Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait UrlParameterTrait
{

  /**
   * @var array
   */
  protected array $urlParameters = array();

  /**
   * @param string|null $domainId
   *
   * @return UrlParameter|null
   */
  public function getUrlParameter(string $domainId = DomainsManagement::DOMAIN_ID_MASTER): ?UrlParameter
  {
    if(array_key_exists($domainId, $this->urlParameters))
    {
      return $this->urlParameters[$domainId];
    }
    return AustralTools::first($this->urlParameters);
  }

  /**
   * @return EntityInterface|UrlParameterTrait
   */
  public function addUrlParameter(UrlParameter $urlParameter): EntityInterface
  {
    $this->urlParameters[$urlParameter->getDomainId()] = $urlParameter;
    return $this;
  }

  /**
   * @return array
   */
  public function getUrlParameters(): array
  {
    return $this->urlParameters;
  }

  /**
   * @param array $urlParameters
   *
   * @return EntityInterface|UrlParameterTrait
   */
  public function setUrlParameters(array $urlParameters): EntityInterface
  {
    $this->urlParameters = $urlParameters;
    return $this;
  }

}
