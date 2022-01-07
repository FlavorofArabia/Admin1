<?php
/**
 * order.php
 *
 * Orders management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestOrder extends RestController
{

    public function orders()
    {

        $this->checkPlugin();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get order details
            if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                $this->getOrder($this->request->get['id']);
            } else {
                //get order list
                $this->listOrders();
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //reorder
            if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                if (isset($this->request->get['order_product_id'])) {
                    $order_product_id = $this->request->get['order_product_id'];
                } else {
                    $order_product_id = 0;
                }

                $this->reorder($this->request->get['id'], $order_product_id);
            } else {
                $this->json['error'][] = "Missing ID";
                $this->statusCode = 400;
            }

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        $this->sendResponse();
    }

    public function getOrder($order_id)
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged.";
            $this->statusCode = 400;
        }
        $this->load->model('tool/image');

        if (empty($this->json['error'])) {

            $this->load->model('account/order');
            $this->language->load('account/order');

            $order_info = $this->model_account_order->getOrder($order_id);

            if ($order_info) {
                $data = $order_info;
                if ($order_info['invoice_no']) {
                    $data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
                } else {
                    $data['invoice_no'] = '';
                }

                $data['order_id'] = $order_id;
                $data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

                if ($order_info['payment_address_format']) {
                    $format = $order_info['payment_address_format'];
                } else {
                    $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
                }

                $find = array(
                    '{firstname}',
                    '{lastname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $order_info['payment_firstname'],
                    'lastname' => $order_info['payment_lastname'],
                    'company' => $order_info['payment_company'],
                    'address_1' => $order_info['payment_address_1'],
                    'address_2' => $order_info['payment_address_2'],
                    'city' => $order_info['payment_city'],
                    'postcode' => $order_info['payment_postcode'],
                    'zone' => $order_info['payment_zone'],
                    'zone_code' => $order_info['payment_zone_code'],
                    'country' => $order_info['payment_country']
                );

                $data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

                $data['payment_method'] = $order_info['payment_method'];

                if ($order_info['shipping_address_format']) {
                    $format = $order_info['shipping_address_format'];
                } else {
                    $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
                }

                $find = array(
                    '{firstname}',
                    '{lastname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $order_info['shipping_firstname'],
                    'lastname' => $order_info['shipping_lastname'],
                    'company' => $order_info['shipping_company'],
                    'address_1' => $order_info['shipping_address_1'],
                    'address_2' => $order_info['shipping_address_2'],
                    'city' => $order_info['shipping_city'],
                    'postcode' => $order_info['shipping_postcode'],
                    'zone' => $order_info['shipping_zone'],
                    'zone_code' => $order_info['shipping_zone_code'],
                    'country' => $order_info['shipping_country']
                );

                $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

                $data['shipping_method'] = $order_info['shipping_method'];

                $data['products'] = array();

                $products = $this->model_account_order->getOrderProducts($order_id);

                $this->load->model('catalog/product');
                $this->load->model('tool/upload');

                foreach ($products as $product) {
                    $option_data = array();

                    $options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

                    foreach ($options as $option) {
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
                            'name'  => $option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                        );
                    }

                    $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                    if (isset($product_info['image']) && !empty($product_info['image']) && file_exists(DIR_IMAGE . $product_info['image'])) {
                        $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_shopping_cart_rest_api_image_width'), $this->config->get('config_shopping_cart_rest_api_image_height'));
                    } else {
                        $image = $this->model_tool_image->resize('no_image.png', $this->config->get('config_shopping_cart_rest_api_image_width'), $this->config->get('config_shopping_cart_rest_api_image_height'));
                    }
                    if ($product_info) {
                        $order_product_id = $product['order_product_id'];
                    } else {
                        $order_product_id = '';
                    }

                    $data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'order_product_id' => $order_product_id,
                        'name' =>  html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
                        'model' => $product['model'],
                        'option' => $option_data,
                        'quantity' => $product['quantity'],
                        'price' => floor($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0)),
                        'image' => $image,

                        'price_formated' => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                        'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
                        'price_raw' => $product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'],
                        'total_raw' => $product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0),
                        'return' => $this->url->link('account/return/insert', 'order_id=' . $order_info['order_id'] . '&product_id=' . $product['product_id'], 'SSL')
                    );
                }

                // Voucher
                $data['vouchers'] = array();

                $vouchers = $this->model_account_order->getOrderVouchers($order_id);

                foreach ($vouchers as $voucher) {
                    $data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'amount' => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
                    );
                }

                $data['totals'] = [];
                $totals = $this->model_account_order->getOrderTotals($order_id);
                foreach($totals as $total){

                    $data['totals'][] = [
                        'order_total_id' => $total['order_total_id'],
                        'order_id' => $total['order_id'],
                        'code' => $total['code'],
                        'title' => $total['title'],
                        'sort_order' => $total['sort_order'],
                        'value' => round($total['value']),
                        'value_formated' => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
                    ];
                }

                $data['comment'] = nl2br($order_info['comment']);

                $data['histories'] = array();

                $results = $this->model_account_order->getOrderHistories($order_id);

                foreach ($results as $result) {
                    $data['histories'][] = array(
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'status' => $result['status'],
                        'comment' => nl2br($result['comment'])
                    );
                }

                $data['timestamp'] = strtotime($order_info['date_added']);

                $data['currency'] = array(
                    'currency_id'   => $this->currency->getId($order_info['currency_code']),
                    'symbol_left'   => $this->currency->getSymbolLeft($order_info['currency_code']),
                    'symbol_right'  => $this->currency->getSymbolRight($order_info['currency_code']),
                    'decimal_place' => $this->currency->getDecimalPlace($order_info['currency_code']),
                    'value'         => $order_info['currency_value']
                );

                $this->json["data"] = $data;
            } else {
                $this->json['error'][] = "The specified order does not exist.";
                $this->statusCode = 404;
            }
        }
    }

    public function listOrders()
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged.";
            $this->statusCode = 400;
        }

        if (empty($this->json['error'])) {

            $this->language->load('account/order');

            $this->load->model('account/order');

            $start  = 1;
            $limit  = 100;
            $page   = 1;

            /*check limit parameter*/
            if (isset($this->request->get['limit']) && ctype_digit($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            }

            /*check page parameter*/
            if (isset($this->request->get['page']) && ctype_digit($this->request->get['page'])) {
                $start = $this->request->get['page'];
                $page   = $this->request->get['page'];
            }

            $start = ($start - 1) * $limit;

            $results = $this->model_account_order->getOrders($start, $limit);

            foreach ($results as $result) {
                $product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
                $voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);

                $this->json['data'][] = array(
                    'order_id' => $result['order_id'],
                    'name' => $result['firstname'] . ' ' . $result['lastname'],
                    'status' => $result['status'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'products' => ($product_total + $voucher_total),
                    'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                    'currency_code' => $result['currency_code'],
                    'currency_value' => $result['currency_value'],
                    'total_raw' => $result['total'],
                    'timestamp' => strtotime($result['date_added']),
                    'currency' => array(
                        'currency_id'   => $this->currency->getId($result['currency_code']),
                        'symbol_left'   => $this->currency->getSymbolLeft($result['currency_code']),
                        'symbol_right'  => $this->currency->getSymbolRight($result['currency_code']),
                        'decimal_place' => $this->currency->getDecimalPlace($result['currency_code']),
                        'value'         => $result['currency_value']
                    )
                );
            }

            if($this->includeMeta) {

                $total = $this->model_account_order->getTotalOrders();
                $this->response->addHeader('X-Total-Count: ' . (int)$total);
                $this->response->addHeader('X-Pagination-Limit: '.(int)$limit);
                $this->response->addHeader('X-Pagination-Page: '.(int)$page);
//                $data = $this->json['data'];
//
//                $this->json['data'] = array(
//                    'totalrowcount' => (int)$total,
//                    'pagenumber'    => (int)$page,
//                    'pagesize'      => (int)$limit,
//                    'items'         => $data
//                );
            }
        }
    }

    public function reorder($order_id, $order_product_id)
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged.";
            $this->statusCode = 400;
        }

        if (empty($this->json['error'])) {

            $this->language->load('account/order');

            $this->load->model('account/order');
            /*reorder*/
            if (isset($order_id)) {

                $order_info = $this->model_account_order->getOrder($order_id);

                if ($order_info) {

                    $order_product_info = $this->model_account_order->getOrderProduct($order_id, $order_product_id);

                    if ($order_product_info) {
                        $this->load->model('catalog/product');

                        $product_info = $this->model_catalog_product->getProduct($order_product_info['product_id']);

                        if ($product_info) {
                            $option_data = array();

                            $order_options = $this->model_account_order->getOrderOptions($order_product_info['order_id'], $order_product_id);

                            foreach ($order_options as $order_option) {
                                if ($order_option['type'] == 'select' || $order_option['type'] == 'radio' || $order_option['type'] == 'image') {
                                    $option_data[$order_option['product_option_id']] = $order_option['product_option_value_id'];
                                } elseif ($order_option['type'] == 'checkbox') {
                                    $option_data[$order_option['product_option_id']][] = $order_option['product_option_value_id'];
                                } elseif ($order_option['type'] == 'text' || $order_option['type'] == 'textarea' || $order_option['type'] == 'date' || $order_option['type'] == 'datetime' || $order_option['type'] == 'time') {
                                    $option_data[$order_option['product_option_id']] = $order_option['value'];
                                } elseif ($order_option['type'] == 'file') {
                                    $option_data[$order_option['product_option_id']] = $this->encryption->encrypt($this->config->get('config_encryption'), $order_option['value']);
                                }
                            }

                            $this->cart->add($order_product_info['product_id'], $order_product_info['quantity'], $option_data);

                            $this->json['message'][] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $product_info['product_id']), $product_info['name'], $this->url->link('checkout/cart'));

                            unset($this->session->data['shipping_method']);
                            unset($this->session->data['shipping_methods']);
                            unset($this->session->data['payment_method']);
                            unset($this->session->data['payment_methods']);
                        } else {
                            $this->json['error'][] = sprintf($this->language->get('error_reorder'), $order_product_info['name']);
                        }
                    } else {
                        $this->json['error'][] = "The specified item does not found.";
                    }
                } else {
                    $this->json['error'][] = "The specified order does not exist.";
                }
            } else {
                $this->json['error'][] = "The specified order does not exist.";
            }
        }

        if (empty($this->json['error'])) {
            $this->json['message'][] = "You have added item to your shopping cart";
        }
    }
}