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

use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral UrlParameter Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface UrlParameterInterface
{

  const STATUS_PUBLISHED = "published";
  const STATUS_UNPUBLISHED = "unpublished";
  const STATUS_DRAFT = "draft";

  const TYPE_OBJECT = "object";
  const TYPE_ACTION = "action";

  const SECURITY_USER = "user";
  const SECURITY_PASSWORD = "password";

  /**
   * @return string|null
   */
  public function getPath(): ?string;

  /**
   * @param string|null $path
   *
   * @return UrlParameterInterface
   */
  public function setPath(?string $path): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getPathLast(): ?string;

  /**
   * @param string|null $pathLast
   *
   * @return UrlParameterInterface
   */
  public function setPathLast(?string $pathLast): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSeoTitle(): ?string;

  /**
   * @param string|null $seoTitle
   *
   * @return UrlParameterInterface
   */
  public function setSeoTitle(?string $seoTitle): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSeoDescription(): ?string;

  /**
   * @param string|null $seoDescription
   *
   * @return UrlParameterInterface
   */
  public function setSeoDescription(?string $seoDescription): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSeoCanonical(): ?string;

  /**
   * @param string|null $seoCanonical
   *
   * @return UrlParameterInterface
   */
  public function setSeoCanonical(?string $seoCanonical): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSocialTitle(): ?string;

  /**
   * @param string|null $socialTitle
   *
   * @return UrlParameterInterface
   */
  public function setSocialTitle(?string $socialTitle): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSocialDescription(): ?string;

  /**
   * @param string|null $socialDescription
   *
   * @return UrlParameterInterface
   */
  public function setSocialDescription(?string $socialDescription): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSocialImage(): ?string;

  /**
   * @param string|null $socialImage
   *
   * @return UrlParameterInterface
   */
  public function setSocialImage(?string $socialImage): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getObjectRelation(): ?string;

  /**
   * @param string|null $objectRelation
   *
   * @return UrlParameterInterface
   */
  public function setObjectRelation(?string $objectRelation): UrlParameterInterface;

  /**
   * @return string
   */
  public function getObjectClass(): string;

  /**
   * @return string|int
   */
  public function getObjectId();

  /**
   * @return string|null
   */
  public function getDomainId(): ?string;

  /**
   * @return string|null
   */
  public function getActionRelation(): ?string;

  /**
   * @param string|null $actionRelation
   *
   * @return UrlParameterInterface
   */
  public function setActionRelation(?string $actionRelation): UrlParameterInterface;

  /**
   * @return EntityInterface|null
   */
  public function getObject(): ?EntityInterface;

  /**
   * @param EntityInterface|null $object
   *
   * @return UrlParameterInterface
   */
  public function setObject(?EntityInterface $object): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getLanguage(): ?string;

  /**
   * @param string|null $language
   *
   * @return UrlParameterInterface
   */
  public function setLanguage(?string $language): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSecurity(): ?string;

  /**
   * @param string|null $security
   *
   * @return UrlParameterInterface
   */
  public function setSecurity(?string $security): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSecurityPassword(): ?string;

  /**
   * @param string|null $securityPassword
   *
   * @return UrlParameterInterface
   */
  public function setSecurityPassword(?string $securityPassword): UrlParameterInterface;

  /**
   * @return string|null
   */
  public function getSecurityRole(): ?string;

  /**
   * @param string|null $securityRole
   *
   * @return UrlParameterInterface
   */
  public function setSecurityRole(?string $securityRole): UrlParameterInterface;

  /**
   * @return string
   */
  public function getStatus(): string;

  /**
   * @param string $status
   *
   * @return UrlParameterInterface
   */
  public function setStatus(string $status): UrlParameterInterface;

  /**
   * @return bool
   */
  public function getIsIndex(): bool;

  /**
   * @param bool $isIndex
   *
   * @return UrlParameterInterface
   */
  public function setIsIndex(bool $isIndex): UrlParameterInterface;

  /**
   * @return bool
   */
  public function getIsFollow(): bool;

  /**
   * @param bool $isFollow
   *
   * @return UrlParameterInterface
   */
  public function setIsFollow(bool $isFollow): UrlParameterInterface;

  /**
   * @return bool
   */
  public function getInSitemap(): bool;

  /**
   * @param bool $inSitemap
   *
   * @return UrlParameterInterface
   */
  public function setInSitemap(bool $inSitemap): UrlParameterInterface;

}

    
    
      