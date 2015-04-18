<?php
/**
 * A simple exec file can exec git pull from gitlab by Web Hooks
 * 
 * @author *Chen Lin
 */
//Deploy dir
define('__DEPLOY_DIR__', __DIR__ . '/sync-deploy');

//Prepare error var
$error = '';

//Prepare payload array
$payload = array();

//Clone indicator
$clone = false;

//Get param from POST request and get the repo info
$fileData = '---' . date('Y-m-d H:i:s') . '---' . PHP_EOL;
$fileData .= '----------' . PHP_EOL;
$fileData .= 'Repo check' . PHP_EOL;
$fileData .= '----------' . PHP_EOL;

if (!empty($_REQUEST['payload'])){
    //application/x-www-form-urlencodded
    $payload = json_decode($_REQUEST['payload'],true);
    $fileData .= 'Payload decoded[x-www-form-urlencodded]:' . PHP_EOL . var_dump($payload) . PHP_EOL;
} else {
    //application/json
    $payload = json_decode(file_get_contents("php://input"), true);
    $fileData .= 'Payload decoded[json]:' . PHP_EOL . var_dump($payload) . PHP_EOL;
}

$repo_name = substr(strrchr($payload['repository']['full_name'], '/'), 1);

$repo_clone_url = $payload['repository']['clone_url'];

//Cache project (clone if not exists or sync)
if (!is_dir(__DEPLOY_DIR__)){
    $fileData .= '----------' . PHP_EOL;
    $fileData .= 'Create deploy dir' . PHP_EOL;
    $fileData .= '----------' . PHP_EOL;
    mkdir(__DEPLOY_DIR__, 0777, true);

    $fileData .= '----------' . PHP_EOL;
    $fileData .= 'Clone' . PHP_EOL;
    $fileData .= '----------' . PHP_EOL;
    $command = 'git clone ' . $repo_clone_url . ' ' . __DEPLOY_DIR__;
    exec($command, $result);
    $fileData .= 'Result: ' . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $result) . PHP_EOL . PHP_EOL;
    $clone = true;
}

$fileData .= '----------' . PHP_EOL;
$fileData .= 'Check out & sync' . PHP_EOL;
$fileData .= '----------' . PHP_EOL;

//If dir is already created, try to sync it
if (is_dir(__DEPLOY_DIR__) && !$clone){
    $fileData .= '----------' . PHP_EOL;
    $fileData .= 'Sync' . PHP_EOL;
    $fileData .= '----------' . PHP_EOL;
    $command = 'cd ' . __DEPLOY_DIR__ . ' && git pull';
    $fileData .= 'Executing: ' . $command . PHP_EOL;
    exec($command, $result);
    $fileData .= 'Result: ' . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $result) . PHP_EOL . PHP_EOL;
}

$fileData .= '** SYNC FINISHED **' . PHP_EOL;
    

//Write the log file
file_put_contents('githooklog.txt', $fileData . PHP_EOL, FILE_APPEND);
