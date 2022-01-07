<?php
/**
 * guest_shipping.php
 *
 * Guest customer shipping management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestGuestShipping extends RestController
{

    public function guestshipping()
    {

        $this->checkPlugin();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->getGuestShipping();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->saveGuestShipping($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        $this->sendResponse();
    }

    public function getGuestShipping()
    {


        if (isset($this->session->data['shipping_address']['firstname'])) {
            $data['firstname'] = $this->session->data['shipping_address']['firstname'];
        } else {
            $data['firstname'] = '';
        }

        if (isset($this->session->data['shipping_address']['lastname'])) {
            $data['lastname'] = $this->session->data['shipping_address']['lastname'];
        } else {
            $data['lastname'] = '';
        }

        if (isset($this->session->data['shipping_address']['company'])) {
            $data['company'] = $this->session->data['shipping_address']['company'];
        } else {
            $data['company'] = '';
        }

        if (isset($this->session->data['shipping_address']['address_1'])) {
            $data['address_1'] = $this->session->data['shipping_address']['address_1'];
        } else {
            $data['address_1'] = '';
        }

        if (isset($this->session->data['shipping_address']['address_2'])) {
            $data['address_2'] = $this->session->data['shipping_address']['address_2'];
        } else {
            $data['address_2'] = '';
        }

        if (isset($this->session->data['shipping_address']['postcode'])) {
            $data['postcode'] = $this->session->data['shipping_address']['postcode'];
        } else {
            $data['postcode'] = '';
        }

        if (isset($this->session->data['shipping_address']['city'])) {
            $data['city'] = $this->session->data['shipping_address']['city'];
        } else {
            $data['city'] = '';
        }

        if (isset($this->session->data['shipping_address']['country_id'])) {
            $data['country_id'] = $this->session->data['shipping_address']['country_id'];
        } else {
            $data['country_id'] = $this->config->get('config_country_id');
        }

        if (isset($this->session->data['shipping_address']['zone_id'])) {
            $data['zone_id'] = $this->session->data['shipping_address']['zone_id'];
        } else {
            $data['zone_id'] = '';
        }

        // Custom Fields
        $this->load->model('account/custom_field');

        $data['custom_fields'] = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

        if (isset($this->session->data['shipping_address']['custom_field'])) {
            $data['address_custom_field'] = $this->session->data['shipping_address']['custom_field'];
        } else {
            $data['address_custom_field'] = array();
        }

        $this->json["data"] = $data;
    }


    public function saveGuestShipping($data)
    {

        $this->language->load('checkout/checkout');

        // Validate if customer is logged in.
        if ($this->customer->isLogged()) {
            $this->json['error'][] = "User is logged, not guest user";
            $this->statusCode = 400;
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->json['error'][] = "Your cart is empty or a product is out of stock";
            $this->statusCode = 400;
        }

        // Check if guest checkout is avaliable.
        if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload()) {
            $this->json['error'][] = "Guest checkout is not avaliable";
            $this->statusCode = 400;

        }

        if (empty($this->json['error'])) {

            if (!isset($data['firstname']) || (utf8_strlen($data['firstname']) < 1) || (utf8_strlen($data['firstname']) > 32)) {
                $this->json['error'][] = $this->language->get('error_firstname');
            }

            if (!isset($data['lastname']) || (utf8_strlen($data['lastname']) < 1) || (utf8_strlen($data['lastname']) > 32)) {
                $this->json['error'][] = $this->language->get('error_lastname');
            }

            if (!isset($data['address_1']) || (utf8_strlen($data['address_1']) < 3) || (utf8_strlen($data['address_1']) > 128)) {
                $this->json['error'][] = $this->language->get('error_address_1');
            }

            if (!isset($data['city']) || (utf8_strlen($data['city']) < 2) || (utf8_strlen($data['city']) > 128)) {
                $this->json['error'][] = $this->language->get('error_city');
            }

            // Custom field validation
            $this->load->model('account/custom_field');

            $custom_fields = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'address') {
                    if ($custom_field['required'] && empty($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                        $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) &&
                        !filter_var($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                        $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    }
                }
            }

            if (isset($data['country_id'])) {
                $this->load->model('localisation/country');

                $country_info = $this->model_localisation_country->getCountry($data['country_id']);

                if ($country_info && $country_info['postcode_required'] && (utf8_strlen($data['postcode']) < 2) || (utf8_strlen($data['postcode']) > 10)) {
                    $this->json['error'][] = $this->language->get('error_postcode');
                }

                if ($data['country_id'] == '') {
                    $this->json['error'][] = $this->language->get('error_country');
                }

                if (!isset($data['zone_id']) || $data['zone_id'] == '') {
                    $this->json['error'][] = $this->language->get('error_zone');
                }
            } else {
                $this->json['error'][] = $this->language->get('error_country');
            }
        }

        if (empty($this->json['error'])) {

            if(!isset($data['company'])){
                $data['company'] = "";
            }

            $this->session->data['shipping_address']['firstname'] = $data['firstname'];
            $this->session->data['shipping_address']['lastname'] = $data['lastname'];
            $this->session->data['shipping_address']['company'] = $data['company'];
            $this->session->data['shipping_address']['address_1'] = $data['address_1'];
            $this->session->data['shipping_address']['address_2'] = $data['address_2'];
            $this->session->data['shipping_address']['postcode'] = $data['postcode'];
            $this->session->data['shipping_address']['city'] = $data['city'];
            $this->session->data['shipping_address']['country_id'] = $data['country_id'];
            $this->session->data['shipping_address']['zone_id'] = $data['zone_id'];

            $this->load->model('localisation/country');

            $country_info = $this->model_localisation_country->getCountry($data['country_id']);

            if ($country_info) {
                $this->session->data['shipping_address']['country'] = $country_info['name'];
                $this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
                $this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
                $this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
            } else {
                $this->session->data['shipping_address']['country'] = '';
                $this->session->data['shipping_address']['iso_code_2'] = '';
                $this->session->data['shipping_address']['iso_code_3'] = '';
                $this->session->data['shipping_address']['address_format'] = '';
            }

            $this->load->model('localisation/zone');

            $zone_info = $this->model_localisation_zone->getZone($data['zone_id']);

            if ($zone_info) {
                $this->session->data['shipping_address']['zone'] = $zone_info['name'];
                $this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
            } else {
                $this->session->data['shipping_address']['zone'] = '';
                $this->session->data['shipping_address']['zone_code'] = '';
            }

            if (isset($data['custom_field'])) {
                $this->session->data['shipping_address']['custom_field'] = $data['custom_field'];
            } else {
                $this->session->data['shipping_address']['custom_field'] = array();
            }

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

        } else {
            $this->statusCode = 400;
        }
    }
}