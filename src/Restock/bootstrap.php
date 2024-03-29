<?php

declare(strict_types=1);

require dirname(__DIR__) . '../../vendor/autoload.php';
// TODO: Use a config library instead?
// Possible option: https://config.thephpleague.com/1.1/
/**
 * @var array $config
 */
require dirname(__DIR__) . '/config.default.php';
if (file_exists(dirname(__DIR__) . '/config.php')) {
    require dirname(__DIR__) . '/config.php';
}

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

use \League\BooBoo\Formatter\JsonFormatter;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Restock\Controller;

// Error handler and logger.
$json_formatter = new JsonFormatter();
$booboo = new League\BooBoo\BooBoo([$json_formatter]);
$booboo->treatErrorsAsExceptions($config['debug']);
$logger = new Logger("Restock");
$logger->pushHandler(
    new StreamHandler(
        "../log/error.log",
        $config['debug'] ? Logger::DEBUG : Logger::ERROR
    )
);
$booboo->pushHandler(new League\BooBoo\Handler\LogHandler($logger));

try {
    $booboo->register();
} catch (\League\BooBoo\Exception\NoFormattersRegisteredException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    die('{"result": "error", "message": "An absurd condition has occurred. The error handler had an error."}');
}

if ($config['debug']) {
    $json_formatter->setErrorLimit(E_ALL);
    $booboo->setErrorPageFormatter($json_formatter);
}

// Consider: Use a query builder instead?
$db = new \PDO(
    "mysql:host=" . $config['database']['host'] . ";" .
    "dbname=" . $config['database']['database'] . ";charset=utf8mb4",
    $config['database']['username'], $config['database']['password'],
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

// Todo: encapsulate request and routing external from setup so bootstrapping can be used to set up the environment for scripts
$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$responseFactory = new Laminas\Diactoros\ResponseFactory();
$container = new League\Container\Container;

$doctrine_config = ORMSetup::createAttributeMetadataConfiguration(
    ['/src/Restock/Entity'],
    $config['debug']
);
$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'user' => $config['database']['username'],
    'password' => $config['database']['password'],
    'dbname' => $config['database']['database']
],
    $doctrine_config
);
$entityManager = new EntityManager($connection, $doctrine_config);
$container->add(EntityManager::class, $entityManager);

/** @var \Restock\Entity\User $user */
$user = null;
if ($request->hasHeader('X-RestockUserApiToken')) {
    $token = $request->getHeader('X-RestockUserApiToken')[0];
    /** @var \Restock\Entity\Session $session */
    if ($session = $entityManager->getRepository('Restock\Entity\Session')->findOneBy(['token' => $token])) {
        // Token is found
        #TODO: check session age
        $user = $session->getUser();
        $session->setLastUsedDate();
        $entityManager->persist($session);
        $entityManager->flush();
    }
}

$container->add(Restock\Controller\ActionLoggerController::class)
    ->addArgument(EntityManager::class)
    ->addArgument($user);
$container->add(Restock\Controller\GroupController::class)
    ->addArgument(EntityManager::class)
    ->addArgument($user);
$container->add(Restock\Controller\RecipeController::class)
    ->addArgument(EntityManager::class)
    ->addArgument($user);
$container->add(Restock\Controller\ItemController::class)
    ->addArgument(EntityManager::class)
    ->addArgument($user);
$container->add(Restock\Controller\UserController::class)
    ->addArgument(EntityManager::class)
    ->addArgument($user);
// Require only a supported content-type to be requested. Right now that means only JSON.
switch ($request->getHeader('Accept')[0]) {
    case "application/json":
        $strategy = (new League\Route\Strategy\JsonStrategy($responseFactory));
        break;
    case "text/html":
    case "application/xhtml+xml":
    case "text/xml":
    case "application/xml":
        // Should other content types be supported? Probably not.
    default:
        // Any other request will be treated as unsupported.
        http_response_code(406);
        die(
            "Unsupported content-type. Acceptable types are:\n" .
            "application/json"
        );
}

$strategy->setContainer($container);
$router = (new League\Route\Router())->setStrategy($strategy);

$router->addPatternMatcher('username', '[a-zA-Z0-9_\-]{3,30}');

// API endpoints which do not require user authentication
$router->group('/api/v1', function (\League\Route\RouteGroup $route) {
    $route->map('POST', '/session', [Restock\Controller\UserController::class, 'userLogin']);
    $route->map('POST', '/user', [Restock\Controller\UserController::class, 'createUser']);

    $route->map(
        'HEAD',
        '/user/{username:username}',
        [Restock\Controller\UserController::class, 'checkUsernameAvailable']
    );
})->middleware(new \Restock\Middleware\Auth\Api());

// API endpoints which require user authentication
$router->group('/api/v1', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/authtest', [Restock\Controller\UserController::class, 'authTest']);

    $route->map('DELETE', '/session', [Controller\UserController::class, 'userLogout']);

    $route->map('GET', '/user/{user_id:number}', [Restock\Controller\UserController::class, 'getUser']);
    $route->map('PUT', '/user/{user_id:number}', [Restock\Controller\UserController::class, 'updateUser']);
    $route->map('DELETE', '/user/{user_id:number}', [Restock\Controller\UserController::class, 'deleteUser']);

    $route->map('GET', '/group', [Restock\Controller\GroupController::class, 'getUserGroups']);
    $route->map('GET', '/group/{group_id:number}', [Restock\Controller\GroupController::class, 'getGroupDetails']);
//    $route->map('GET', '/group/{group_id:number}/members', [Restock\Controller\GroupController::class, 'getGroupMembers']);
    $route->map('GET', '/group/{group_id:number/items}', [Restock\Controller\GroupController::class, 'getGroupItems']);
    $route->map('GET', '/group/{group_id:number}/history', [Restock\Controller\GroupController::class, 'getGroupActionLogs']);
    $route->map(
        'POST',
        '/group/{group_id:number}/invite',
        [Restock\Controller\GroupController::class, 'createGroupInvite']
    );
    $route->map(
        'GET',
        '/group/{group_id:number}/invite',
        [Restock\Controller\GroupController::class, 'listGroupInvites']
    );
    $route->map(
        'DELETE',
        '/group/{group_id:number}/invite/{invite_id:number}',
        [Restock\Controller\GroupController::class, 'deleteGroupInvite']
    );
    $route->map(
        'GET',
        '/invite/{code}',
        [Restock\Controller\GroupController::class, 'getGroupInviteDetails']
    );
    $route->map(
        'POST',
        '/invite/{code}',
        [Restock\Controller\GroupController::class, 'acceptGroupInvite']
    );
    $route->map('POST', '/group', [Restock\Controller\GroupController::class, 'createGroup']);
    $route->map('PUT', '/group/{group_id:number}', [Restock\Controller\GroupController::class, 'updateGroup']);
    $route->map('DELETE', '/group/{group_id:number}', [Restock\Controller\GroupController::class, 'deleteGroup']);

    $route->map('GET', '/group/{group_id:number}/member', [Restock\Controller\GroupController::class, 'getGroupMembers']
    );
    $route->map(
        'GET',
        '/group/{group_id:number}/member/{group_member_id:number}',
        [Restock\Controller\GroupController::class, 'getGroupMemberDetails']
    );
    $route->map('POST', '/group/{group_id:number}/member', [Restock\Controller\GroupController::class, 'addGroupMember']
    );
    $route->map(
        'PUT',
        '/group/{group_id:number}/member/{group_member_id:number}',
        [Restock\Controller\GroupController::class, 'updateGroupMember']
    );
    $route->map(
        'DELETE',
        '/group/{group_id:number}/member/{group_member_id:number}',
        [Restock\Controller\GroupController::class, 'deleteGroupMember']
    );

    $route->map(
        'GET',
        '/group/{group_id:number}/item/{item_id:number}',
        [Restock\Controller\ItemController::class, 'getItemDetails']
    );
    $route->map('POST', '/group/{group_id:number}/item', [Restock\Controller\ItemController::class, 'createItem']);
    $route->map(
        'PUT',
        '/group/{group_id:number}/item/{item_id:number}',
        [Restock\Controller\ItemController::class, 'updateItem']
    );
    $route->map(
        'DELETE',
        '/group/{group_id:number}/item/{item_id:number}',
        [Restock\Controller\ItemController::class, 'deleteItem']
    );

//    $route->map('GET', '/recipe', [Restock\Controller\RecipeController::class, 'getRecipes']);
    $route->map('POST', '/recipe', [Restock\Controller\RecipeController::class, 'createRecipe']);
    $route->map('PUT', '/recipe', [Restock\Controller\RecipeController::class, 'editRecipe']);
    $route->map('DELETE', '/recipe', [Restock\Controller\RecipeController::class, 'deleteRecipe']);
})
    ->middleware(new \Restock\Middleware\Auth\Api())
    ->middleware(new \Restock\Middleware\Auth\User($user));


$response = $router->dispatch($request);
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);

