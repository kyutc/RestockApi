<?php declare(strict_types=1);

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

// TODO: Make the namespace here less bad
use Restock\Middleware\Auth\Api;
use Restock\Middleware\Auth\User;
use Restock\Db\Register;

// Error handler and logger.
$json_formatter = new JsonFormatter();
$booboo = new League\BooBoo\BooBoo([$json_formatter]);
$booboo->treatErrorsAsExceptions($config['debug']);
$logger = new Logger("Restock");
$logger->pushHandler(new StreamHandler("../log/error.log", $config['debug'] ? Logger::DEBUG : Logger::ERROR));
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

$db = new \PDO("mysql:host=" . $config['database']['host'] . ";" .
    "dbname=" . $config['database']['database'] . ";charset=utf8mb4",
    $config['database']['username'], $config['database']['password'],
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$responseFactory = new Laminas\Diactoros\ResponseFactory();

// Require only a supported content-type to be requested. Right now that means only JSON.
switch($request->getHeader('Accept')[0]) {
    case "application/json":
        $strategy = new League\Route\Strategy\JsonStrategy($responseFactory);
        break;
    case "text/html":
    case "application/xhtml+xml":
    case "text/xml":
    case "application/xml":
        // Should other content types be supported? Probably not.
        die("Unsupported content-type.");
    default:
        // Any other request will be treated as unsupported.
        die("Unsupported content-type.");
}

$router = (new League\Route\Router)->setStrategy($strategy);

$router->addPatternMatcher('username', '[a-zA-Z0-9_\-]{3,30}');

// This group is for unauthenticated users (not logged in)
// Intended only for two functions: logging in, and creating an account.
// These routes can be cleaned up by creating a proper organisation of the functions
// into separate files.
$router->group('/api/v1', function (\League\Route\RouteGroup $route) {

    // User login
    $route->map('GET', '/login', function (ServerRequestInterface $request): ResponseInterface {
        global $db;

        // TODO: Rate limiting
        // TODO: Token limiting ex. 10 before older tokens get replaced? Or allow no more than 1 token per user.

        $username = $request->getQueryParams()['username'];
        $password = $request->getQueryParams()['password'];

        if (!is_string($username) || !is_string($password)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invalid username or password.'],
                400
            );
        }

        $reg = new \Restock\Db\Register($db);

        $token = '';
        $result = $reg->Login($username, $password, $token);

        if ($result) {
            return new JsonResponse([
                'result' => 'success',
                'token' => $token],
                200
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Invalid username or password.'],
            400
        );
    });

    // User registration
    $route->map('POST', '/register', function (ServerRequestInterface $request): ResponseInterface {
        global $db; // Improper DI

        // TODO: Rate limiting and captcha.

        // TODO: Use tools instead of manually checking user input and creating errors
        $username = $request->getParsedBody()['username'];
        $password = $request->getParsedBody()['password'];

        // Consider: mb_strlen and is varchar/other data type multibyte aware in db?
        // TODO: Limit charset of username to A-Z, a-z, 0-9, -, _
        if (!is_string($username) || strlen($username) < 3 || strlen($username) > 30) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Username must be between 3 and 30 characters.'],
                400
            );
        }

        if (!is_string($password) || strlen($password) < 8) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Password must be 8 or more characters.'],
                400
            );
        }

        $reg = new \Restock\Db\Register($db);
        $reg->CreateAccount($username, $password);

        return new \Laminas\Diactoros\Response\JsonResponse(['result' => 'success'], 200);
    });

    // Username availability checking
    // TODO: Mutable path with user_id included?
    // Or use a dedicated path rather than a HEAD request?
    $route->map('HEAD', '/user/{username:username}', function (ServerRequestInterface $request, array $args): ResponseInterface {
        global $db;

        $reg = new Register($db);

        if ($reg->CheckUsernameAvailability($args['username'])) {
            return new JsonResponse([],404); // Username is available
        }

        return new JsonResponse([],200); // Username is not available
    });

})->middleware(new \Restock\Middleware\Auth\Api());

// API endpoints which require user authentication
$router->group('/api/v1', function (\League\Route\RouteGroup $route) {
    $route->map('GET', '/', function (ServerRequestInterface $request): array {
        return [
            'title'   => 'API',
            'version' => 1,
        ];
    });
    $route->map('GET', '/authtest', function (ServerRequestInterface $request): array {
        return [
            'messsage'   => 'Seeing this means auth is successful',
        ];
    });
})
    ->middleware(new \Restock\Middleware\Auth\Api())
    ->middleware(new \Restock\Middleware\Auth\User(new Register($db)));


$response = $router->dispatch($request);
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);

