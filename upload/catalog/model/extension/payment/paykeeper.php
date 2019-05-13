<?php
class ModelExtensionPaymentPaykeeper extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/paykeeper');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('paykeeper_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		$method_data = array();

		$method_data = array(
			'code'       => 'paykeeper',
			'title'      => $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('paykeeper_sort_order')
		);

		return $method_data;
	}
}
