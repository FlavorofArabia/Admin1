<?php
/**
 * shipping_method.php
 *
 * Shipping method management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestShippingMethod extends RestController
{

    /*
    * Get shipping methods
    */
    public function shippingmethods()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get shipping methods
            $this->listShippingMethods();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //save shipping information to session
            $post = $this->getPost();
            $this->saveShippingMethod($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        return $this->sendResponse();

    }

    public function listShippingMethods()
    {
        $this->load->language('extension/total/shipping');

        // Validate if shipping is required. If not the customer should not have reached this page.
        if (!$this->cart->hasShipping()) {
            $this->json['error'][] = "The customer should not have reached this page, because shipping is not required.";
            return false;
        }

        if (isset($this->session->data['shipping_address'])) {
            $this->language->load('checkout/checkout');
            // Shipping Methods
            $method_data = array();

            $this->load->model('setting/extension');

            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);

                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

                    if ($quote) {
                        $method_data[$result['code']] = array(
                            'title' => $quote['title'],
                            'quote' => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error' => $quote['error']
                        );
                    }
                }
            }

            $sort_order = array();

            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $method_data);

            $this->session->data['shipping_methods'] = $method_data;

        } else {
            $this->json['error'][] = "Missing shipping address";
        }

        if (empty($this->session->data['shipping_methods'])) {
            $this->json['error'][] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
        }

        $data = array();

        if(empty($this->json['error'])){
            if (isset($this->session->data['shipping_methods'])) {
                $data['shipping_methods'] = $this->session->data['shipping_methods'];

                $data['shipping_methods'] = array_values($data['shipping_methods']);

                foreach ($data['shipping_methods'] as &$method){
                    $method['quote'] = array_values($method['quote']);
                }

            } else {
                $data['shipping_methods'] = array();
            }

            if (isset($this->session->data['shipping_method']['code'])) {
                $data['code'] = $this->session->data['shipping_method']['code'];
            } else {
                $data['code'] = '';
            }

            if (isset($this->session->data['comment'])) {
                $data['comment'] = $this->session->data['comment'];
            } else {
                $data['comment'] = '';
            }
        }

        $this->json["data"] = $data;

        if($this->includeMeta) {
            $this->response->addHeader('X-Total-Count: ' . count($data['shipping_methods']));
            $this->response->addHeader('X-Pagination-Limit: '.count($data['shipping_methods']));
            $this->response->addHeader('X-Pagination-Page: 1');
//            $data = $data['shipping_methods'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => count($data),
//                'pagenumber'    => 1,
//                'pagesize'      => count($data),
//                'items'         => $data
//            );
        }
    }

    public function saveShippingMethod($post)
    {

        $this->language->load('checkout/checkout');
        $this->language->load('checkout/cart');


        // Validate if shipping is required. If not the customer should not have reached this page.

        if (!$this->cart->hasShipping()) {
            $this->json['error'][] = "The customer should not have reached this page, because shipping is not required.";
            return false;
        }

        if (!isset($this->session->data['shipping_address'])) {
            $this->json['error'][] = "Empty shipping address";
            return false;
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->json['error'][] = "Your cart is empty or a product is out of stock";
            return false;
        }

        // Validate minimum quantity requirements.
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

        if (!isset($post['shipping_method'])) {
            $this->json['error'][] = $this->language->get('error_shipping');
        } else {
            $shipping = explode('.', $post['shipping_method']);

            if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $this->json['error'][] = $this->language->get('error_shipping');
            }
        }

        if (empty($this->json['error'])) {
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            $this->session->data['comment'] = strip_tags($post['comment']);
        }
    }
}