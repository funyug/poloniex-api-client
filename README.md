# poloniex-api-client
Api client for Poloniex.com. Based on PHP wrapper by compcentral. Extended to have more functionality.


# Installation
composer require funyug/poloniex

# Usage
use funyug/Poloniex;

$api = new Poloniex("Api key","Api secret");

Get Balance:

$api->get_total_btc_balance(['BTC']);

Get Order book:

$api->get_order_book("BTC_ETH");

Place Buy Order:

$api->buy('BTC_ETH',$buy_price,$amount);

Place Sell Order:

$api->sell('BTC_ETH', $sell_price, $number_of_coins);