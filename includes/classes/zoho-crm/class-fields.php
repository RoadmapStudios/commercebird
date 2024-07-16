<?php
final class ZcrmFields {

	/**
	 * Collects Zoho prices.
	 *
	 * @return void
	 */
	public function zoho_prices_collect(): void {
		// $this->verify();
		$price_list_class = new ImportPricelistClass();
		$prices = $price_list_class->zi_get_all_pricelist();
		// $this->response = wp_list_pluck( $prices, 'name', 'pricebook_id' );
		// $this->serve();
	}
}