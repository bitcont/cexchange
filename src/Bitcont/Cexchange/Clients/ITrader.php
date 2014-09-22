<?php

namespace Bitcont\Cexchange\Clients;


interface ITrader
{

	/**
	 * Creates market buy order.
	 *
	 * @param int $fiatAmount Fiat amount in cents
	 * @return string Id of the newly created order
	 */
	public function marketBuy($fiatAmount);
}

