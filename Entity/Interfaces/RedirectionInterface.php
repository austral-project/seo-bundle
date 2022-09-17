<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Entity\Interfaces;

/**
 * Austral Interface Redirection.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface RedirectionInterface
{
  /**
   * Get isAutoGenerate
   * @return bool
   */
  public function getIsAutoGenerate(): bool;

  /**
   * Set isAutoGenerate
   *
   * @param bool $isAutoGenerate
   *
   * @return $this
   */
  public function setIsAutoGenerate(bool $isAutoGenerate): RedirectionInterface;

  /**
   * Get isActive
   * @return bool
   */
  public function getIsActive(): bool;

  /**
   * Set isActive
   *
   * @param bool $isActive
   *
   * @return $this
   */
  public function setIsActive(bool $isActive): RedirectionInterface;

  /**
   * Get urlSource
   * @return string|null
   */
  public function getUrlSource(): ?string;

  /**
   * Set urlSource
   *
   * @param string|null $urlSource
   *
   * @return $this
   */
  public function setUrlSource(?string $urlSource): RedirectionInterface;

  /**
   * Get urlDestination
   * @return string|null
   */
  public function getUrlDestination(): ?string;

  /**
   * Set urlDestination
   *
   * @param string|null $urlDestination
   *
   * @return $this
   */
  public function setUrlDestination(?string $urlDestination): RedirectionInterface;

  /**
   * Get relationEntityName
   * @return string|null
   */
  public function getRelationEntityName(): ?string;

  /**
   * Set relationEntityName
   *
   * @param string|null $relationEntityName
   *
   * @return $this
   */
  public function setRelationEntityName(?string $relationEntityName = null): RedirectionInterface;

  /**
   * Get relationEntityId
   * @return string|null
   */
  public function getRelationEntityId(): ?string;

  /**
   * Set relationEntityId
   *
   * @param string|null $relationEntityId
   *
   * @return $this
   */
  public function setRelationEntityId(?string $relationEntityId = null): RedirectionInterface;

  /**
   * Get statusCode
   * @return int
   */
  public function getStatusCode(): int;

  /**
   * Set statusCode
   *
   * @param int $statusCode
   *
   * @return $this
   */
  public function setStatusCode(int $statusCode): RedirectionInterface;

  /**
   * Get language
   * @return string|null
   */
  public function getLanguage(): ?string;

  /**
   * Set language
   *
   * @param string|null $language
   *
   * @return $this
   */
  public function setLanguage(?string $language): RedirectionInterface;

}

    
    
      