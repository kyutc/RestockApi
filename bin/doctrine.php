<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

//require_once dirname(__DIR__).'/src/Restock/bootstrap.php';
require_once dirname(__DIR__).'/vendor/autoload.php';
/**
 * @var array $config
 */
require dirname(__DIR__).'/src/config.php';


$doctrine_config = ORMSetup::createAttributeMetadataConfiguration(
    [dirname(__DIR__).'/src/Restock/Entity'],
    $config['debug'],
    null,
    null,
    false
);
$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'user' => $config['database']['username'],
    'password' => $config['database']['password'],
    'dbname' => $config['database']['database'],
    'host' => $config['database']['host']
],
    $doctrine_config
);
$entityManager = new EntityManager($connection, $doctrine_config);

ConsoleRunner::run(
    new SingleManagerProvider($entityManager)
);