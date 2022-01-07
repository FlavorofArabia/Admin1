<?php
/**
 * payment_method.php
 *
 * Payment method management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestPaymentMethod extends RestController
{

    /*
    * Get payment methods
    */
    public function payments()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get payments
            $this->listPayments();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //save payments information to session
            $post = $this->getPost();
            $this->savePayment($post);

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        return $this->sendResponse();

    }

    public function listPayments()
    {

        $this->language->load('checkout/checkout');

        if (isset($this->session->data['payment_address'])) {

            $this->load->model('account/address');

            // Selected payment methods should be from cart sub total not total!
            $total = $this->cart->getSubTotal();

            $this->load->model('setting/extension');

            $results = $this->model_setting_extension->getExtensions('payment');

            $recurring = $this->cart->hasRecurringProducts();

            // Payment Methods
            $method_data = array();

            foreach ($results as $result) {
                if ($this->config->get('payment_' . $result['code'] . '_status')) {
                    $this->load->model('extension/payment/' . $result['code']);

                    $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $total);

                    if ($method) {
                        if ($recurring) {
                            if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
                                $method_data[$result['code']] = $method;
                            }
                        } else {
                            $method_data[$result['code']] = $method;
                        }
                    }
                }
            }

            $sort_order = array();

            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $method_data);

            $this->session->data['payment_methods'] = $method_data;
        } else {
            $this->json['error'][] = "Missing payment address";
        }


        if (empty($this->session->data['payment_methods'])) {
            $this->json['error'][] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
        }

        if (isset($this->session->data['payment_methods'])) {
            $data['payment_methods'] = $this->session->data['payment_methods'];

            $data['payment_methods'] = array_values($data['payment_methods']);
        } else {
            $data['payment_methods'] = array();
        }

        $this->json["data"] = $data;

        if($this->includeMeta) {
            $this->response->addHeader('X-Total-Count: ' . count($data['payment_methods']));
            $this->response->addHeader('X-Pagination-Limit: '.count($data['payment_methods']));
            $this->response->addHeader('X-Pagination-Page: 1');
//            $data = $data['payment_methods'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => count($data),
//                'pagenumber'    => 1,
//                'pagesize'      => count($data),
//                'items'         => $data
//            );
        }
    }

    public function savePayment($data)
    {
        $this->language->load('checkout/checkout');
        $this->language->load('checkout/cart');


        if (!isset($this->session->data['payment_address'])) {
            $this->json['error'][] = "Empty payment address";
        } else {

            // Validate cart has products and has stock.
            if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                $this->json['error'][] = "Your cart is empty or a product is out of stock";
            }

            // Validate minimum quantity requirments.
            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $this->json['error'][] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
                    break;
                }
            }
        }

        if (empty($this->json['error'])) {
            if (!isset($data['payment_method'])) {

                $this->json['error'][] = $this->language->get('error_payment');
            } else {

                if (!isset($this->session->data['payment_methods'][$data['payment_method']])) {

                    $this->json['error'][] = $this->language->get('error_payment');
                }
            }

//            if ($this->config->get('config_checkout_id')) {
//                $this->load->model('catalog/information');
//
//                $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
//
//                if ($information_info && !isset($data['agree'])) {
//                    $this->json['error'][] = sprintf($this->language->get('error_agree'), $information_info['title']);
//                }
//            }

            if (empty($this->json['error'])) {

                $this->session->data['payment_method'] = $this->session->data['payment_methods'][$data['payment_method']];

                $this->session->data['comment'] = ' '; //strip_tags($data['comment']);

                $this->session->data['agree'] = 1 ; //$data['agree'];
            }
        }
    }
}