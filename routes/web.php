<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/*$router->get('/', function () use ($router) {
    return $router->app->version();
});*/

$router->get('', function () {
    return 'BPC api middleware';  
});
$router->get('/teste', 'ApiGate@teste');
$router->get('/auth', 'ApiGate@auth');
$router->get('/domain/{domain}', 'ApiGate@domain');
$router->get('/domains/objectives', 'ApiGate@objectives');
$router->get('/domains/professions', 'ApiGate@professions');
$router->get('/financialInstitution', 'ApiGate@financialInstitution');
$router->get('/locations/{cep}', 'ApiGate@locations');
$router->get('/proposals', 'ApiGate@proposals');
$router->post('/borrower', 'ApiGate@borrower');
$router->post('/borrower/proposal', 'ApiGate@borrowerProposal');
$router->post('/borrower/meta', 'ApiGate@borrowerMeta');
$router->post('/borrower/metas', 'ApiGate@borrowerMetas');

