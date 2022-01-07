<?php
/**
 * guest.php
 *
 * Guest customer management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestGuest extends RestController
{


    public function zone()
    {

        $this->checkPlugin();


        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->load->model('localisation/zone');
            if (isset($this->request->get['country_id']) && ctype_digit($this->request->get['country_id'])) {

                $results = $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']);

                foreach ($results as $result) {
                    $this->json['data'][] = array(
                        'zone_id' => $result['zone_id'],
                        'country_id' => $result['country_id'],
                        'name' => $result['name'],
                        'status' => $result['status'],
                        'code' => $result['code']
                    );
                }

            } else {
                $this->json['error'] = "Missing or wrong country_id parameter";
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET");
        }

        return $this->sendResponse();
    }

    public function guest()
    {

        $this->checkPlugin();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get guest
            $this->getGuest();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //add guest
            $post = $this->getPost();
            $this->addGuest($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        return $this->sendResponse();

    }

    public function getGuest()
    {

        $this->json["data"]['customer_groups'] = array();

        if (is_array($this->config->get('config_customer_group_display'))) {
            $this->load->model('account/customer_group');

            $customer_groups = $this->model_account_customer_group->getCustomerGroups();

            foreach ($customer_groups as $customer_group) {
                if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                    $this->json["data"]['customer_groups'][] = $customer_group;
                }
            }
        }

        if (isset($this->session->data['guest']['customer_group_id'])) {
            $this->json["data"]['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
        } else {
            $this->json["data"]['customer_group_id'] = $this->config->get('config_customer_group_id');
        }

        if (isset($this->session->data['guest']['firstname'])) {
            $this->json["data"]['firstname'] = $this->session->data['guest']['firstname'];
        } else {
            $this->json["data"]['firstname'] = '';
        }

        if (isset($this->session->data['guest']['lastname'])) {
            $this->json["data"]['lastname'] = $this->session->data['guest']['lastname'];
        } else {
            $this->json["data"]['lastname'] = '';
        }

        if (isset($this->session->data['guest']['email'])) {
            $this->json["data"]['email'] = $this->session->data['guest']['email'];
        } else {
            $this->json["data"]['email'] = '';
        }

        if (isset($this->session->data['guest']['telephone'])) {
            $this->json["data"]['telephone'] = $this->session->data['guest']['telephone'];
        } else {
            $this->json["data"]['telephone'] = '';
        }

        if (isset($this->session->data['guest']['fax'])) {
            $this->json["data"]['fax'] = $this->session->data['guest']['fax'];
        } else {
            $this->json["data"]['fax'] = '';
        }

        if (isset($this->session->data['payment_address']['company'])) {
            $this->json["data"]['company'] = $this->session->data['payment_address']['company'];
        } else {
            $this->json["data"]['company'] = '';
        }

        if (isset($this->session->data['payment_address']['address_1'])) {
            $this->json["data"]['address_1'] = $this->session->data['payment_address']['address_1'];
        } else {
            $this->json["data"]['address_1'] = '';
        }

        if (isset($this->session->data['payment_address']['address_2'])) {
            $this->json["data"]['address_2'] = $this->session->data['payment_address']['address_2'];
        } else {
            $this->json["data"]['address_2'] = '';
        }

        if (isset($this->session->data['payment_address']['postcode'])) {
            $this->json["data"]['postcode'] = $this->session->data['payment_address']['postcode'];
        } elseif (isset($this->session->data['shipping_address']['postcode'])) {
            $this->json["data"]['postcode'] = $this->session->data['shipping_address']['postcode'];
        } else {
            $this->json["data"]['postcode'] = '';
        }

        if (isset($this->session->data['payment_address']['city'])) {
            $this->json["data"]['city'] = $this->session->data['payment_address']['city'];
        } else {
            $this->json["data"]['city'] = '';
        }

        if (isset($this->session->data['payment_address']['country_id'])) {
            $this->json["data"]['country_id'] = $this->session->data['payment_address']['country_id'];
        } elseif (isset($this->session->data['shipping_address']['country_id'])) {
            $this->json["data"]['country_id'] = $this->session->data['shipping_address']['country_id'];
        } else {
            $this->json["data"]['country_id'] = $this->config->get('config_country_id');
        }

        if (isset($this->session->data['payment_address']['zone_id'])) {
            $this->json["data"]['zone_id'] = $this->session->data['payment_address']['zone_id'];
        } elseif (isset($this->session->data['shipping_address']['zone_id'])) {
            $this->json["data"]['zone_id'] = $this->session->data['shipping_address']['zone_id'];
        } else {
            $this->json["data"]['zone_id'] = '';
        }

        $this->load->model('localisation/country');

        $this->json["data"]['countries'] = $this->model_localisation_country->getCountries();

        // Custom Fields
        $this->load->model('account/custom_field');

        $this->json["data"]['custom_fields'] = $this->model_account_custom_field->getCustomFields();

        if (isset($this->session->data['guest']['custom_field'])) {
            $this->json["data"]['guest_custom_field'] = $this->session->data['guest']['custom_field'] + $this->session->data['payment_address']['custom_field'];
        } else {
            $this->json["data"]['guest_custom_field'] = array();
        }

        $this->json["data"]['shipping_required'] = $this->cart->hasShipping();

        if (isset($this->session->data['guest']['shipping_address'])) {
            $this->json["data"]['shipping_address'] = $this->session->data['guest']['shipping_address'];
        } else {
            $this->json["data"]['shipping_address'] = true;
        }

    }

    public function addGuest($data)
    {

        $this->language->load('checkout/checkout');


        // Validate if customer is logged in.
        if ($this->customer->isLogged()) {
            $this->json['error'][] = "Customer is logged in.";
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->json['error'][] = "Your cart is empty or a product is out of stock.";
        }

        // Check if guest checkout is avaliable.
        if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload()) {
            $this->json['error'][] = "Guest checkout is not available";
        }

        if (empty($this->json['error'])) {
            if (!isset($data['firstname']) || (utf8_strlen(trim($data['firstname'])) < 1) || (utf8_strlen(trim($data['firstname'])) > 32)) {
                $this->json['error'][] = $this->language->get('error_firstname');
            }

            if (!isset($data['lastname']) || (utf8_strlen(trim($data['lastname'])) < 1) || (utf8_strlen(trim($data['lastname'])) > 32)) {
                $this->json['error'][] = $this->language->get('error_lastname');
            }

            if (!isset($data['email']) || (utf8_strlen($data['email']) > 96) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $data['email'])) {
                $this->json['error'][] = $this->language->get('error_email');
            }

            if (!isset($data['telephone']) || (utf8_strlen($data['telephone']) < 3) || (utf8_strlen($data['telephone']) > 32)) {
                $this->json['error'][] = $this->language->get('error_telephone');
            }

            if (!isset($data['address_1']) || (utf8_strlen(trim($data['address_1'])) < 3) || (utf8_strlen(trim($data['address_1'])) > 128)) {
                $this->json['error'][] = $this->language->get('error_address_1');
            }

            if (!isset($data['city']) || (utf8_strlen(trim($data['city'])) < 2) || (utf8_strlen(trim($data['city'])) > 128)) {
                $this->json['error'][] = $this->language->get('error_city');
            }

            if (!isset($data['country_id']) || $data['country_id'] == '') {
                $this->json['error'][] = $this->language->get('error_country');
            } else {
                $this->load->model('localisation/country');

                $country_info = $this->model_localisation_country->getCountry($data['country_id']);

                if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($data['postcode'])) < 2 || utf8_strlen(trim($data['postcode'])) > 10)) {
                    $this->json['error'][] = $this->language->get('error_postcode');
                }
            }

            if (!isset($data['zone_id']) || empty($data['zone_id'])) {
                $this->json['error'][] = $this->language->get('error_zone');
            }

            // Customer Group
            if (isset($data['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($data['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                $customer_group_id = $data['customer_group_id'];
            } else {
                $customer_group_id = $this->config->get('config_customer_group_id');
            }

            // Custom field validation
            $this->load->model('account/custom_field');

            $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'address' || $custom_field['location'] == 'account') {
                    if ($custom_field['required'] && empty($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                        $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) &&
                        !filter_var($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                        $this->json['error'][] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    }
                }
            }
        }

        if (empty($this->json['error'])) {
            $this->session->data['account'] = 'guest';

            $this->session->data['guest']['customer_group_id'] = $customer_group_id;
            $this->session->data['guest']['firstname'] = $data['firstname'];
            $this->session->data['guest']['lastname'] = $data['lastname'];
            $this->session->data['guest']['email'] = $data['email'];
            $this->session->data['guest']['telephone'] = $data['telephone'];
            $this->session->data['guest']['fax'] = isset($data['fax']) ? $data['fax'] : '';

            if (isset($data['custom_field']['account'])) {
                $this->session->data['guest']['custom_field'] = $data['custom_field']['account'];
            } else {
                $this->session->data['guest']['custom_field'] = array();
            }

            $this->session->data['payment_address']['firstname'] = $data['firstname'];
            $this->session->data['payment_address']['lastname'] = $data['lastname'];
            $this->session->data['payment_address']['company'] = isset($data['company']) ? $data['company'] : '';
            $this->session->data['payment_address']['address_1'] = $data['address_1'];
            $this->session->data['payment_address']['address_2'] = isset($data['address_2']) ? $data['address_2'] : '';
            $this->session->data['payment_address']['postcode'] = $data['postcode'];
            $this->session->data['payment_address']['city'] = $data['city'];
            $this->session->data['payment_address']['country_id'] = $data['country_id'];
            $this->session->data['payment_address']['zone_id'] = $data['zone_id'];

            $this->load->model('localisation/country');

            $country_info = $this->model_localisation_country->getCountry($data['country_id']);

            if ($country_info) {
                $this->session->data['payment_address']['country'] = $country_info['name'];
                $this->session->data['payment_address']['iso_code_2'] = $country_info['iso_code_2'];
                $this->session->data['payment_address']['iso_code_3'] = $country_info['iso_code_3'];
                $this->session->data['payment_address']['address_format'] = $country_info['address_format'];
            } else {
                $this->session->data['payment_address']['country'] = '';
                $this->session->data['payment_address']['iso_code_2'] = '';
                $this->session->data['payment_address']['iso_code_3'] = '';
                $this->session->data['payment_address']['address_format'] = '';
            }

            if (isset($data['custom_field']['address'])) {
                $this->session->data['payment_address']['custom_field'] = $data['custom_field']['address'];
            } else {
                $this->session->data['payment_address']['custom_field'] = array();
            }

            $this->load->model('localisation/zone');

            $zone_info = $this->model_localisation_zone->getZone($data['zone_id']);

            if ($zone_info) {
                $this->session->data['payment_address']['zone'] = $zone_info['name'];
                $this->session->data['payment_address']['zone_code'] = $zone_info['code'];
            } else {
                $this->session->data['payment_address']['zone'] = '';
                $this->session->data['payment_address']['zone_code'] = '';
            }

            if (!empty($data['shipping_address'])) {
                $this->session->data['guest']['shipping_address'] = $data['shipping_address'];
            } else {
                $this->session->data['guest']['shipping_address'] = false;
            }

            // Default Payment Address
            if ($this->session->data['guest']['shipping_address']) {
                $this->session->data['shipping_address']['firstname'] = $data['firstname'];
                $this->session->data['shipping_address']['lastname'] = $data['lastname'];
                $this->session->data['shipping_address']['company'] = $data['company'];
                $this->session->data['shipping_address']['address_1'] = $data['address_1'];
                $this->session->data['shipping_address']['address_2'] = $data['address_2'];
                $this->session->data['shipping_address']['postcode'] = $data['postcode'];
                $this->session->data['shipping_address']['city'] = $data['city'];
                $this->session->data['shipping_address']['country_id'] = $data['country_id'];
                $this->session->data['shipping_address']['zone_id'] = $data['zone_id'];

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

                if ($zone_info) {
                    $this->session->data['shipping_address']['zone'] = $zone_info['name'];
                    $this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
                } else {
                    $this->session->data['shipping_address']['zone'] = '';
                    $this->session->data['shipping_address']['zone_code'] = '';
                }

                if (isset($data['custom_field']['address'])) {
                    $this->session->data['shipping_address']['custom_field'] = $data['custom_field']['address'];
                } else {
                    $this->session->data['shipping_address']['custom_field'] = array();
                }
            }

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
        }
    }
}