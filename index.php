<?php
use Medoo\Medoo;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'config.php';

date_default_timezone_set('PRC');
session_start();

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

function redirect()
{
    
}

function render($app, Response $response, $twig_file, $params=array())
{
    global $_SESSION;
    global $menu_item_list;    
    if (!isset($_SESSION['user']))
    {
        return $response->withRedirect('/login', 302);
    }
    $user = $_SESSION['user'];
    $filter_menu = array();
    // 过滤掉没有权限的菜单
    foreach ($menu_item_list as $item)
    {
        if ($item['type'] == $user['type'])
        {
            array_push($filter_menu, $item);
        }
    }

    $active_menu = str_replace('.twig', '', $twig_file);

    $temp_param = array('menu' => $filter_menu, 'active_menu' => $active_menu);
    if (isset($params) && count($params) > 0)
    {
        $temp_param = array_merge($temp_param, $params);
    }

    return $app->view->render($response, $twig_file, $temp_param);
}

$app->get('/', function (Request $request, Response $response)
{
    return render($this, $response, 'index.twig');
});

$app->post('/edituser', function (Request $request, Response $response)
{
    global $_SESSION;
    if (!isset($_SESSION['user']))
    {
        return $response->withRedirect('/login', 302);
    }
    $user = $_SESSION['user'];
    if ($user['type'] != 0)
    {
        return $response->withStatus(404);
    }
    $code = 0;
    $uid = $request->getParsedBody()['uid'];
    $passwd = $request->getParsedBody()['passwd'];
    $state = $request->getParsedBody()['state'];
    if (!isset($uid))
    {
        $code = 1;
    }
    else
    {
        $data = $this->db->select('users', '*', ['uid[=]' => $uid]);
        if (!isset($data) || count($data) == 0)
        {
            $code = 2;  // 用户不存在
        }
        else
        {
            if ($state != 0)
            {
                $state = 1;
            }
            $userData = $data[0];
            if (isset($passwd) && strlen($passwd) > 4)
            {
                $this->db->update('users', ['state[=]' => $state, 'password' => md5($passwd)], ['uid[=]' => $uid]);
            }
            else
            {
                $this->db->update('users', ['state' => $state], ['uid[=]' => $uid]);
            }
            $code = 0;
        }
    }

    return $response->withJson(['code' => $code], 200);
});

$app->get('/member', function (Request $request, Response $response)
{
    global $_SESSION;    
    if (!isset($_SESSION['user']))
    {        
        return $response->withRedirect('/login', 302);
    }
    $user = $_SESSION['user'];
    if ($user['type'] != 0)
    {        
        return $response->withStatus(404);
    }
    $page = $request->getQueryParams()['page'];
    if (!isset($page))
    {
        $page = 1;
    }

    $row = 50;
    $offset = ($page - 1) * $row;

    $count = $this->db->count('users', ['type' => 1]);

    $total_page = $count / $row;
    if ($count % $row != 0)
    {
        $total_page++;
    }
   $users = $this->db->select('users', '*', ['type[=]' => 1], ['limit' => [$row, $offset]]);

    $pages = array();
    if ($total_page > 1)
    {
        if ($total_page == 2)
        {
            if ($page == 2)
            {
                array_push($pages, '<li class="paginate_button previous" aria-controls="example-1" tabindex="0"
                                    id="example-1_previous"><a href="/member?page=1">上一页</a></li>');
                array_push($pages, '<li class="paginate_button active" aria-controls="example-1" tabindex="0"><a
                                            href="#">2</a></li>');
            }
            else
            {
                array_push($pages, '<li class="paginate_button active" aria-controls="example-1" tabindex="0"><a
                                            href="#">1</a></li>');
                array_push($pages, '<li class="paginate_button next" aria-controls="example-1" tabindex="0"
                                    id="example-1_next"><a href="/member?page=2">下一页</a></li>');
            }
        }
        else
        {

        }
    }

    $pageStrs = implode("\r\n", $pages);

    return render($this, $response, 'member.twig', array('users' => $users, 'curpage' => $page, 'pages' => $pageStrs));
});

$app->get('/adconfig', function (Request $request, Response $response)
{
    global $_SESSION;    
    if (!isset($_SESSION['user']))
    {
        return $response->withRedirect('/login', 302);
    }
    $user = $_SESSION['user'];
    if ($user['type'] != 0)
    {
        return $response->withStatus(404);
    }
    render($this, $response, 'adconfig.twig');    
});

$app->get('/madconfig', function (Request $request, Response $response)
{
    global $_SESSION;    
    if (!isset($_SESSION['user']))
    {
        return $response->withRedirect('/login', 302);
    }
    return render($this, $response, 'madconfig.twig');    
});

$app->get('/domainconfig', function (Request $request, Response $response)
{
    global $_SESSION;    
    if (!isset($_SESSION['user']))
    {
        return $response->withRedirect('/login', 302);
    } 
   $user = $_SESSION['user'];
    if ($user['type'] != 0)
    {
        return $response->withStatus(404);
    }
    return render($this, $response, 'domainconfig.twig');    
});

$app->get('/login', function (Request $request, Response $response)
{
    global $_SESSION;
    $user = $_SESSION['user'];
    if (isset($user))
    {
        return $response->withRedirect('/', 302);
    }
    $this->view->render($response, 'login.twig');        
});

$app->get('/logout', function (Request $request, Response $response)
{
    global $_SESSION;
    unset($_SESSION['user']);
    return $response->withRedirect('/login', 302);
});

$app->get('/reg', function (Request $request, Response $response)
{
    $this->view->render($response, 'register.twig');
});

$app->post('/login', function (Request $request, Response $response)
{
    global $_SESSION;
    $username = $request->getParsedBody()['username'];
    $passwd = $request->getParsedBody()['passwd'];
    $resp = array();
    if (empty($username) || empty($passwd))
    {
        $resp['code'] = 3;
    }
    else 
    {
        $data = $this->db->select('users', '*', ['username[=]' => $username]);
        if (!isset($data) || count($data) == 0)
        {
            $resp['code'] = 2;  // 用户不存在
        }
        else
        {
            $pwdMd5 = md5($passwd);
            $userData = $data[0];
            if ($userData['password'] == $pwdMd5)
            {
                if ($userData['state'] != 0)
                {
                    $resp['code'] = 4;                                                    
                }
                else
                {
                    $resp['code'] = 0;
                    $_SESSION['user'] = $userData;
                }
            }
            else
            {
                $resp['code'] = 1;
            }
        }
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