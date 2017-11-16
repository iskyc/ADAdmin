<?php
/** @var array $config */
$config['displayErrorDetails'] = true; //开启slim debug模式

$config['db']['type'] = "mysql";
$config['db']['host'] = "localhost";
$config['db']['user'] = "root";
$config['db']['pass'] = "123456";
$config['db']['dbname'] = "adadmin";
$config['db']['charset'] = "utf8";

//1. 会员注册，删除，修改
// 2. 自定义广告模板标题，广告管理
// 3. 绑定域名N条域名供会员使用
$menu_item_list = array(
    array('name' => '会员管理', 'type' => '0', 'url' => '/member', 'id' => 'member'),
    array('name' => '广告管理', 'type' => '0', 'url' => '/adconfig', 'id' => 'adconfig'),
    array('name' => '域名管理', 'type' => '0', 'url' => '/domainconfig', 'id' => 'domainconfig'),
    array('name' => '广告管理', 'type' => '1', 'url' => '/madconfig', 'id' => 'madconfig'),
);