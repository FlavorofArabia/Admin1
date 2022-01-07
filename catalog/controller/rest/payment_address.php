<?php
/**
 * payment_address.php
 *
 * Payment management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestPaymentAddress extends RestController
{

    public function validate($post)
    {

        if (!isset($post['firstname']) || (utf8_strlen(trim($post['firstname'])) < 1) || (utf8_strlen(trim($post['firstname'])) > 32)) {
            $this->json['error'][] = $this->language->get('error_firstname');
        }

        if (!isset($post['lastname']) || (utf8_strlen(trim($post['lastname'])) < 1) || (utf8_strlen(trim($post['lastname'])) > 32)) {
            $this->json['error'][] = $this->language->get('error_lastname');
        }

        if (!isset($post['address_1']) || (utf8_strlen(trim($post['address_1'])) < 3) || (utf8_strlen(trim($post['address_1'])) > 128)) {
            $this->json['error'][] = $this->language->get('error_address_1');
        }

        if (!isset($post['city']) || (utf8_strlen($post['city']) < 2) || (utf8_strlen($post['city']) > 32)) {
            $this->json['error'][] = $this->language->get('error_city');
        }

        if (isset($post['country_id'])) {

            $this->load->model('localisation/country');

            $country_info = $this->model_localisation_country->getCountry($post['country_id']);

            if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($post['postcode'])) < 2 || utf8_strlen(trim($post['postcode'])) > 10)) {
                $this->json['error'][] = $this->language->get('error_postcode');
            }

            if ($post['country_id'] == '') {
                $this->json['error'][] = $this->language->get('error_country');
            }

            if (!isset($post['zone_id']) || $post['zone_id'] == '') {
                $this->json['error'][] = $this->language->get('error_zone');
            }
        } else {
            $this->json['error'][] = $this->language->get('error_country');
        }
    }


    public function paymentaddress()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get payment addresses
            $this->listPaymentAddresses();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //save payment address information to session
            $post = $this->getPost();

            $existing = false;

            if (isset($this->request->get['existing']) && !empty($this->request->get['existing'])) {
                $existing = true;
            }

            $this->savePaymentAddress($post, $existing);

        } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $post = $this->getPost();

            if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                $this->editAddress($this->request->get['id'], $post);
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST", "PUT");
        }

        return $this->sendResponse();

    }

    public function listPaymentAddresses()
    {

        $this->language->load('checkout/checkout');


        if (isset($this->session->data['payment_address']['address_id'])) {
            $data['address_id'] = $this->session->data['payment_address']['address_id'];
        } else {
            $data['address_id'] = $this->customer->getAddressId();
        }

        $this->load->model('account/address');

        $addresses = array();
        foreach ($this->model_account_address->getAddresses() as $address) {
            $addresses[] = $address;
        }

        $data['addresses'] = $addresses;

        // Custom Fields
//        $this->load->model('account/custom_field');
//        $data['custom_fields'] = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        $this->json["data"] = $data;

        if($this->includeMeta) {
            $this->response->addHeader('X-Total-Count: ' . count($data['addresses']));
            $this->response->addHeader('X-Pagination-Limit: '.count($data['addresses']));
            $this->response->addHeader('X-Pagination-Page: 1');
//            $addressData = $data['addresses'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => count($addressData),
//                'pagenumber'    => 1,
//                'pagesize'      => count($addressData),
//                'address_id'    => $data['address_id'],
//                'items'         => $addressData
//            );
        }
    }

    public function savePaymentAddress($post, $existing=false)
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

                    unset($this->session->data['payment_method']);
                    unset($this->session->data['payment_methods']);

                }
            } else {
                if (!isset($post['firstname']) || (utf8_strlen(trim($post['firstname'])) < 1) || (utf8_strlen(trim($post['firstname'])) > 32)) {
                    $this->json['error'][] = $this->language->get('error_firstname');
                }

                if (!isset($post['lastname']) || (utf8_strlen(trim($post['lastname'])) < 1) || (utf8_strlen(trim($post['lastname'])) > 32)) {
                    $this->json['error'][] = $this->language->get('error_lastname');
                }

                if (!isset($post['address_1']) || (utf8_strlen(trim($post['address_1'])) < 3) || (utf8_strlen(trim($post['address_1'])) > 128)) {
                    $this->json['error'][] = $this->language->get('error_address_1');
                }

                if (!isset($post['city']) || (utf8_strlen($post['city']) < 2) || (utf8_strlen($post['city']) > 32)) {
                    $this->json['error'][] = $this->language->get('error_city');
                }

                if (isset($post['country_id'])) {
                    $this->load->model('localisation/country');

                    $country_info = $this->model_localisation_country->getCountry($post['country_id']);

                    if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($post['postcode'])) < 2 || utf8_strlen(trim($post['postcode'])) > 10)) {
                        $this->json['error'][] = $this->language->get('error_postcode');
                    }

                    if ($post['country_id'] == '') {
                        $this->json['error'][] = $this->language->get('error_country');
                    }

                    if (!isset($post['zone_id']) || $post['zone_id'] == '') {
                        $this->json['error'][] = $this->language->get('error_zone');
                    }
                } else {
                    $this->json['error'][] = $this->language->get('error_country');
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

                    if (!isset($post['company'])) {
                        $post["company"] = "";
                    }


                    $address_id = $this->model_account_address->addAddress($this->customer->getId(), $post);

                    $this->session->data['payment_address'] = $this->model_account_address->getAddress($address_id);

                    unset($this->session->data['payment_method']);
                    unset($this->session->data['payment_methods']);

                }
            }
        }
    }
}