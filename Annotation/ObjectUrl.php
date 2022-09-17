<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\SeoBundle\Annotation;

use Austral\EntityBundle\Annotation\AustralEntityAnnotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"CLASS"})
 */
final class ObjectUrl extends AustralEntityAnnotation
{

  /**
   * @var string|null
   */
  public ?string $methodGenerateLastPath = null;

  /**
   * @var string|null
   */
  public ?string $keyForObjectLink = null;

  /**
   * @param null $methodGenerateLastPath
   */
  public function __construct($methodGenerateLastPath = null, $keyForObjectLink = null) {
    $this->methodGenerateLastPath = $methodGenerateLastPath;
    $this->keyForObjectLink = $keyForObjectLink;
  }

}
