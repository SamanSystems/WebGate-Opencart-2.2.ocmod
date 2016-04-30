<?php
/** written by Mohsen Ranjbar Helan (mimrahe) for Zarinpal Company **/
/** support by Zarinpal.com **/

class ControllerPaymentZarinpal extends Controller {
	public function index() {
		$this->load->language('payment/zarinpal');
		
		$data['text_connect'] = $this->language->get('text_connect');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_wait'] = $this->language->get('text_wait');
		
		$data['button_confirm'] = $this->language->get('button_confirm');

		return $this->load->view('payment/zarinpal', $data);
	}

	public function confirm() {
		$this->load->language('payment/zarinpal');

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$amount = $this->correctAmount($order_info);
		
		$data['return'] = $this->url->link('checkout/success', '', true);
		$data['cancel_return'] = $this->url->link('checkout/payment', '', true);
		$data['back'] = $this->url->link('checkout/payment', '', true);
		
		$MerchantID = $this->config->get('zarinpal_pin');  	//Required
		$Amount = $amount; 									//Amount will be based on Toman  - Required
		$Description = $this->language->get('text_order_no') . $order_info['order_id']; // Required
		$Email = isset($order_info['email']) ? $order_info['email'] : ''; 	// Optional
		$Mobile = isset($order_info['fax']) ? $order_info['fax'] : $order_info['telephone']; 	// Optional
		$data['order_id'] = $this->encryption->encrypt($this->session->data['order_id']);
		$CallbackURL = $this->url->link('payment/zarinpal/callback', 'order_id=' . $data['order_id'], true);  // Required

		$parameters = array(
			'MerchantID' 	=> $MerchantID,
			'Amount' 		=> $Amount,
			'Description' 	=> $Description,
			'Email' 		=> $Email,
			'Mobile' 		=> $Mobile,
			'CallbackURL' 	=> $CallbackURL
			);

		$requestResult = $this->zpRequest($parameters);

		if(!$requestResult){
			$json = array();
			$json['error']= $this->language->get('error_cant_connect');				
		} elseif($requestResult->Status == 100) {
			$data['action'] = "https://www.zarinpal.com/pg/StartPay/" . $requestResult->Authority;
			$json['success']= $data['action'];
		} else {
			$json = $this->checkState($requestResult->Status);
		}

		$this->response->addHeader('Content-Type: application/json');

		return $this->response->setOutput(json_encode($json));
	}



	public function callback() {
		
		$this->load->language('payment/zarinpal');

		if ($this->session->data['payment_method']['code'] != 'zarinpal') {
			return false;
		}


		$this->document->setTitle($this->language->get('text_title'));

		$data['heading_title'] = $this->language->get('text_title');
		$data['text_results'] = $this->language->get('text_results');
		$data['results'] = "";

			//breadcrumbs
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'), 
			'href' => $this->url->link('common/home', '', true)
			);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_title'), 
			'href' => $this->url->link('payment/zarinpal/callback', '', true)
			);

		try {
			if($this->request->get['Status'] != 'OK')
				throw new Exception($this->language->get('error_verify'));

			$order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0;
			$this->load->model('checkout/order');
			$order_info = @$this->model_checkout_order->getOrder($order_id);

			if (!$order_info)
				throw new Exception($this->language->get('error_order_id'));

			$authority = $this->request->get['Authority'];
			$amount = $this->correctAmount($order_info);

			$verifyResult = $this->verifyPayment($authority, $amount);

			if (!$verifyResult)
				throw new Exception($this->language->get('error_connect_verify'));

			switch ( array_keys($verifyResult)[0] ) {
				case 'RefID': // success
					$comment = $this->language->get('text_results') . $verifyResult['RefID'];
					$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('zarinpal_order_status_id'), $comment, true);

					$data['error_warning'] = NULL;
					$data['results'] = $verifyResult['RefID'];
					$data['button_continue'] = $this->language->get('button_complete');
					$data['continue'] = $this->url->link('checkout/success');

						break;

				case 'Status': // error with error status
					throw new Exception($this->checkState($verifyResult['Status'])['error']);
						break;
			}

		} catch (Exception $e) {
			$data['error_warning'] = $e->getMessage();
			$data['button_continue'] = $this->language->get('button_view_cart');
			$data['continue'] = $this->url->link('checkout/cart');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('payment/zarinpal_confirm', $data));
	}

	private function correctAmount($order_info)
	{
		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$amount = round($amount);
		$amount = $this->currency->convert($amount, $order_info['currency_code'], "TOM");
		return (int)$amount;
	}

	private function zpRequest($parameters){
		// URL also Can be https://ir.zarinpal.com/pg/services/WebGate/wsdl
		try{
			$client = new SoapClient('https://ir.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));
			return $client->PaymentRequest($parameters);

		} catch(SoapFault $e) {
			return false;
		}
	}

	private function zpVerification($context){
		// URL also Can be https://ir.zarinpal.com/pg/services/WebGate/wsdl
		try {
			$client = new SoapClient('https://ir.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 

			return $client->PaymentVerification($context);

		} catch(SoapFault $e) {
			return false;
		}		
	}

	private function checkState($status) {
		$json = array();
		$json['error'] = $this->language->get('error_status_undefined');

		if ($this->language->get('error_status_' . $status) != 'error_status_' . $status ) {
			$json['error'] = $this->language->get('error_status_' . $status);
		}

		return $json;
	}


	private function verifyPayment($authority, $amount){

		$data['MerchantID'] = $this->config->get('zarinpal_pin');
		$context = array(
			'MerchantID'	 => $data['MerchantID'],
			'Authority' 	 => $authority,
			'Amount'	 => $amount
			);
		$verifyResult = $this->zpVerification($context);

		if(!$verifyResult) {
			// echo  $this->language->get('error_cant_connect');
			return false;
		} elseif($verifyResult->Status == 100) {
			return ['RefID' => $verifyResult->RefID];
		} else {
			return ['Status' => $verifyResult->Status];
		}
	}
}
?>