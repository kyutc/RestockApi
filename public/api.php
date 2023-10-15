<?php

declare(strict_types=1);

require '../vendor/autoload.php';
// TODO: Use a config library instead?
// Possible option: https://config.thephpleague.com/1.1/
/**
 * @var array $config
 */
require '../src/config.default.php';
require '../src/config.php';

use \League\BooBoo\BooBoo;
use \League\BooBoo\Formatter\JsonFormatter;
use \Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Restock\Middleware\Auth\Api;
use Restock\Middleware\Auth\User;
use Restock\Db\UserAccount;
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

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$responseFactory = new Laminas\Diactoros\ResponseFactory();
$container = new League\Container\Container;

$userAccount = new UserAccount($db);
$container->add(Restock\Controller\Api::class)->addArgument($userAccount);
$container->add(UserAccount::class);

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
    $route->map('POST', '/session', [Controller\Api::class, 'userLogin']);
    $route->map('POST', '/user', [Restock\Controller\Api::class, 'registerNewUser']);

    $route->map('HEAD', '/user/{username:username}', [Restock\Controller\Api::class, 'checkUsernameAvailable']);
})->middleware(new \Restock\Middleware\Auth\Api());

// API endpoints which require user authentication
$router->group('/api/v1', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/authtest', [Restock\Controller\Api::class, 'authTest']);

    $route->map('DELETE', '/session', [Controller\Api::class, 'userLogout']);

    $route->map('GET', '/user/{user_id:number}', [Restock\Controller\Api::class, 'getUserAccount']);
    $route->map('PUT', '/user/{user_id:number}', [Restock\Controller\Api::class, 'updateUserAccount']);
    $route->map('DELETE', '/user/{user_id:number}', [Restock\Controller\Api::class, 'deleteUserAccount']);

    $route->map('GET', '/group/{group_id:number}', [Restock\Controller\Group::class, 'getGroupDetails']);
    $route->map('POST', '/group/{group_id:number}', [Restock\Controller\Group::class, 'createGroup']);
    $route->map('PUT', '/group/{group_id:number}', [Restock\Controller\Group::class, 'updateGroup']);
    $route->map('DELETE', '/group/{group_id:number}', [Restock\Controller\Group::class, 'deleteGroup']);

    $route->map(
        'GET',
        '/group/{group_id:number}/member/{user_id:number}',
        [Restock\Controller\Group::class, 'getGroupMemberDetails']
    );
    $route->map('POST', '/group/{group_id:number}/member', [Restock\Controller\Group::class, 'addGroupMember']);
    $route->map(
        'PUT',
        '/group/{group_id:number}/member/{user_id:number}',
        [Restock\Controller\Group::class, 'updateGroupMember']
    );
    $route->map(
        'DELETE',
        '/group/{group_id:number}/member/{user_id:number}',
        [Restock\Controller\Group::class, 'deleteGroupMember']
    );
})
    ->middleware(new \Restock\Middleware\Auth\Api())
    ->middleware(new \Restock\Middleware\Auth\User($userAccount));


$response = $router->dispatch($request);
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);

