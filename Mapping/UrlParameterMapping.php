<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Mapping;

use Austral\EntityBundle\Mapping\EntityClassMapping;

/**
 * Austral UrlParameterMapping.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
final Class UrlParameterMapping extends EntityClassMapping
{

  /**
   * @var string|null
   */
  protected ?string $methodGenerateLastPath = null;

  /**
   * @var string|null
   */
  protected ?string $methodUrlName = null;

  /**
   * @var string|null
   */
  protected ?string $keyForObjectLink = null;

  /**
   * Constructor.
   */
  public function __construct()
  {
  }

  /**
   * @return string|null
   */
  public function getMethodGenerateLastPath(): ?string
  {
    return $this->methodGenerateLastPath;
  }

  /**
   * @param string|null $methodGenerateLastPath
   *
   * @return UrlParameterMapping
   */
  public function setMethodGenerateLastPath(?string $methodGenerateLastPath): UrlParameterMapping
  {
    $this->methodGenerateLastPath = $methodGenerateLastPath;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getMethodUrlName(): ?string
  {
    return $this->methodUrlName;
  }

  /**
   * @param string|null $methodUrlName
   *
   * @return $this
   */
  public function setMethodUrlName(?string $methodUrlName): UrlParameterMapping
  {
    $this->methodUrlName = $methodUrlName;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getKeyForObjectLink(): ?string
  {
    return $this->keyForObjectLink;
  }

  /**
   * @param string|null $keyForObjectLink
   *
   * @return UrlParameterMapping
   */
  public function setKeyForObjectLink(?string $keyForObjectLink): UrlParameterMapping
  {
    $this->keyForObjectLink = $keyForObjectLink;
    return $this;
  }

}
