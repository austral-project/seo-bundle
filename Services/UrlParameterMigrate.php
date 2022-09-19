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
use Austral\EntityBundle\Entity\Interfaces\RobotInterface;
use Austral\EntityBundle\Entity\Interfaces\SeoInterface;
use Austral\EntityBundle\Entity\Interfaces\SocialNetworkInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Compression\Compression;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral UrlParameterMigrate service.
 * Recovery of old values from Austral 3.0 which are obsolete in Austral 3.1, this service will be removed in a future release
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterMigrate
{

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var Compression
   */
  protected Compression $compression;

  /**
   * UrlParameterMigrate constructor.
   *
   * @param Mapping $mapping
   * @param Compression $compression
   */
  public function __construct(Mapping $mapping, Compression $compression)
  {
    $this->mapping = $mapping;
    $this->compression = $compression;
  }

  /**
   * @param UrlParameter $urlParameter
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function recoveryRefUrlPathValue(UrlParameter $urlParameter, EntityInterface $object): UrlParameterMigrate
  {
    /* Retreive refUrlLast Austral 3.0 who is deprecated in Austral 3.1, this check will be removed in a future release */
    if($object instanceof SeoInterface && $urlParameter->getIsCreate())
    {
      $urlParameter->setPathLast($object->getRefUrlLast());
    }
    return $this;
  }

  /**
   * @param UrlParameter $urlParameter
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function recoverySeoValues(UrlParameter $urlParameter, EntityInterface $object): UrlParameterMigrate
  {
    /* Retreive value if SeoInterface Austral 3.0 who is deprecated in Austral 3.1, this check will be removed in a future release */
    if($object instanceof SeoInterface && $urlParameter->getIsCreate())
    {
      if(!$urlParameter->getSeoTitle())
      {
        $urlParameter->setSeoTitle($object->getRefTitle());
      }
      if(!$urlParameter->getSeoDescription())
      {
        $urlParameter->setSeoDescription($object->getRefDescription());
      }
      if(!$urlParameter->getSeoCanonical())
      {
        $urlParameter->setSeoCanonical($object->getCanonical());
      }
    }
    return $this;
  }

  /**
   * @param UrlParameter $urlParameter
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function recoveryRobotValues(UrlParameter $urlParameter, EntityInterface $object): UrlParameterMigrate
  {
    /* Retreive value if RobotInterface Austral 3.0 who is deprecated in Austral 3.1, this check will be removed in a future release */
    if($object instanceof RobotInterface && $urlParameter->getIsCreate())
    {
      $urlParameter->setInSitemap($object->getInSitemap());
      $urlParameter->setIsIndex($object->getIsIndex());
      $urlParameter->setIsFollow($object->getIsFollow());
      $urlParameter->setStatus($object->getStatus());
    }
    return $this;
  }

  /**
   * @param UrlParameter $urlParameter
   * @param EntityInterface $object
   *
   * @return $this
   * @throws \Exception
   */
  public function recoverySocialValues(UrlParameter $urlParameter, EntityInterface $object): UrlParameterMigrate
  {
    /* Retreive value if RobotInterface Austral 3.0 who is deprecated in Austral 3.1, this check will be removed in a future release */
    if($object instanceof SocialNetworkInterface && $urlParameter->getIsCreate())
    {
      $urlParameter->setSocialTitle($object->getSocialTitle());
      $urlParameter->setSocialDescription($object->getSocialDescription());
      /** @var FieldFileMapping $fieldFileMappingObject */
      $fieldFileMappingObject = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, "socialImage");

      /** @var FieldFileMapping $fieldFileMappingObject */
      $fieldFileMappingUrlParameter = $this->mapping->getFieldsMappingByFieldname($urlParameter->getClassnameForMapping(), FieldFileMapping::class, "socialImage");
      if($fieldFileMappingObject && $fieldFileMappingUrlParameter)
      {
        $urlParameter->setSocialImage($object->getSocialImage());
        $uploadsPathSource = AustralTools::join(
          $fieldFileMappingObject->path->upload,
          $fieldFileMappingObject->getFieldname()
        );
        $pathSource = AustralTools::join($uploadsPathSource, $fieldFileMappingObject->getObjectValue($object));

        $uploadsPathDestination = AustralTools::join(
          $fieldFileMappingObject->path->upload,
          $fieldFileMappingObject->getFieldname()
        );
        $pathDestination = AustralTools::join($uploadsPathDestination, $fieldFileMappingObject->getObjectValue($urlParameter));
        $filesystem = new Filesystem();
        if(file_exists($pathSource) && is_file($pathSource))
        {
          if(AustralTools::isImage($pathSource))
          {
            $filesystem->copy($pathSource, $pathDestination);
            $this->compression->compress($pathDestination, array("webp"));
          }
        }
      }
    }
    return $this;
  }



}