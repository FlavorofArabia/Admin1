<?php

require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestUtility extends RestController
{
    private $error;

    public function address()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get payment addresses
            $this->json["data"]= $this->listAddresses();

        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //save payment address information to session
            $post = $this->getPost();

            $existing = false;

            if (isset($this->request->get['existing']) && !empty($this->request->get['existing'])) {
                $existing = true;
            }

            $this->saveAddress($post, $existing);

            if (empty($this->json['error'])){
                list($data['payment'] , $data['shipping']) = $this->listMethods();
                $this->json["data"] = $data;
            }
        }
//        else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
//            $post = $this->getPost();
//
//            if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
//                $this->editAddress($this->request->get['id'], $post);
//            }
//        }
        else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        return $this->sendResponse();

    }

    public function listAddresses()
    {

        $this->language->load('checkout/checkout');
        $this->load->model('account/address');


        if (isset($this->session->data['shipping_address']['address_id'])) {
            $data['address_id'] = $this->session->data['shipping_address']['address_id'];
        } else {
            if ($this->customer->getAddressId()){
                $this->session->data['shipping_address']  = $this->model_account_address->getAddress($this->customer->getAddressId());
                $data['address_id'] =  $this->session->data['shipping_address']['address_id'];
            } else {
                $this->session->data['shipping_address'] = $data['address_id'] = $this->model_account_address->getDefaultAddress(0);
                $data['address_id'] =  $this->session->data['shipping_address']['address_id'];
            }
        }


        if (isset($this->session->data['payment_address']['address_id'])) {
            $data['address_id'] = $this->session->data['payment_address']['address_id'];
        } else {

            if ($this->customer->getAddressId()){

                $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                $data['address_id'] = $this->session->data['payment_address']['address_id'];
            } else {
                $this->session->data['payment_address'] = $data['address_id'] = $this->model_account_address->getDefaultAddress(0);
                $data['address_id'] = $this->session->data['payment_address']['address_id'];
            }
        }

        $addresses = array();
        foreach ($this->model_account_address->getAddresses() as $address) {
            $addresses[] = $address;
        }

        $json['addresses'] = $addresses;
        list($json['payment'] , $json['shipping']) = $this->listMethods();

        return $json;

        // Custom Fields
//        $this->load->model('account/custom_field');
//        $data['custom_fields'] = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

//        $this->json["data"] = $data;
//
//        if($this->includeMeta) {
//            $this->response->addHeader('X-Total-Count: ' . count($data['addresses']));
//            $this->response->addHeader('X-Pagination-Limit: '.count($data['addresses']));
//            $this->response->addHeader('X-Pagination-Page: 1');
//            $addressData = $data['addresses'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => count($addressData),
//                'pagenumber'    => 1,
//                'pagesize'      => count($addressData),
//                'address_id'    => $data['address_id'],
//                'items'         => $addressData
//            );
//        }
    }

    public function saveAddress($post, $existing=false)
    {

        $this->language->load('checkout/checkout');
        $this->language->load('checkout/cart');

        // Validate if customer is logged in.
        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged.";
            $this->statusCode = 400;
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->json['error'][] = "Your cart is empty or a product is out of stock";
            $this->statusCode = 400;
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
                $this->statusCode = 400;
                break;
            }
        }


        if (empty($this->json['error'])) {
            if ($existing) {

                $this->load->model('account/address');

                if (empty($post['address_id'])) {

                    $this->json['error'][] = $this->language->get('error_address');
                } elseif (!in_array($post['address_id'], array_keys($this->model_account_address->getAddresses()))) {
                    $this->json['error'][] = $this->language->get('error_address');
                }

                if (empty($this->json['error'])) {

                    // Default Payment Address
                    $this->load->model('account/address');

                    $this->session->data['payment_address'] = $this->model_account_address->getAddress($post['address_id']);
                    $this->session->data['shipping_address'] = $this->model_account_address->getAddress($post['address_id']);


//                    unset($this->session->data['payment_method']);
//                    unset($this->session->data['payment_methods']);

                }
            } else {
                $this->language->load('account/address');



                // if (empty($post['name']) || (utf8_strlen($post['name']) < 3) || (utf8_strlen($post['name']) > 32)) {
                //     $this->json['error'][] = $this->language->get('error_name');
                // }

                // if (empty($post['telephone']) || (utf8_strlen($post['telephone']) < 8) || (utf8_strlen($post['telephone']) > 16)) {
                //     $this->json['error'][] = $this->language->get('error_telephone');
                // }

                if (empty($post['zone_id']) || !is_int((int)$post['zone_id'])) {
                    $this->json['error'][] = $this->language->get('error_zone_id');
                }

                if (empty($post['city']) || !is_int((int)$post['city'])) {
                    $this->json['error'][] = $this->language->get('error_city');
                }


                // Custom field validation
                $this->load->model('account/custom_field');

                $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

                foreach ($custom_fields as $custom_field) {
                    if ($custom_field['location'] == 'address') {
                        if ($custom_field['required'] && empty($post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                            $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                        } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) &&
                            !filter_var($post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                            $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                        }
                    }
                }

                if (empty($this->json['error'])) {

                    // Default Payment Address
                    $this->load->model('account/address');


                    $post['firstname'] = ' ';
                    $post['lastname'] = ' ';
                    $post['company'] = ' ';
                    $post['telephone'] = ' ';

                    $post['address_2'] = ' ';
                    $post['postcode'] = ' ';

                    $address_id = $this->model_account_address->addAddress($this->customer->getId(), $post);

                    $address = $this->model_account_address->getAddress($address_id);
                    $this->session->data['payment_address'] = $address;
                    $this->session->data['shipping_address'] = $address;

//                    unset($this->session->data['payment_method']);
//                    unset($this->session->data['payment_methods']);

                }




                if (empty($this->json['error'])) {

                    // Validate if shipping is required. If not the customer should not have reached this page.
                    if (!$this->cart->hasShipping()) {
                        $this->json['error'][] = "The customer should not have reached this page, because shipping is not required.";
                    }

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

                    $this->load->model('account/address');

                    if (empty($address_id)) {
                        $this->json['error'][] = $this->language->get('error_address');
                    } elseif (!in_array($address_id, array_keys($this->model_account_address->getAddresses()))) {
                        $this->json['error'][] = $this->language->get('error_address');
                    }

                    if (empty($this->json['error'])) {
                        $this->session->data['shipping_address_id'] = $address_id;
                        $this->session->data['shipping_address'] = $this->model_account_address->getAddress($address_id);

//                        unset($this->session->data['shipping_method']);
//                        unset($this->session->data['shipping_methods']);
                    }


                }

            }
        }
    }

    private function listMethods()
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


            return array($data['payment_methods'] , $data['shipping_methods']);
        }

        return $this->json['error'];






//        $this->json["data"] = $data;
//
//        if($this->includeMeta) {
//            $this->response->addHeader('X-Total-Count: ' . count($data['payment_methods']));
//            $this->response->addHeader('X-Pagination-Limit: '.count($data['payment_methods']));
//            $this->response->addHeader('X-Pagination-Page: 1');
//            $data = $data['payment_methods'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => count($data),
//                'pagenumber'    => 1,
//                'pagesize'      => count($data),
//                'items'         => $data
//            );
//        }
    }


    public function generateCheckoutData(){
        $dataAddress = $this->listAddresses();
        $this->load->model('account/address');

        if (is_array($dataAddress['addresses']) AND count($dataAddress['addresses']) > 0 ){
            $address = $dataAddress['addresses'][0];
        } else {
            $address = $this->model_account_address->getAddress(0);
        }

        $this->load->model('catalog/utility');

        $this->session->data['payment_address'] = $address;
        $this->session->data['shipping_address'] = $address;
        $data = array();
        $data['addresses'] = $dataAddress['addresses'];

        list($data['payment'] , $data['shipping']) = $this->listMethods();

        $this->json["data"] = $data;
        return $this->sendResponse();

    }



    public function companyOrder()
    {
        $this->checkPlugin();
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->addCompanyOrder($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }
        return $this->sendResponse();

    }


    public function addCompanyOrder($post)
    {
        $this->load->language('api/utility');
        if (!isset($post['company_name']) || (utf8_strlen($post['company_name']) < 3) || (utf8_strlen($post['company_name']) > 255)) {
            $this->json['error']['company_name'] = $this->language->get('error_company_name');
        }
        if (!isset($post['phone_number']) || (utf8_strlen($post['phone_number']) < 8) || (utf8_strlen($post['phone_number']) > 25)) {
            $this->json['error']['phone_number'] = $this->language->get('error_phone_number');
        }
        if (!isset($post['address']) || (utf8_strlen($post['address']) < 3) || (utf8_strlen($post['address']) > 255)) {
            $this->json['error']['address'] = $this->language->get('error_address');
        }
        if (!isset($post['content']) || (utf8_strlen($post['content']) < 10) || (utf8_strlen($post['content']) > 10000)) {
            $this->json['error']['content'] = $this->language->get('error_content');
        }
        if (!isset($post['email']) || (utf8_strlen($post['email']) > 96) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $post['email'])) {
            $this->json['error']['email'] = $this->language->get('error_email');
        }
        if (empty($this->json["error"])) {
            $this->load->model('catalog/utility');
            $this->model_catalog_utility->saveCompanyOrder($post);
        } else {
            $this->statusCode = 400;
        }
    }


    public function review()
    {
        $this->checkPlugin();
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->addReview($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }
        return $this->sendResponse();

    }


    public function addReview($post)
    {
        $this->load->model('catalog/utility');
        $this->load->language('api/utility');
        if (!isset($post['order_id']) || !is_int((int)$post['order_id'])) {
            $this->json['error']['order_id'] = $this->language->get('error_order_id');
        } else {
            $orderCount = $this->model_catalog_utility->checkCustomerHasThisOrder($post['order_id']);
            if($orderCount == 0){
                $this->json['error']['order_id'] = $this->language->get('error_not_exist_order');
            } else{
                $orderReviewCount = $this->model_catalog_utility->checkCustomerHasReviewBefore($post['order_id']);
                if ($orderReviewCount != 0){
                    $this->json['error']['review'] = $this->language->get('error_review_exist');
                }
            }
        }

        if (!$this->customer->isLogged()) {
            $this->json['error']['logged'] = $this->language->get('error_login');
        }

        if (!isset($post['content']) || (utf8_strlen($post['content']) < 10) || (utf8_strlen($post['content']) > 10000)) {
            $this->json['error']['content'] = $this->language->get('error_content');
        }
        if (empty($this->json["error"])) {
            $this->model_catalog_utility->saveReview($post);
        } else {
            $this->statusCode = 400;
        }
    }



}