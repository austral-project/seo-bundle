<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\SeoBundle\Validator;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AllowedDomain extends Constraint
{
  public string $message = 'Le domaine "{{ domain }}" n\'est pas autorisé pour la redirection.';
  public string $parameterName = 'austral_entity.allowed_domains';

  public function __construct(
    ?string $parameterName = null,
    ?string $message = null,
    ?array $groups = null,
    mixed $payload = null
  ) {
    parent::__construct([], $groups, $payload);

    $this->parameterName = $parameterName ?? $this->parameterName;
    $this->message = $message ?? $this->message;
  }
}