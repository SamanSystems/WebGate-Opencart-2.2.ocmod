<?php
class ControllerPaymentZarinpal extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/zarinpal');

		$this->document->setTitle($this->language->get('doc_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('zarinpal', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_pin'] = $this->language->get('entry_pin');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['pin'])) {
			$data['error_pin'] = $this->error['pin'];
		} else {
			$data['error_pin'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/zarinpal', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('payment/zarinpal', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], true);

		if (isset($this->request->post['zarinpal_pin'])) {
			$data['zarinpal_pin'] = $this->request->post['zarinpal_pin'];
		} else {
			$data['zarinpal_pin'] = $this->config->get('zarinpal_pin');
		}

		if (isset($this->request->post['zarinpal_order_status_id'])) {
			$data['zarinpal_order_status_id'] = $this->request->post['zarinpal_order_status_id'];
		} else {
			$data['zarinpal_order_status_id'] = $this->config->get('zarinpal_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['zarinpal_status'])) {
			$data['zarinpal_status'] = $this->request->post['zarinpal_status'];
		} else {
			$data['zarinpal_status'] = $this->config->get('zarinpal_status');
		}

		if (isset($this->request->post['zarinpal_sort_order'])) {
			$data['zarinpal_sort_order'] = $this->request->post['zarinpal_sort_order'];
		} else {
			$data['zarinpal_sort_order'] = $this->config->get('zarinpal_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/zarinpal', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/zarinpal')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['zarinpal_pin']) {
			$this->error['pin'] = $this->language->get('error_pin');
		}

		return !$this->error;
	}
}
?>