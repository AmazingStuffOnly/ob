<?php

error_reporting(null);

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

require_once 'vendor/autoload.php';

$app = new \Slim\App(
    [
        'settings' => [
            'displayErrorDetails' => false,
            'determineRouteBeforeAppMiddleware' => true,
        ]
    ]
);

$container = $app->getContainer();

$container['phpErrorHandler'] = function ($container) {
    return function (Request $request, Response $response, $error) use ($container) {
        $response->getBody()->rewind();
        return $response->withStatus(500)
            ->withJson(
                [
                    'error' => [
                        'message' => 'An unknown error occurred, please contact system administrator'
                    ]
                ]
            );
    };
};

$app->add(
    function (Request $request, Response $response, callable $next) {
        /**
         * @var $handledResponse \Slim\Http\Response
         */
        $handledResponse = $next($request, $response);

        $statusCode = $handledResponse->getStatusCode();
        switch (true) {
            case $handledResponse->isClientError():
            case $handledResponse->isServerError():
                return $response->withStatus($statusCode)
                    ->withJson(
                        [
                            'error' => [
                                'message' => ucfirst(strtolower($handledResponse->getReasonPhrase()))
                            ]
                        ]
                    );
                break;
        }

        return $handledResponse;
    }
);

$app->post(
    '/api/bet',
    function (Request $request, Response $response) {
        return $response->withStatus(418);
    }
);

$app->any(
    '/coffee',
    function (Request $request, Response $response) {
        return $response->withStatus(418);
    }
);

$app->run();
