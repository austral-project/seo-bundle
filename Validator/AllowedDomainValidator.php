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

use Austral\SeoBundle\Configuration\SeoConfiguration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AllowedDomainValidator extends ConstraintValidator
{

  public function __construct(
    #[Autowire(service: "austral.seo.config")]private readonly SeoConfiguration $seoConfiguration
  ) {}

  public function validate(mixed $value, Constraint $constraint): void
  {
    if (!$constraint instanceof AllowedDomain) {
      throw new UnexpectedTypeException($constraint, AllowedDomain::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    if (!is_string($value)) {
      throw new UnexpectedValueException($value, 'string');
    }

    // Vérification si l'URL commence par http:// ou https://
    if (preg_match('#^https?://#i', $value)) {
      $host = parse_url($value, PHP_URL_HOST);

      if (!$host) {
        $this->context->buildViolation('L\'URL fournie est invalide.')
          ->addViolation();
        return;
      }

      // Normalisation du domaine (passage en minuscules)
      $host = strtolower($host);
      $allowedDomains = $this->seoConfiguration->get("redirection.allowed_domains", array());

      if (!in_array($host, $allowedDomains, true)) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('{{ domain }}', $host)
          ->addViolation();
      }
    }
  }
}