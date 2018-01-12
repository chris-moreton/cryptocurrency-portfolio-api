<?php

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

$router->get('/', function () use ($router) {

    $api = new Binance\API(getenv('BINANCE_API_KEY'), getenv('BINANCE_API_SECRET'));
    $ticker = $api->prices();

    $binanceBalances = array_filter(
                    $api->balances($ticker),
                    function ($balance) {
                        return $balance['btcTotal'] > 0;
                    }
                );

    $btcTotal = 0;
    foreach ($binanceBalances as $balance) {
        $btcTotal += $balance['btcTotal'];
    }

    $coinbaseConfiguration = Coinbase\Wallet\Configuration::apiKey(getenv('COINBASE_API_KEY'), getenv('COINBASE_API_SECRET'));
    $coinbaseClient = Coinbase\Wallet\Client::create($coinbaseConfiguration);

    $exchangeRates = $coinbaseClient->getExchangeRates(['currency' => 'BTC']);

    return [
        'usd_value' => $btcTotal * $exchangeRates['rates']['USD'],
        'gbp_value' => $btcTotal * $exchangeRates['rates']['GBP'],
        'binance' => $binanceBalances,
    ];
});
