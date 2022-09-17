<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Listener;

use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Entity\Redirection;
use Austral\SeoBundle\Services\RedirectionManagement;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral Doctrine Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener implements EventSubscriber
{

  /**
   * @var mixed
   */
  protected $name;

  /**
   * @var bool
   */
  protected bool $postFlush = false;

  /**
   * @var RedirectionManagement
   */
  protected RedirectionManagement $redirectionManagement;

  /**
   * DoctrineListener constructor.
   */
  public function __construct(RedirectionManagement $redirectionManagement)
  {
    $parts = explode('\\', $this->getNamespace());
    $this->name = end($parts);
    $this->redirectionManagement = $redirectionManagement;
  }

  /**
   * @return array
   */
  public function getSubscribedEvents()
  {
    return array(
      'preUpdate',
      "postFlush"
    );
  }

  /**
   * @param PreUpdateEventArgs $args
   *
   * @throws NonUniqueResultException
  */
  public function preUpdate(PreUpdateEventArgs $args)
  {
    $object = $args->getObject();
    if($object instanceof UrlParameterInterface)
    {
      if($args->hasChangedField("path"))
      {
        $this->redirectionManagement->generateRedirectionAuto($object, $args->getNewValue("path"), $args->getOldValue("path"));
      }
    }
  }

  /**
   * @param PostFlushEventArgs $args
   *
   */
  public function postFlush(PostFlushEventArgs $args)
  {
    if(!$this->postFlush)
    {
      $this->postFlush = true;
      $entityManager = $args->getObjectManager();
      /** @var Redirection $redirection */
      foreach($this->redirectionManagement->getRedirectionsUpdate() as $redirection)
      {
        if(!$redirection->getIsActive() && $redirection->getIsAutoGenerate())
        {
          $entityManager->remove($redirection);
        }
        else
        {
          $entityManager->persist($redirection);
        }
      }
      $entityManager->flush();
    }
  }

  /**
   * Get an event adapter to handle event specific
   * methods
   *
   * @param EventArgs $args
   *
   * @return EventArgs
   */
  protected function getEventAdapter(EventArgs $args): EventArgs
  {
    return $args;
  }

  /**
   * @return string
   */
  protected function getNamespace(): string
  {
    return __NAMESPACE__;
  }

}