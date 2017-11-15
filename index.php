<?php
use Medoo\Medoo;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'config.php';
//$app = new \Slim\App;
$app = new \Slim\App(["settings" => $config]);
// Fetch DI Container
$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates', [
//        'cache' => 'cache'
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

    return $view;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $medoo = new medoo([
        'database_type' => $db['type'],
        'database_name' => $db['dbname'],
        'server' => $db['host'],
        'username' => $db['user'],
        'password' => $db['pass'],
        'charset' => $db['charset'],
    ]);
    return $medoo;
};

$app->get('/', function (Request $request, Response $response)
{
    global $menu_item_list;
    $this->view->render($response, 'index.twig', array('menu' => $menu_item_list));
});

$app->get('/login', function (Request $request, Response $response)
{
    $this->view->render($response, 'login.twig');
});
$app->get('/reg', function (Request $request, Response $response)
{
    $this->view->render($response, 'register.twig');
});

$app->post('/login', function (Request $request, Response $response)
{
    $username = $request->getParsedBody()['username'];
    $passwd = $request->getParsedBody()['passwd'];
    $resp = array();
    if ($username == 'admin' && $passwd == '123456')
    {
        $resp['code'] = 1;
    }
    else
    {
        $resp['code'] = 0;
    }

    return $response->withJson($resp, 200);
});

$app->post('/reg', function (Request $request, Response $response)
{
    $email = $request->getParsedBody()['email'];
    $username = $request->getParsedBody()['username'];
    $passwd = $request->getParsedBody()['passwd'];
    $passwd2 = $request->getParsedBody()['passwd2'];
    $resp = array();

    if (empty($email) || empty($username) || empty($passwd) || empty($passwd2))
    {
        $resp['code'] = 3;
    }
    else if ($passwd != $passwd2)
    {
        $resp['code'] = 1;
    }
    else
    {
        $data = $this->db->select('users', '*', ['username[=]' => $username]);
        if (!isset($data) || count($data) == 0)
        {
            $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => md5($passwd),
                'type' => 1,
                'state' => 0,
                'last_time' => date('Y-m-d H:i:s',time()),
                'create_time' => date('Y-m-d H:i:s',time())
            ]);

            $resp['code'] = 0;
        }
        else
        {
            $resp['code'] = 2;
        }
    }
    return $response->withJson($resp, 200);
});

$app->run();