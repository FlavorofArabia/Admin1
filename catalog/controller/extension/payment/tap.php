<?php
class ControllerExtensionPaymentTap extends Controller {
	public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');

		if (!$this->config->get('payment_tap_test')) {
			$data['action'] = 'https://www.gotapnow.com/webpay.aspx';
		} 
		else {
			$data['action'] = 'http://live.gotapnow.com/webpay.aspx';
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$data['APIKey'] = $this->config->get('payment_tap_apikey');
		
		$data['MerchantID'] = $this->config->get('payment_tap_merchantid');
		
		$data['Username'] = $this->config->get('payment_tap_username');
		$data['Password'] = $this->config->get('payment_tap_password');
		
		
		$data['itemprice1'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['itemname1'] ='Order ID - '.$order_info['order_id'];
		$data['currencycode'] = $order_info['currency_code'];
		$data['ordid'] = $order_info['order_id'];

		$data['cstemail'] = $order_info['email'];
		$data['cstname'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
		$data['cstmobile'] = $order_info['telephone'];
		$data['cntry'] = $order_info['payment_iso_code_2'];

		$data['returnurl'] = $this->url->link('extension/payment/tap/callback', 'hashcd=' . md5($order_info['order_id'] . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . $order_info['currency_code'] . $this->config->get('tap_password')));
		return $this->load->view('extension/payment/tap', $data);
	}

	public function callback() {
		$this->load->language('payment/tap');

		if (isset($this->request->get['trackid'])) {
			$order_id = $this->request->get['trackid'];
		} else {
			$order_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			$error = '';
			
			$key = $this->config->get('payment_tap_merchantid');
			$refid = $this->request->get['ref'];
			
			$str = 'x_account_id'.$key.'x_ref'.$refid.'x_resultSUCCESSx_referenceid'.$order_id.'';
			$hashstring = hash_hmac('sha256', $str, '1tap7');
			$responsehashstring=$this->request->get['hash'];
				
			// if ($hashstring != $responsehashstring) {
			// 	$error = $this->language->get('text_unable');
			// } else if ($this->request->get['result'] != 'SUCCESS') {
			// 	$error = $this->language->get('text_declined');
			// }
		} else {
			$error = $this->language->get('text_unable');
		}

		if ($error) {
			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_failed'),
				'href' => $this->url->link('checkout/success')
			);

			$data['heading_title'] = $this->language->get('text_failed');

			$data['text_message'] = sprintf($this->language->get('text_failed_message'), $error, $this->url->link('information/contact'));

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success')) {
				$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/common/success', $data));
			} else {
				$this->response->setOutput($this->load->view('common/success', $data));
			}
		} else {
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_tap_order_status_id'));

			$this->response->redirect($this->url->link('checkout/success'));
		}
	}
}