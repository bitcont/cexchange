<?php

namespace Bitcont\Cexchange\Clients\Bitstamp;

use Bitcont\Cexchange\Clients\ITrader,
	Kdyby\Curl\Request,
	Kdyby\CurlCaBundle\CertificateHelper;


class Client implements ITrader
{

	/**
	 * Transaction list url.
	 *
	 * @var string
	 */
	const TRANSACTION_LIST_URL = 'https://www.bitstamp.net/api/user_transactions/';

	/**
	 * Ticker url.
	 *
	 * @var string
	 */
	const TICKER_URL = 'https://www.bitstamp.net/api/ticker/';

	/**
	 * Buy limit order url.
	 *
	 * @var string
	 */
	const BUY_ORDER_URL = 'https://www.bitstamp.net/api/buy/';

	/**
	 * Account balance url.
	 *
	 * @var string
	 */
	const ACCOUNT_BALANCE_URL = 'https://www.bitstamp.net/api/balance/';

	/**
	 * Account balance url.
	 *
	 * @var string
	 */
	const BTC_WITHDRAWAL_URL = 'https://www.bitstamp.net/api/bitcoin_withdrawal/';


	/**
	 * Client ID.
	 *
	 * @var string
	 */
	protected $clientId;

	/**
	 * Api key.
	 *
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Api secret.
	 *
	 * @var string
	 */
	protected $apiSecret;


	/**
	 * @param string $clientId
	 * @param string $apiKey
	 * @param string $apiSecret
	 */
	public function __construct($clientId, $apiKey, $apiSecret)
	{
		$this->clientId = $clientId;
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
	}


	/**
	 * Returns list of transactions.
	 *
	 * @return array
	 */
	public function listTransactions()
	{
		$nonce = $this->getNonce();

		$params = array(
			'key' => $this->apiKey,
			'nonce' => $nonce,
			'signature' => $this->getSignature($nonce)
		);

		$request = new Request(static::TRANSACTION_LIST_URL);
		$request->setTrustedCertificate(CertificateHelper::getCaInfoFile());
		$response = $request->post($params);

		return json_decode($response->getResponse(), TRUE);
	}


	/**
	 * Returns ticker data.
	 *
	 * @return array
	 */
	public function getTicker()
	{
		$request = new Request(static::TICKER_URL);
		$request->setTrustedCertificate(CertificateHelper::getCaInfoFile());
		$response = $request->get();

		return json_decode($response->getResponse(), TRUE);
	}


	/**
	 * Returns account balance data.
	 *
	 * @return array
	 */
	public function getAccountBalance()
	{
		$nonce = $this->getNonce();

		$params = array(
			'key' => $this->apiKey,
			'nonce' => $nonce,
			'signature' => $this->getSignature($nonce)
		);

		$request = new Request(static::ACCOUNT_BALANCE_URL);
		$request->setTrustedCertificate(CertificateHelper::getCaInfoFile());
		$response = $request->post($params);

		return json_decode($response->getResponse(), TRUE);
	}


	/**
	 * Creates limit buy order.
	 *
	 * @param string $btcAmount
	 * @param string $price
	 * @return array The newly created order info
	 */
	public function limitBuy($btcAmount, $price)
	{
		$nonce = $this->getNonce();

		$params = array(
			'key' => $this->apiKey,
			'nonce' => $nonce,
			'signature' => $this->getSignature($nonce),
			'amount' => $btcAmount,
			'price' => $price
		);

		$request = new Request(static::BUY_ORDER_URL);
		$request->setTrustedCertificate(CertificateHelper::getCaInfoFile());
		$response = $request->post($params);

//		print_r(json_decode($response->getResponse(), TRUE));

		return json_decode($response->getResponse(), TRUE);
	}


	/**
	 * Creates market buy order.
	 *
	 * @param string $usdAmount Fiat amount
	 * @return array The newly created order info
	 */
	public function marketBuy($usdAmount)
	{
		// get ask price
		$ticker = $this->getTicker();
		$ask = floatval($ticker['ask']);

		// count btc amount
		$btcAmount = floatval($usdAmount) / $ask;

		// increase the price limit so the order gets matched no matter the price
		$price = $ask * 1.1;

		// create order
		return $this->limitBuy(number_format($btcAmount, 8, '.', ''), number_format($price, 2, '.', ''));
	}


	/**
	 * Withdraws btc.
	 *
	 * @param string $btcAmount
	 * @param string $address
	 * @return array Withdrawal info
	 */
	public function withdrawBtc($btcAmount, $address)
	{
		$nonce = $this->getNonce();

		$params = array(
			'key' => $this->apiKey,
			'nonce' => $nonce,
			'signature' => $this->getSignature($nonce),
			'amount' => $btcAmount,
			'address' => $address
		);

		$request = new Request(static::BTC_WITHDRAWAL_URL);
		$request->setTrustedCertificate(CertificateHelper::getCaInfoFile());
		$response = $request->post($params);

		return json_decode($response->getResponse(), TRUE);
	}


	/**
	 * Generates nonce.
	 *
	 * @return string
	 */
	protected function getNonce()
	{
		$mt = explode(' ', microtime());
		return $mt[1] . substr($mt[0], 2, 6);
	}


	/**
	 * Generates signature.
	 *
	 * @return string
	 */
	protected function getSignature($nonce)
	{
		$message = $nonce . $this->clientId . $this->apiKey;
		return strtoupper(hash_hmac('sha256', $message, $this->apiSecret));
	}
}

