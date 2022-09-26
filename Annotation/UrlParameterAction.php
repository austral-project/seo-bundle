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
 * @Target({"METHOD"})
 */
final class UrlParameterAction extends AustralEntityAnnotation
{
  /**
   * @var string|null
   */
  public ?string $name = null;

  /**
   * @param string|null $name
   */
  public function __construct(?string $name = null) {
    $this->name = $name;
  }

}
