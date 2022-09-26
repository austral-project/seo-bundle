<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\SeoBundle\Entity;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\SeoBundle\Entity\Interfaces\RedirectionInterface;

use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;
use Austral\HttpBundle\Annotation\DomainFilter;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;


/**
 * Austral Redirection Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 * @DomainFilter(forAllDomainEnabled=false, autoDomainId=true)
 */
abstract class Redirection extends Entity implements RedirectionInterface, EntityInterface
{

  use EntityTimestampableTrait;
  use FilterByDomainTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var boolean
   * @ORM\Column(name="is_auto_generate", type="boolean", nullable=true)
   */
  protected bool $isAutoGenerate = false;

  /**
   * @var boolean
   * @ORM\Column(name="is_active", type="boolean", nullable=true)
   */
  protected bool $isActive = false;
  
  /**
   * @var string|null
   * @ORM\Column(name="url_source", type="string", length=255, nullable=true )
   */
  protected ?string $urlSource = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="url_destination", type="string", length=255, nullable=true )
   */
  protected ?string $urlDestination = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="relation_entity_name", type="string", length=255, nullable=true )
   */
  protected ?string $relationEntityName = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="relation_entity_id", type="string", length=255, nullable=true )
   */
  protected ?string $relationEntityId = null;
  
  /**
   * @var int
   * @ORM\Column(name="status_code", type="integer", length=255, nullable=true )
   */
  protected int $statusCode = 301;
  
  /**
   * @var string|null
   * @ORM\Column(name="language", type="string", length=255, nullable=true )
   */
  protected ?string $language = null;

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->id ? sprintf("#%s", $this->getId()) : "";
  }

  /**
   * Get isAutoGenerate
   * @return bool
   */
  public function getIsAutoGenerate(): bool
  {
    return $this->isAutoGenerate;
  }

  /**
   * Set isAutoGenerate
   *
   * @param bool $isAutoGenerate
   *
   * @return $this
   */
  public function setIsAutoGenerate(bool $isAutoGenerate): Redirection
  {
    $this->isAutoGenerate = $isAutoGenerate;
    return $this;
  }

  /**
   * Get isActive
   * @return bool
   */
  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  /**
   * Set isActive
   *
   * @param bool $isActive
   *
   * @return $this
   */
  public function setIsActive(bool $isActive): Redirection
  {
    $this->isActive = $isActive;
    return $this;
  }

  /**
   * Get urlSource
   * @return string|null
   */
  public function getUrlSource(): ?string
  {
    return $this->urlSource;
  }

  /**
   * Set urlSource
   *
   * @param string|null $urlSource
   *
   * @return $this
   */
  public function setUrlSource(?string $urlSource): Redirection
  {
    $this->urlSource = $urlSource;
    return $this;
  }

  /**
   * Get urlDestination
   * @return string|null
   */
  public function getUrlDestination(): ?string
  {
    return $this->urlDestination;
  }

  /**
   * Set urlDestination
   *
   * @param string|null $urlDestination
   *
   * @return $this
   */
  public function setUrlDestination(?string $urlDestination): Redirection
  {
    $this->urlDestination = $urlDestination;
    return $this;
  }

  /**
   * Get relationEntityName
   * @return string|null
   */
  public function getRelationEntityName(): ?string
  {
    return $this->relationEntityName;
  }

  /**
   * @param string|null $relationEntityName
   *
   * @return Redirection
   */
  public function setRelationEntityName(?string $relationEntityName = null): Redirection
  {
    $this->relationEntityName = $relationEntityName;
    return $this;
  }

  /**
   * Get relationEntityId
   * @return string|null
   */
  public function getRelationEntityId(): ?string
  {
    return $this->relationEntityId;
  }

  /**
   * @param string|null $relationEntityId
   *
   * @return Redirection
   */
  public function setRelationEntityId(?string $relationEntityId = null): Redirection
  {
    $this->relationEntityId = $relationEntityId;
    return $this;
  }

  /**
   * Get statusCode
   * @return int
   */
  public function getStatusCode(): int
  {
    return $this->statusCode;
  }

  /**
   * Set statusCode
   *
   * @param int $statusCode
   *
   * @return $this
   */
  public function setStatusCode(int $statusCode): Redirection
  {
    $this->statusCode = $statusCode;
    return $this;
  }

  /**
   * Get language
   * @return string|null
   */
  public function getLanguage(): ?string
  {
    return $this->language;
  }

  /**
   * Set language
   *
   * @param string|null $language
   *
   * @return $this
   */
  public function setLanguage(?string $language): Redirection
  {
    $this->language = $language;
    return $this;
  }
  
}