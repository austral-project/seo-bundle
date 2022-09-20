<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Command;

use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\ToolsBundle\Command\Base\Command;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Austral Roles Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterCommand extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:seo:url-parameter';

  /**
   * @var string
   */
  protected string $titleCommande = "Generate Urls parameter";

  /**
   * @var UrlParameterEntityManager|null
   */
  protected ?UrlParameterEntityManager $urlParameterEntityManager;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
        new InputOption('--clean', '-c', InputOption::VALUE_NONE, 'Delete all UrlParameters'),
        new InputOption('--generate', '-g', InputOption::VALUE_NONE, 'Generate automatically UrlParameters'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> command to create or update modules roles

  <info>php %command.full_name% --clean</info>
  <info>php %command.full_name% --generate</info>
  <info>php %command.full_name% --clean --generate</info>
  
  <info>php %command.full_name% -c</info>
  <info>php %command.full_name% -g</info>
  <info>php %command.full_name% -c -g</info>
EOF
      )
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws Exception
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {

    $this->urlParameterEntityManager = $this->container->get("austral.entity_manager.url_parameter");
    if($input->getOption("clean"))
    {
      $classMetaData = $this->urlParameterEntityManager->getDoctrineEntityManager()->getClassMetadata($this->urlParameterEntityManager->getClass());
      $connection = $this->urlParameterEntityManager->getDoctrineEntityManager()->getConnection();
      $dbPlatform = $connection->getDatabasePlatform();
      $connection->beginTransaction();
      $isMysql = $connection->getDriver() instanceof Driver;
      try {
        if($isMysql) {
          $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }
        $q = $dbPlatform->getTruncateTableSql($classMetaData->getTableName(), true);
        $connection->executeStatement($q);
        if($isMysql) {
          $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
        $connection->commit();
        $this->viewMessage("UrlParameters Clean successfully !!!", "success");
      }
      catch (Exception $e) {
        $connection->rollback();
        $this->viewMessage("UrlParameters Clean error -> {$e->getMessage()} !!!", "error");
      }
    }
    if($input->getOption("generate"))
    {
      /** @var DomainsManagement $domainsManagement */
      $domainsManagement = $this->container->get('austral.http.domains.management');
      $domainsManagement->initialize();

      /** @var UrlParameterManagement $urlParameterManagement */
      $urlParameterManagement = $this->container->get('austral.seo.url_parameter.management');
      $urlParameterManagement->initialize()->generateAllWithMapping();
    }


  }

}