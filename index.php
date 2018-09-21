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
            'db' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'username' => 'root',
                'database' => 'test',
                'password' => 'toor', # omg password in public
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
            ],
            'win' => [
                'amount' => [
                    'max' => 20000
                ]
            ],
            'stakes' => [
                'amount' => [
                    'min' => 0.3,
                    'max' => 10000
                ]
            ],
            'selections' => [
                'count' => [
                    'min' => 1,
                    'max' => 20
                ],
                'amount' => [
                    'min' => 1,
                    'max' => 10000
                ],
            ],
            'errors' => [
                'Unknown error',
                'Betslip structure mismatch',
                'Minimum stake amount is %s',
                'Maximum stake amount is %s',
                'Minimum number of selections is %s',
                'Maximum number of selections is %s',
                'Minimum odds are :min_odds',
                'Maximum odds are :max_odds',
                'Duplicate selection found',
                'Maximum win amount is %s',
                'Your previous action is not finished yet',
                'Insufficient balance',
            ]
        ]
    ]
);

$container = $app->getContainer();

$container['database'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);
    $capsule->setAsGlobal();

    return $capsule;
};

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

$app->any(
    '/',
    function (Request $request, Response $response) {
        /**
         * @var $database \Illuminate\Database\Capsule\Manager
         */
        $database = $this->get('database');
        $settings = $this->get('settings');

        $input = [
            'player_id' => 1,
            'stake_amount' => '123.99',
            'errors' => [],
            'selections' => [
                [
                    'id' => 1,
                    'odds' => '1.601',
                    'errors' => [],
                ],
                [
                    'id' => 2,
                    'odds' => '1.601',
                    'errors' => [],
                ]
            ]
        ];

//        $input = $request->getParams();

        # Checking bet mandatory fields
        switch (true) {
            case empty($input['player_id']):
            case empty($input['stake_amount']):
                $input['errors'][] = $settings['errors'][1]; # Error key can be constants

                # No need to use API resources if we already know that request is bad
                return $response->withJson($input, 400);
                break;
        }

        # Checking selections count
        switch (true) {
            case empty($input['selections']):
            case sizeof($input['selections']) < $settings['selections']['count']['min']:
                $input['errors'][] = sprintf(
                    $settings['errors'][4],
                    $settings['selections']['amount']['min']
                );

                return $response->withJson($input, 400);
            case sizeof($input['selections']) > $settings['selections']['count']['max']:
                $input['errors'][] = sprintf(
                    $settings['errors'][5],
                    $settings['selections']['amount']['max']
                );

                return $response->withJson($input, 400);
        }

        # Checking stake amount structure and size
        switch (true) {
            case filter_var($input['stake_amount'], FILTER_VALIDATE_FLOAT):
            case filter_var($input['stake_amount'], FILTER_VALIDATE_INT):
                # If float lets check it "structure"
                if (round($input['stake_amount']) != $input['stake_amount']) {
                    # We can filter max stack amount here ({1,4}), but we need specific error message, so we don't
                    $stakePattern = '#^([0-9]{1,}\.[0-9]{1,2})$#';
                    $stakeValid = preg_match(
                        $stakePattern,
                        $input['stake_amount']
                    );

                    if (!$stakeValid) {
                        $input['errors'][] = $settings['errors'][1];
                        return $response->withJson($input, 400);
                    }
                }

                switch (true) {
                    case $input['stake_amount'] < $settings['stakes']['amount']['min']:
                        $input['errors'][] = sprintf(
                            $settings['errors'][2],
                            $settings['stakes']['amount']['min']
                        );

                        return $response->withJson($input, 400);
                    case $input['stake_amount'] > $settings['stakes']['amount']['max']:
                        $input['errors'][] = sprintf(
                            $settings['errors'][3],
                            $settings['stakes']['amount']['max']
                        );

                        return $response->withJson($input, 400);
                }

                break;
        }

        # Filtering selections
        $selectionUids = $selectionOdds = [];
        foreach ($input['selections'] as $selection) {
            $selectionUids[] = $selection['id'];
            $selectionOdds[] = $selection['odds'];
        }

        $selectionUidCounts = array_count_values($selectionUids);

        $selectionsHadError = false;
        foreach ($input['selections'] as &$selection) {
            $selectionUid = $selection['id'];

            # Selection UID is duplicate
            if ($selectionUidCounts[$selectionUid] > 1) {
                $selection['errors'][] = $settings['errors'][8];
                $selectionsHadError = true;
            }

            switch (true) {
                case filter_var($selection['odds'], FILTER_VALIDATE_FLOAT):
                case filter_var($selection['odds'], FILTER_VALIDATE_INT):
                    # If float lets check it "structure"
                    if (round($input['stake_amount']) != $input['stake_amount']) {
                        $stakePattern = '#^([0-9]{1,}\.[0-9]{1,3})$#';
                        $stakeValid = preg_match(
                            $stakePattern,
                            $selection['odds']
                        );

                        if (!$stakeValid) {
                            $selection['errors'][] = $settings['errors'][1];
                            $selectionsHadError = true;
                        }
                    }

                    switch (true) {
                        case $selection['odds'] < $settings['selections']['amount']['min']:
                            $selection['errors'][] = sprintf(
                                $settings['errors'][7],
                                $settings['selections']['amount']['min']
                            );

                            $selectionsHadError = true;
                            break;
                        case $selection['odds'] > $settings['selections']['amount']['max']:
                            $selection['errors'][] = sprintf(
                                $settings['errors'][8],
                                $settings['selections']['amount']['max']
                            );

                            $selectionsHadError = true;
                            break;
                    }

                    break;
            }
        }
        unset($selection);

        if ($selectionsHadError) {
            return $response->withJson($input, 400);
        }

        $winAmount = array_reduce(
            $selectionOdds,
            function ($carry, $odd) {
                return $carry * $odd;
            },
            $input['stake_amount']
        );

        if ($winAmount > 20000) {
            $input['errors'][] = sprintf(
                $settings['errors'][9],
                $settings['win']['amount']['max']
            );
            return $response->withJson($input, 400);
        }

        $player = $database::table('player')
            ->where('id', $input['player_id'])
            ->get();

        $balance = 1000;
        if ($player->isEmpty()) {
            $database::table('player')->insert(
                [
                    'id' => $input['player_id'],
                    'balance' => $balance
                ]
            );
        } else {
            $balance = $player->first()->balance;
        }

        if ($balance < $input['stake_amount']) {
            $input['errors'][] = $settings['errors'][11];
            return $response->withJson($input, 400);
        }

        $database::table('player')
            ->where('id', $input['player_id'])
            ->update(
                [
                    'balance' => $balance - $input['stake_amount']
                ]
            );

        $database::table('balance_transaction')
            ->insert(
                [
                    'player_id' => $input['player_id'],
                    'amount' => $balance - $input['stake_amount'],
                    'amount_before' => $balance,
                ]
            );

        $betUid = $database::table('bet')
            ->insertGetId(
                [
                    'stake_amount' => $input['stake_amount']
                ]
            );

        $database::table('bet_selections')
            ->insert(
                array_map(
                    function ($selection) use ($betUid) {
                        return [
                            'bet_id' => $betUid,
                            'selection_id' => $selection['id'],
                            'odds' => $selection['odds'],
                        ];
                    },
                    $input['selections']
                )
            );

        return $response
            ->withStatus(201)
            ->withJson($input);
    }
);

$app->any(
    '/coffee',
    function (Request $request, Response $response) {
        return $response->withStatus(418);
    }
);

$app->run();
