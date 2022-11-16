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
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;

use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;
use Austral\EntityFileBundle\Annotation as AustralFile;

use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;

use Austral\HttpBundle\Annotation\DomainFilter;

use Austral\ToolsBundle\AustralTools;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

use Exception;


/**
 * Austral UrlParameter Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 * @ORM\MappedSuperclass
 * @UniqueEntity(fields={"domainId", "language", "objectRelation", "actionRelation"}, errorPath="pathLast")
 * @DomainFilter(forAllDomainEnabled=false, autoDomainId=true, autoAttachement=false)
 */
abstract class UrlParameter extends Entity implements EntityInterface, UrlParameterInterface, FileInterface
{
  use EntityFileTrait;
  use EntityTimestampableTrait;
  use FilterByDomainTrait;

  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;

  /**
   * @var string|null
   * @ORM\Column(name="path", type="string", length=255, nullable=false )
   */
  protected ?string $path = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="path_last", type="string", length=255, nullable=false )
   */
  protected ?string $pathLast = null;

  /**
   * @var string|null
   * @ORM\Column(name="seo_title", type="string", length=255, nullable=true )
   */
  protected ?string $seoTitle = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="seo_description", type="text", nullable=true )
   */
  protected ?string $seoDescription = null;

  /**
   * @var string|null
   * @ORM\Column(name="seo_canonical", type="string", length=255, nullable=true)
   */
  protected ?string $seoCanonical = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="social_title", type="string", length=255, nullable=true )
   */
  protected ?string $socialTitle = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="social_description", type="text", nullable=true )
   */
  protected ?string $socialDescription = null;

  /**
   * @var string|null
   * @ORM\Column(name="social_image", type="string", length=255, nullable=true)
   * @AustralFile\ImageSize()
   * @AustralFile\Croppers({
   *  "social"
   * })
   * @AustralFile\UploadParameters(configName="social_image")
   */
  protected ?string $socialImage = null;

  /**
   * @var string|null
   * @ORM\Column(name="object_keyname", type="string", length=255, nullable=true )
   */
  protected ?string $objectKeyname = null;

  /**
   * @var string|null
   * @ORM\Column(name="object_relation", type="string", length=255, nullable=true )
   */
  protected ?string $objectRelation = null;

  /**
   * @var string|null
   * @ORM\Column(name="action_relation", type="string", length=255, nullable=true )
   */
  protected ?string $actionRelation = null;

  /**
   * @var array
   */
  protected array $actionParameters = array();

  /**
   * @var string|null
   * @ORM\Column(name="name", type="string", length=255, nullable=true )
   */
  protected ?string $name = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="language", type="string", length=255, nullable=true )
   */
  protected ?string $language = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="security", type="string", length=255, nullable=true )
   */
  protected ?string $security = null;
  
  /**
   * @var string|null
   * @ORM\Column(name="security_password", type="string", length=255, nullable=true )
   */
  protected ?string $securityPassword = null;

  /**
   * @var string|null
   * @ORM\Column(name="security_role", type="string", length=255, nullable=true )
   */
  protected ?string $securityRole = null;

  /**
   * @var string
   * @ORM\Column(name="status", type="string", length=50, nullable=false )
   */
  protected string $status = UrlParameterInterface::STATUS_PUBLISHED; // draft // published

  /**
   * @var boolean
   * @ORM\Column(name="is_index", type="boolean", nullable=false)
   */
  protected bool $isIndex = true;

  /**
   * @var boolean
   * @ORM\Column(name="is_follow", type="boolean", nullable=false)
   */
  protected bool $isFollow = true;

  /**
   * @var boolean
   * @ORM\Column(name="in_sitemap", type="boolean", nullable=false)
   */
  protected bool $inSitemap = true;

  /**
   * @var string|null
   */
  protected ?string $keyLink = null;

  /**
   * @var bool
   */
  protected bool $isVirtual = false;

  /**
   * @var bool
   */
  protected bool $isTreeView = true;

  /**
   * @var DomainInterface|null
   */
  protected ?DomainInterface $domain = null;

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
   * @return string|null
   */
  public function __toString()
  {
    if($this->object)
    {
      return $this->object->__toString();
    }
    return $this->name ?? $this->path;
  }

  /**
   * @return string|null
   */
  public function getKeyLink(): ?string
  {
    return $this->keyLink;
  }

  /**
   * @param string|null $keyLink
   *
   * @return UrlParameter
   */
  public function setKeyLink(?string $keyLink): UrlParameter
  {
    $this->keyLink = $keyLink;
    return $this;
  }

  /**
   * @return DomainInterface|null
   */
  public function getDomain(): ?DomainInterface
  {
    return $this->domain;
  }

  /**
   * @param DomainInterface|null $domain
   *
   * @return UrlParameter
   */
  public function setDomain(?DomainInterface $domain): UrlParameter
  {
    $this->domain = $domain;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getPath(): ?string
  {
    return $this->path;
  }

  /**
   * @param string|null $path
   *
   * @return UrlParameter
   */
  public function setPath(?string $path): UrlParameter
  {
    $this->path = $path;
    return $this;
  }

  /**
   * @return string
   */
  public function getBasePath()
  {
    $urls = explode("/", $this->getPath());
    unset($urls[array_key_last($urls)]);
    return implode("/", $urls)."/";
  }

  /**
   * @return string|null
   */
  public function getPathLast(): ?string
  {
    return $this->pathLast;
  }

  /**
   * @param string|null $pathLast
   *
   * @return UrlParameter
   */
  public function setPathLast(?string $pathLast): UrlParameter
  {
    $this->pathLast = AustralTools::slugger($pathLast, true, true);
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSeoTitle(): ?string
  {
    return $this->seoTitle;
  }

  /**
   * @param string|null $seoTitle
   *
   * @return UrlParameter
   */
  public function setSeoTitle(?string $seoTitle): UrlParameter
  {
    $this->seoTitle = $seoTitle;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSeoDescription(): ?string
  {
    return $this->seoDescription;
  }

  /**
   * @param string|null $seoDescription
   *
   * @return UrlParameter
   */
  public function setSeoDescription(?string $seoDescription): UrlParameter
  {
    $this->seoDescription = $seoDescription;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSeoCanonical(): ?string
  {
    return $this->seoCanonical;
  }

  /**
   * @param string|null $seoCanonical
   *
   * @return UrlParameter
   */
  public function setSeoCanonical(?string $seoCanonical): UrlParameter
  {
    $this->seoCanonical = $seoCanonical;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSocialTitle(): ?string
  {
    return $this->socialTitle;
  }

  /**
   * @param string|null $socialTitle
   *
   * @return UrlParameter
   */
  public function setSocialTitle(?string $socialTitle): UrlParameter
  {
    $this->socialTitle = $socialTitle;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSocialDescription(): ?string
  {
    return $this->socialDescription;
  }

  /**
   * @param string|null $socialDescription
   *
   * @return UrlParameter
   */
  public function setSocialDescription(?string $socialDescription): UrlParameter
  {
    $this->socialDescription = $socialDescription;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSocialImage(): ?string
  {
    return $this->socialImage;
  }

  /**
   * @param string|null $socialImage
   *
   * @return UrlParameter
   */
  public function setSocialImage(?string $socialImage): UrlParameter
  {
    $this->socialImage = $socialImage;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getObjectKeyname(): ?string
  {
    return $this->objectKeyname;
  }

  /**
   * @param string|null $objectKeyname
   *
   * @return UrlParameter
   */
  public function setObjectKeyname(?string $objectKeyname): UrlParameter
  {
    $this->objectKeyname = $objectKeyname;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getObjectRelation(): ?string
  {
    return $this->objectRelation;
  }

  /**
   * @param string|null $objectRelation
   *
   * @return UrlParameter
   */
  public function setObjectRelation(?string $objectRelation): UrlParameter
  {
    $this->objectRelation = $objectRelation;
    return $this;
  }

  /**
   * @return array
   */
  public function getObjectRelationParameters(): array
  {
    return explode("::", $this->getObjectRelation());
  }

  /**
   * @return string
   */
  public function getObjectClass(): string
  {
    return AustralTools::getValueByKey($this->getObjectRelationParameters(), 0);
  }

  /**
   * @return string|null
   * @throws \ReflectionException
   */
  public function getObjectClassShort(): ?string
  {
    if($this->getObjectClass())
    {
      return (new \ReflectionClass($this->getObjectClass()))->getShortName();
    }
    return null;
  }

  /**
   * @return string|int
   */
  public function getObjectId()
  {
    return AustralTools::getValueByKey($this->getObjectRelationParameters(), 1);
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string|null $name
   *
   * @return $this
   */
  public function setName(?string $name): UrlParameter
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @var EntityInterface|null
   */
  protected ?EntityInterface $object = null;

  /**
   * @return EntityInterface|null
   */
  public function getObject(): ?EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface|null $object
   *
   * @return UrlParameter
   */
  public function setObject(?EntityInterface $object): UrlParameter
  {
    $this->object = $object;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getActionRelation(): ?string
  {
    return $this->actionRelation;
  }

  /**
   * @param string|null $actionRelation
   *
   * @return UrlParameter
   */
  public function setActionRelation(?string $actionRelation): UrlParameter
  {
    $this->actionRelation = $actionRelation;
    return $this;
  }

  /**
   * @return array
   */
  public function getActionParameters(): array
  {
    return $this->actionParameters;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return mixed
   */
  public function getActionParameterByKey(string $key, $default = null)
  {
    return AustralTools::getValueByKey($this->actionParameters, $key, $default);
  }

  /**
   * @param string $key
   * @param $value
   *
   * @return UrlParameter
   */
  public function addActionParameters(string $key, $value): UrlParameter
  {
    $this->actionParameters[$key] = $value;
    return $this;
  }

  /**
   * @param array $actionParameters
   *
   * @return UrlParameter
   */
  public function setActionParameters(array $actionParameters): UrlParameter
  {
    $this->actionParameters = $actionParameters;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLanguage(): ?string
  {
    return $this->language;
  }

  /**
   * @param string|null $language
   *
   * @return UrlParameter
   */
  public function setLanguage(?string $language): UrlParameter
  {
    $this->language = $language;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSecurity(): ?string
  {
    return $this->security;
  }

  /**
   * @param string|null $security
   *
   * @return UrlParameter
   */
  public function setSecurity(?string $security): UrlParameter
  {
    $this->security = $security;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSecurityPassword(): ?string
  {
    return $this->securityPassword;
  }

  /**
   * @param string|null $securityPassword
   *
   * @return UrlParameter
   */
  public function setSecurityPassword(?string $securityPassword): UrlParameter
  {
    $this->securityPassword = $securityPassword;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getSecurityRole(): ?string
  {
    return $this->securityRole;
  }

  /**
   * @param string|null $securityRole
   *
   * @return UrlParameter
   */
  public function setSecurityRole(?string $securityRole): UrlParameter
  {
    $this->securityRole = $securityRole;
    return $this;
  }

  /**
   * @return string
   */
  public function getStatus(): string
  {
    return $this->status;
  }

  /**
   * @param string $status
   *
   * @return UrlParameter
   */
  public function setStatus(string $status): UrlParameter
  {
    $this->status = $status;
    return $this;
  }

  /**
   * @return bool
   */
  public function isPublished(): bool
  {
    return $this->status === UrlParameterInterface::STATUS_PUBLISHED;
  }

  /**
   * @return bool
   */
  public function getIsIndex(): bool
  {
    return $this->isIndex;
  }

  /**
   * @param bool $isIndex
   *
   * @return UrlParameter
   */
  public function setIsIndex(bool $isIndex): UrlParameter
  {
    $this->isIndex = $isIndex;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsFollow(): bool
  {
    return $this->isFollow;
  }

  /**
   * @param bool $isFollow
   *
   * @return UrlParameter
   */
  public function setIsFollow(bool $isFollow): UrlParameter
  {
    $this->isFollow = $isFollow;
    return $this;
  }

  /**
   * @return bool
   */
  public function getInSitemap(): bool
  {
    return $this->inSitemap;
  }

  /**
   * @param bool $inSitemap
   *
   * @return UrlParameter
   */
  public function setInSitemap(bool $inSitemap): UrlParameter
  {
    $this->inSitemap = $inSitemap;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsVirtual(): bool
  {
    return $this->isVirtual;
  }

  /**
   * @param bool $isVirtual
   *
   * @return UrlParameter
   */
  public function setIsVirtual(bool $isVirtual): UrlParameter
  {
    $this->isVirtual = $isVirtual;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsTreeView(): bool
  {
    return $this->isTreeView;
  }

  /**
   * @param bool $isTreeView
   *
   * @return UrlParameter
   */
  public function setIsTreeView(bool $isTreeView): UrlParameter
  {
    $this->isTreeView = $isTreeView;
    return $this;
  }

}