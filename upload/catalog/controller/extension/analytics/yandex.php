<?php
class ControllerExtensionAnalyticsYandex extends Controller {
    public function index() {
		return html_entity_decode($this->config->get('analytics_yandex_code'), ENT_QUOTES, 'UTF-8');
	}
}