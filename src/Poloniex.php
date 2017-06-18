<?php
namespace Funyug;

class Poloniex {
    protected $api_key;
    protected $api_secret;
    protected $trading_url = "https://poloniex.com/tradingApi";
    protected $public_url = "https://poloniex.com/public";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    private function query(array $req = array()) {
        // API settings
        $key = $this->api_key;
        $secret = $this->api_secret;

        // generate a nonce to avoid problems with 32bit systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac('sha512', $post_data, $secret);

        // generate the extra headers
        $headers = array(
            'Key: '.$key,
            'Sign: '.$sign,
        );

        // curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT,
                'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
            );
        }
        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);

        if ($res === false) throw new \Exception('Curl error: '.curl_error($ch));
        //echo $res;
        $dec = json_decode($res, true);
        if (!$dec){
            //throw new Exception('Invalid data: '.$res);
            return false;
        }else{
            return $dec;
        }
    }

    protected function retrieveJSON($URL) {
        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 10
            )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json = json_decode($feed, true);
        return $json;
    }

    public function get_balances() {
        return $this->query(
            array(
                'command' => 'returnBalances'
            )
        );
    }

    public function get_open_orders($pair) {
        return $this->query(
            array(
                'command' => 'returnOpenOrders',
                'currencyPair' => strtoupper($pair)
            )
        );
    }

    public function get_my_trade_history($pair) {
        return $this->query(
            array(
                'command' => 'returnTradeHistory',
                'currencyPair' => strtoupper($pair)
            )
        );
    }

    public function buy($pair, $rate, $amount, $order_settings = []) {
        $settings = array(
            'command' => 'buy',
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount
        );
        $final_query = array_merge($settings,$order_settings);
        return $this->query(
            $final_query
        );
    }

    public function sell($pair, $rate, $amount, $order_settings = []) {
        $settings = array(
            'command' => 'sell',
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount
        );
        $final_query = array_merge($settings,$order_settings);
        return $this->query(
          $final_query
        );
    }

    public function cancel_order($pair, $order_number) {
        return $this->query(
            array(
                'command' => 'cancelOrder',
                'currencyPair' => strtoupper($pair),
                'orderNumber' => $order_number
            )
        );
    }

    public function withdraw($currency, $amount, $address) {
        return $this->query(
            array(
                'command' => 'withdraw',
                'currency' => strtoupper($currency),
                'amount' => $amount,
                'address' => $address
            )
        );
    }

    public function get_trade_history($pair) {
        $trades = $this->retrieveJSON($this->public_url.'?command=returnTradeHistory&currencyPair='.strtoupper($pair));
        return $trades;
    }

    public function get_order_book($pair) {
        $orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair));
        return $orders;
    }

    public function get_volume() {
        $volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
        return $volume;
    }

    public function get_ticker($pair = "ALL") {
        $pair = strtoupper($pair);
        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        if($pair == "ALL"){
            return $prices;
        }else{
            $pair = strtoupper($pair);
            if(isset($prices[$pair])){
                return $prices[$pair];
            }else{
                return array();
            }
        }
    }

    public function get_trading_pairs() {
        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        return array_keys($tickers);
    }

    public function get_total_btc_balance($exclude = []) {
        $total = 0;
        $balances = $this->query(
            array(
                'command' => 'returnCompleteBalances'
            )
        );
        foreach($balances as $key=>$balance) {
            if(in_array($key,$exclude)) {
                continue;
            }
            $total = $total + $balance["btcValue"];
        }
        return $total;
    }

    public function get_balance($coin) {
        $balances = $this->get_balances();
        foreach($balances as $key=>$balance)
        {
            if($key == $coin) {
                return $balance;
            }
        }
        return 0.00;
    }

    public function get_btc_balance($coin) {
        $balances = $this->query(
            array(
                'command' => 'returnCompleteBalances'
            )
        );
        foreach($balances as $key=>$balance)
        {
            if($key == $coin) {
                $balance["btcValue"];
            }
        }
        return 0.00;
    }


}
?>