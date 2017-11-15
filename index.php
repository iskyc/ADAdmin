<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'config.php';
//$app = new \Slim\App;
$app = new \Slim\App(["settings" => $config]);

//$container = $app->getContainer();

//$container['db'] = function ($c) {
//    $db = $c['settings']['db'];
//    $medoo = new medoo([
//        'database_type' => $db['type'],
//        'database_name' => $db['dbname'],
//        'server' => $db['host'],
//        'username' => $db['user'],
//        'password' => $db['pass'],
//        'charset' => $db['charset'],
//    ]);
//    return $medoo;
//};

$app->get('/', function (Request $request, Response $response)
{
    global $menu_item_list;

    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader);
    echo $twig->render('index.twig', array('menu' => $menu_item_list));
    return $response;
});

$app->get('/login', function (Request $request, Response $response)
{
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader);
    echo $twig->render('login.twig');
    return $response;
});

$app->run();