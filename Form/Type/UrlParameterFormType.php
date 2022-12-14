<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Form\Type;

use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\FormBundle\Form\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Austral UrlParameter Form Type.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterFormType extends FormType
{

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);
    $resolver->setDefaults([
      'data_class' => UrlParameterInterface::class,
    ]);
  }

}