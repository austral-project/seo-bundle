<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\SeoBundle\Admin;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\AdminModuleInterface;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;

use Austral\FormBundle\Field as Field;

use Austral\FormBundle\Mapper\Fieldset;
use Austral\ListBundle\Column as Column;

/**
 * Redirect Admin .
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class RedirectionAdmin extends Admin implements AdminModuleInterface
{

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent)
  {
    $listAdminEvent->getListMapper()
      ->addColumn(new Column\Value("urlSource"))
      ->addColumn(new Column\Value("urlDestination"))
      ->addColumn(new Column\Value("language"))
      ->addColumn(new Column\SwitchValue("isActive"));

  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent)
  {
    $formAdminEvent->getFormMapper()
      ->addFieldset("fieldset.right")
        ->setPositionName(Fieldset::POSITION_RIGHT)
        ->setViewName(false)
        ->add(Field\ChoiceField::create("isActive", array(
          "choices.status.no"         =>  false,
          "choices.status.yes"        =>  true,
        )))
      ->end()
      ->addFieldset("fieldset.generalInformation")
        ->add(Field\TextField::create("urlSource"))
        ->add(Field\TextField::create("urlDestination"))
        ->add(Field\IntegerField::create("statusCode"))
        ->add(Field\TextField::create("language"))
      ->end()
      ->addFieldset("fieldset.admin")
        ->setIsView($this->container->get('security.authorization_checker')->isGranted('ROLE_ROOT'))
        ->add(Field\TextField::create("relationEntityName"))
        ->add(Field\TextField::create("relationEntityId"))
        ->add(Field\SwitchField::create("isAutoGenerate"))
      ->end();
  }
}