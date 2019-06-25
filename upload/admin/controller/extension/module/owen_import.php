<?php

/*
 * Import Module Controller for Owen Products
 */

class ControllerExtensionModuleOwenImport extends Controller {

  protected function validate() {
    if (!$this->user->hasPermission('modify', 'extension/module/owen_import')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    return !$this->error;
  }

	public function index()
	{
		$this->load->language('extension/module/owen_import');

		$this->document->setTitle($this->language->get('heading_title'));


		$data['heading_title'] = $this->language->get('heading_title');
		$data['entry_import'] = $this->language->get('entry_import');


		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/owen_import', 'token=' . $this->session->data['user_token'], true)
		);

		$data['import_action'] = $this->url->link('extension/module/owen_import', 'token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/owen_import', $data));
	}

  public function install() {
    
  }

  public function uninstall() {
    
  }

}
