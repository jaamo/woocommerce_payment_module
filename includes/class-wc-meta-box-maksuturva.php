<?php
/**
 * WooCommerce Maksuturva Payment Gateway
 *
 * @package WooCommerce Maksuturva Payment Gateway
 */

/**
 * Maksuturva Payment Gateway Plugin for WooCommerce 2.x
 * Plugin developed for Maksuturva
 * Last update: 08/03/2016
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-payment-checker-maksuturva.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Meta_Box_Maksuturva.
 *
 * Adds a meta box to the order page.
 *
 * @since 2.0.0
 */
class WC_Meta_Box_Maksuturva {

	/**
	 * The text domain.
	 *
	 * @since 2.0.2
	 *
	 * @var string the text domain string.
	 */
	private static $td;

	/**
	 * Output the meta box.
	 *
	 * @param WP_Post $post The post.
	 * @param array   $args Arguments passed to the output function.
	 */
	public static function output( $post, $args ) {
		try {
			if ( ! isset( $args['args']['gateway'] ) ) {
				throw new WC_Gateway_Maksuturva_Exception( 'No gateway given to meta-box.' );
			}
			$gateway  = $args['args']['gateway'];
			self::$td = $gateway->td;
			$order    = wc_get_order( $post );
			$payment  = new WC_Payment_Maksuturva( $order->get_id() );
		} catch ( WC_Gateway_Maksuturva_Exception $e ) {
			// If the payment was not found, it probably means that the order was not paid with Maksuturva.
			return;
		}

		/** @var WC_Gateway_Maksuturva $gateway */
		$gateway->render( 'meta-box', 'admin', array( 'message' => self::get_messages( $payment ) ) );
	}

	/**
	 * Get messages.
	 *
	 * Returns the messages for the given payment.
	 *
	 * @param WC_Payment_Maksuturva $payment The Maksuturva payment object.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private static function get_messages( $payment ) {

		if ($payment->is_delayed() || $payment->is_pending()) {
			( new WC_Payment_Checker_Maksuturva() )->check_payment( $payment );
		}

		switch ( $payment->get_status() ) {
			case WC_Payment_Maksuturva::STATUS_COMPLETED:
				$msg = __( 'The payment is confirmed by Maksuturva', self::$td );
				break;

			case WC_Payment_Maksuturva::STATUS_CANCELLED:
				$msg = __( 'The payment is canceled by Maksuturva', self::$td );
				break;

			case WC_Payment_Maksuturva::STATUS_ERROR:
				$msg = __( 'The payment could not be confirmed by Maksuturva, please check manually', self::$td );
				break;

			case WC_Payment_Maksuturva::STATUS_DELAYED:
			case WC_Payment_Maksuturva::STATUS_PENDING:
			default:
				$msg = __( 'The payment is still waiting for confirmation by Maksuturva', self::$td );
				break;
		}

		return $msg;
	}
}
