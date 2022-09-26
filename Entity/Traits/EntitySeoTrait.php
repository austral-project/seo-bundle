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

use Austral\EntityBundle\Entity\Interfaces\SeoInterface;
use Austral\ToolsBundle\AustralTools;

use Doctrine\ORM\Mapping as ORM;

/**
 * Austral Entity Seo Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @deprecated
 */
trait EntitySeoTrait
{

  /**
   * @var string|null
   * @ORM\Column(name="ref_title", type="string", length=255, nullable=true)
   */
  protected ?string $refTitle = null;

  /**
   * @var string|null
   * @ORM\Column(name="ref_description", type="text", nullable=true)
   */
  protected ?string $refDescription = null;

  /**
   * @var string|null
   * @ORM\Column(name="ref_url", type="string", length=255, nullable=true)
   */
  protected ?string $refUrl = null;

  /**
   * @var string|null
   * @ORM\Column(name="ref_url_last", type="string", length=255, nullable=true)
   */
  protected ?string $refUrlLast = null;

  /**
   * @var string|null
   * @ORM\Column(name="canonical", type="string", length=255, nullable=true)
   */
  protected ?string $canonical = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="homepage_id", type="string", length=255, nullable=true )
   */
  protected ?string $homepageId = null;

  /**
   * @var string|null
   */
  protected ?string $bodyClass = null;

  /**
   * Set refTitle
   *
   * @param string|null $refTitle
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setRefTitle(?string $refTitle): SeoInterface
  {
    $this->refTitle = $refTitle;
    return $this;
  }

  /**
   * Get refTitle
   *
   * @return string|null
   */
  public function getRefTitle(): ?string
  {
    return $this->refTitle;
  }
  
  /**
   * Set refUrl
   *
   * @param string|null $refUrl
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setRefUrl(?string $refUrl): SeoInterface
  {
    $this->refUrl = $refUrl;
    return $this;
  }

  /**
   * Get refUrl
   *
   * @return string|null
   */
  public function getRefUrl(): ?string
  {
    return $this->refUrl;
  }

  /**
   * Set refUrlLast
   *
   * @param string|null $refUrlLast
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setRefUrlLast(?string $refUrlLast): SeoInterface
  {
    $this->refUrlLast = AustralTools::slugger($refUrlLast, true, true);
    return $this;
  }

  /**
   * Get refUrlLast
   *
   * @return string|null
   */
  public function getRefUrlLast(): ?string
  {
    return $this->refUrlLast;
  }

  /**
   * Set refDescription
   *
   * @param string|null $refDescription
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setRefDescription(?string $refDescription): SeoInterface
  {
    $this->refDescription = $refDescription;
    return $this;
  }

  /**
   * Get refDescription
   *
   * @return string|null
   */
  public function getRefDescription(): ?string
  {
    return $this->refDescription;
  }
  
  /**
   * Set canonical
   *
   * @param string|null $canonical
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setCanonical(?string $canonical): SeoInterface
  {
    $this->canonical = $canonical;
    return $this;
  }

  /**
   * Get canonical
   *
   * @return string|null
   */
  public function getCanonical(): ?string
  {
    return $this->canonical;
  }

  /**
   * @return string
   * @throws \Exception
   */
  public function getBaseUrl(): string
  {
    if($this->getRefUrl())
    {
      $url = str_replace($this->getRefUrlLast(), "",  $this->getRefUrl());
      $urls = explode("/", $url);
      if(count($urls) > 1)
      {
        return sprintf('/%s/', trim(implode("/", $urls), "/"));
      }
      return "/";
    }
    else
    {
      return $this->getPageParent() ? sprintf('/%s/', trim($this->getPageParent()->getRefUrl(), "/")) : "/";
    }
  }

  /**
   * @return SeoInterface|EntitySeoTrait
   */
  public function getHomepage(): ?SeoInterface
  {
    if(method_exists($this, "getIsHomepage") && $this->getIsHomepage())
    {
      return $this;
    }
    return $this->getPageParent() ? $this->getPageParent()->getHomepage() : null;
  }

  /**
   * @return string|null
   */
  public function getBodyClass(): ?string
  {
    return $this->bodyClass;
  }

  /**
   * @param string|null $bodyClass
   *
   * @return SeoInterface|EntitySeoTrait
   */
  public function setBodyClass(?string $bodyClass): SeoInterface
  {
    $this->bodyClass = $bodyClass;
    return $this;
  }

}
