<?php
/**
 * simple_confirm.php
 *
 * Order confirmation simple way, no payment_method, shipping method needed
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestSimpleConfirm extends RestController
{

    public function confirm()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->saveOrderToDatabase();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->confirmOrder();
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("PUT", "POST");
        }

        return $this->sendResponse();
    }


    public function saveOrderToDatabase()
    {

        $this->checkPlugin();
        $this->auth();

        $this->load->model('checkout/order');
        $this->load->model('account/order');

        if (isset($this->session->data['order_id'])) {
            $order_status_id = 1;

            $cod_status = $this->config->get('payment_cod_order_status_id');

            if (!empty($cod_status)) {
                $order_status_id = $cod_status;
            }

            if (!isset($this->session->data['payment_method']) || empty($this->session->data['payment_method'])) {
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id, isset($this->session->data['comment']) ? $this->session->data['comment'] : '');
            } else {
                $status = $this->model_account_order->getOrderStatusById($this->session->data['order_id']);
                if (empty($status)) {
                    $defaultStatus = $this->config->get("payment_" . $this->session->data['payment_method']['code'] . '_order_status_id');
                    $defaultStatus = is_null($defaultStatus) ? $order_status_id : $defaultStatus;

                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $defaultStatus, isset($this->session->data['comment']) ? $this->session->data['comment'] : '');
                }
            }

            if (isset($this->session->data['order_id'])) {
                $this->json["data"]["order_id"] = $this->session->data['order_id'];
                $this->cart->clear();

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
                unset($this->session->data['guest']);

                if (isset($this->session->data['comment'])) {
                    unset($this->session->data['comment']);
                }

                unset($this->session->data['order_id']);
                unset($this->session->data['coupon']);
                unset($this->session->data['reward']);
                unset($this->session->data['voucher']);
                unset($this->session->data['vouchers']);
                unset($this->session->data['totals']);

            }
        } else {
            $this->statusCode = 400;
            $this->json['error'][] = "No order in session";
        }
    }

    public function confirmOrder()
    {

        $this->checkPlugin();
        $this->auth();

        if (!$this->cart->hasShipping()) {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->json["error"][] = "Empty cart or stock failed";
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
                $this->json["error"][] = "Product minimum is greater than product total";

                break;
            }
        }


        if (empty($this->json['error'])) {
            $order_data = array();
            $totals = array();

            $order_data['totals'] = array();
            $total = 0;
            $taxes = $this->cart->getTaxes();


            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );

            $this->load->model('setting/extension');

            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);

            $order_data['totals'] = $totals;


            $this->load->language('checkout/checkout');

            $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
            $order_data['store_id'] = $this->config->get('config_store_id');
            $order_data['store_name'] = $this->config->get('config_name');

            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTPS_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }

            $payment_address = array();
            if ($this->customer->isLogged()) {
                $this->load->model('account/customer');

                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

                $order_data['customer_id'] = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname'] = $customer_info['firstname'];
                $order_data['lastname'] = $customer_info['lastname'];
                $order_data['email'] = $customer_info['email'];
                $order_data['telephone'] = $customer_info['telephone'];
                $order_data['fax'] = $customer_info['fax'];

                $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);

                $this->load->model('account/address');

                if (isset($this->session->data['payment_address']['address_id'])) {
                    $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address']['address_id']);
                } else {
                    $payment_address = $this->model_account_address->getAddress($this->customer->getAddressId());
                }
            } elseif (isset($this->session->data['guest'])) {
                $order_data['customer_id'] = 0;
                $order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
                $order_data['firstname'] = $this->session->data['guest']['firstname'];
                $order_data['lastname'] = $this->session->data['guest']['lastname'];
                $order_data['email'] = $this->session->data['guest']['email'];
                $order_data['telephone'] = $this->session->data['guest']['telephone'];
                $order_data['fax'] = $this->session->data['guest']['fax'];
                $order_data['custom_field'] = $this->session->data['guest']['custom_field'];

                $payment_address = $this->session->data['guest']['payment'];
            }

            if (empty($this->json['error'])) {
                $order_data['payment_firstname'] = $payment_address['firstname'];
                $order_data['payment_lastname'] = $payment_address['lastname'];
                $order_data['payment_company'] = $payment_address['company'];
                $order_data['payment_address_1'] = $payment_address['address_1'];
                $order_data['payment_address_2'] = $payment_address['address_2'];
                $order_data['payment_city'] = $payment_address['city'];
                $order_data['payment_postcode'] = $payment_address['postcode'];
                $order_data['payment_zone'] = $payment_address['zone'];
                $order_data['payment_zone_id'] = $payment_address['zone_id'];
                $order_data['payment_country'] = $payment_address['country'];
                $order_data['payment_country_id'] = $payment_address['country_id'];
                $order_data['payment_address_format'] = $payment_address['address_format'];
                $order_data['payment_custom_field'] = $payment_address['custom_field'];

                if (isset($this->session->data['payment_method']['title'])) {
                    $order_data['payment_method'] = $this->session->data['payment_method']['title'];
                } else {
                    $order_data['payment_method'] = '';
                }

                if (isset($this->session->data['payment_method']['code'])) {
                    $order_data['payment_code'] = $this->session->data['payment_method']['code'];
                } else {
                    $order_data['payment_code'] = '';
                }

                if ($this->cart->hasShipping()) {
                    $shipping_address = array();
                    if ($this->customer->isLogged()) {
                        $this->load->model('account/address');

                        if (isset($this->session->data['shipping_address']['address_id'])) {
                            $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address']['address_id']);
                        } else {
                            $shipping_address = $this->model_account_address->getAddress($this->customer->getAddressId());
                        }
                    } elseif (isset($this->session->data['guest'])) {
                        $shipping_address = $this->session->data['guest']['shipping'];
                    }
                    $order_data['shipping_firstname'] = $shipping_address['firstname'];
                    $order_data['shipping_lastname'] = $shipping_address['lastname'];
                    $order_data['shipping_company'] = $shipping_address['company'];
                    $order_data['shipping_address_1'] = $shipping_address['address_1'];
                    $order_data['shipping_address_2'] = $shipping_address['address_2'];
                    $order_data['shipping_city'] = $shipping_address['city'];
                    $order_data['shipping_postcode'] = $shipping_address['postcode'];
                    $order_data['shipping_zone'] = $shipping_address['zone'];
                    $order_data['shipping_zone_id'] = $shipping_address['zone_id'];
                    $order_data['shipping_country'] = $shipping_address['country'];
                    $order_data['shipping_country_id'] = $shipping_address['country_id'];
                    $order_data['shipping_address_format'] = $shipping_address['address_format'];
                    $order_data['shipping_custom_field'] = $shipping_address['custom_field'];

                    if (isset($this->session->data['shipping_method']['title'])) {
                        $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                    } else {
                        $order_data['shipping_method'] = '';
                    }

                    if (isset($this->session->data['shipping_method']['code'])) {
                        $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                    } else {
                        $order_data['shipping_code'] = '';
                    }
                } else {

                    $order_data['shipping_firstname'] = '';
                    $order_data['shipping_lastname'] = '';
                    $order_data['shipping_company'] = '';
                    $order_data['shipping_address_1'] = '';
                    $order_data['shipping_address_2'] = '';
                    $order_data['shipping_city'] = '';
                    $order_data['shipping_postcode'] = '';
                    $order_data['shipping_zone'] = '';
                    $order_data['shipping_zone_id'] = '';
                    $order_data['shipping_country'] = '';
                    $order_data['shipping_country_id'] = '';
                    $order_data['shipping_address_format'] = '';
                    $order_data['shipping_custom_field'] = array();
                    $order_data['shipping_method'] = '';
                    $order_data['shipping_code'] = '';
                }

                $order_data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        $option_data[] = array(
                            'product_option_id' => $option['product_option_id'],
                            'product_option_value_id' => $option['product_option_value_id'],
                            'option_id' => $option['option_id'],
                            'option_value_id' => $option['option_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value'],
                            'type' => $option['type']
                        );
                    }

                    $order_data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'option' => $option_data,
                        'download' => $product['download'],
                        'quantity' => $product['quantity'],
                        'subtract' => $product['subtract'],
                        'price' => $product['price'],
                        'total' => $product['total'],
                        'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                        'reward' => $product['reward']
                    );
                }

                // Gift Voucher
                $order_data['vouchers'] = array();

                if (!empty($this->session->data['vouchers'])) {
                    foreach ($this->session->data['vouchers'] as $voucher) {
                        $order_data['vouchers'][] = array(
                            'description' => $voucher['description'],
                            'code' => substr(md5(mt_rand()), 0, 10),
                            'to_name' => $voucher['to_name'],
                            'to_email' => $voucher['to_email'],
                            'from_name' => $voucher['from_name'],
                            'from_email' => $voucher['from_email'],
                            'voucher_theme_id' => $voucher['voucher_theme_id'],
                            'message' => $voucher['message'],
                            'amount' => $voucher['amount']
                        );
                    }
                }

                $order_data['comment'] = isset($this->session->data['comment']) ? $this->session->data['comment'] : "";
                $order_data['total'] = $total_data['total'];

                if (isset($this->request->cookie['tracking'])) {
                    $order_data['tracking'] = $this->request->cookie['tracking'];

                    $subtotal = $this->cart->getSubTotal();

                    // Affiliate
                    $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

                    if ($affiliate_info) {
                        $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                        $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                    } else {
                        $order_data['affiliate_id'] = 0;
                        $order_data['commission'] = 0;
                    }

                    // Marketing
                    $this->load->model('checkout/marketing');

                    $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

                    if ($marketing_info) {
                        $order_data['marketing_id'] = $marketing_info['marketing_id'];
                    } else {
                        $order_data['marketing_id'] = 0;
                    }
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                    $order_data['marketing_id'] = 0;
                    $order_data['tracking'] = '';
                }

                $order_data['language_id'] = $this->config->get('config_language_id');
                $order_data['currency_id'] = $this->currency->getId($this->currency->getRestCurrencyCode());
                $order_data['currency_code'] = $this->currency->getRestCurrencyCode();
                $order_data['currency_value'] = $this->currency->getValue($this->currency->getRestCurrencyCode());

                $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

                if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                    $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                    $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
                } else {
                    $order_data['forwarded_ip'] = '';
                }

                if (isset($this->request->server['HTTP_USER_AGENT'])) {
                    $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
                } else {
                    $order_data['user_agent'] = '';
                }

                if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
                    $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
                } else {
                    $order_data['accept_language'] = '';
                }

                $this->load->model('checkout/order');

                $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);


                $data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        if ($option['type'] != 'file') {
                            $value = $option['value'];
                        } else {
                            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                            if ($upload_info) {
                                $value = $upload_info['name'];
                            } else {
                                $value = '';
                            }
                        }

                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                        );
                    }

                    $recurring = '';

                    if ($product['recurring']) {
                        $frequencies = array(
                            'day' => $this->language->get('text_day'),
                            'week' => $this->language->get('text_week'),
                            'semi_month' => $this->language->get('text_semi_month'),
                            'month' => $this->language->get('text_month'),
                            'year' => $this->language->get('text_year'),
                        );

                        if ($product['recurring']['trial']) {
                            $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                        }

                        if ($product['recurring']['duration']) {
                            $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                        } else {
                            $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                        }

                    }

                    $data['products'][] = array(
                        'key' => isset($product['cart_id']) ? $product['cart_id'] : (isset($product['key']) ? $product['key'] : ""),
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'option' => $option_data,
                        'recurring' => $recurring,
                        'quantity' => $product['quantity'],
                        'subtract' => $product['subtract'],
                        'price' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->currency->getRestCurrencyCode()),
                        'total' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->currency->getRestCurrencyCode()),
                        'href' => ""
                    );
                }

                // Gift Voucher
                $data['vouchers'] = array();

                if (!empty($this->session->data['vouchers'])) {
                    foreach ($this->session->data['vouchers'] as $voucher) {
                        $data['vouchers'][] = array(
                            'description' => $voucher['description'],
                            'amount' => $this->currency->format($voucher['amount'])
                        );
                    }
                }

                $data['totals'] = array();

                foreach ($order_data['totals'] as $total) {
                    $data['totals'][] = array(
                        'title' => $total['title'],
                        'text' => $this->currency->format($total['value'], $this->currency->getRestCurrencyCode()),
                    );
                }

                $data['payment'] = '';

                if (isset($this->session->data['payment_method']['code'])) {
                    $data['payment'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);
                }

                $data['order_id'] = $this->session->data['order_id'];
                $this->json["data"] = $data;
            }
        }

    }

}