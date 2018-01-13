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

use BlockCypher\Auth\SimpleTokenCredential;
use BlockCypher\Rest\ApiContext;
use BlockCypher\Client\AddressClient;

function getBalance($currency) {

    $token = getenv('BLOCKCYPHER_TOKEN');
    
    $config = array(
        'mode' => 'sandbox',
        'log.LogEnabled' => true,
        'log.FileName' => '/home/vagrant/Code/cryptocurrency-portfolio-api/storage/logs/BlockCypher.log',
        'log.LogLevel' => 'ERROR', // PLEASE USE 'INFO' LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
        'validation.level' => 'log',
    );

    $apiContext = ApiContext::create(
        'main', strtolower($currency), 'v1',
        new SimpleTokenCredential($token),
        $config
    );

    try {
        $addressClient = new AddressClient($apiContext);
        $address = $addressClient->get(getenv(strtoupper($currency) . '_ADDRESS'));
    } catch (Exception $e) {
        dd($e->getMessage());
    }

    return $address->getBalance();
}

$router->get('/', function () use ($router) {

    $wallets = [];
    $currencies = ['ETH'];
    foreach ($currencies as $currency) {
        $wallets[strtoupper($currency)] = getBalance($currency);
    }

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

    $coinbaseAccount = $coinbaseClient->getPrimaryAccount();
    $coinbaseDeposits = $coinbaseClient->getAccountDeposits($coinbaseAccount);

    return [
        'usd_value' => $btcTotal * $exchangeRates['rates']['USD'],
        'gbp_value' => $btcTotal * $exchangeRates['rates']['GBP'],
        'binance' => $binanceBalances,
        'coinbase' => $coinbaseDeposits,
        'wallets' => $wallets,
    ];
});
