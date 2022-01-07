<?php
/**
 * cart.php
 *
 * Cart management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */

require_once(DIR_SYSTEM . 'engine/restcontroller.php');


class ControllerRestCart extends RestController
{

    private $error = array();


    public function cart()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get cart
            $this->getCart();
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //add to cart
            $post = $this->getPost();
            $this->addItemCart($post);

        } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            //update cart
            $post = $this->getPost();
            $this->updateCartQuantity($post);
        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

            if (isset($this->request->get['key'])) {
                $this->deleteCartItem(array(), $this->request->get['key']);
            } else {
                $post = $this->getPost();
                $this->deleteCartItem($post);
            }
        }

        return $this->sendResponse();
    }

    public function getCart()
    {

        $this->language->load('checkout/cart');
        $this->language->load('restapi/rest_api');

        if (!isset($this->session->data['vouchers'])) {
            $this->session->data['vouchers'] = array();
        }

        if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {

            $points = $this->customer->getRewardPoints();

            $points_total = 0;

            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                if ($product['points']) {
                    $points_total += $product['points'];
                }
            }

            unset($product);

            if (isset($this->error['warning'])) {
                $this->json['cart_error'][] = $this->error['warning'];
            } elseif (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
                //$this->json['cart_error'][] = $this->language->get('error_stock');
                foreach ($products as $product) {
                    if (!$product['stock']) {
                        $this->json['cart_error'][] = sprintf($this->language->get('error_api_product_out_of_stock'), $product['name']);
                    }
                }
                unset($product);
            }


            if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
                $this->json['cart_error'][] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));
            }

            if ($this->config->get('config_cart_weight')) {
                $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
            } else {
                $data['weight'] = '';
            }

            $this->load->model('tool/image');
            $this->load->model('tool/upload');
            $this->load->model('catalog/product');

            $data['products'] = array();


            foreach ($products as $product) {


          

                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $this->json['cart_error'][] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
                }

                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_shopping_cart_rest_api_image_width'), $this->config->get('config_shopping_cart_rest_api_image_height'));
                } else {
                    $image = '';
                }

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
                        'product_option_id' => $option['product_option_id'],
                        'product_option_value_id' => $option['product_option_value_id'],

                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );
                }

                // Display prices
                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                    $price = $this->currency->format($unit_price, $this->currency->getRestCurrencyCode());
                    $total = $this->currency->format($unit_price * $product['quantity'], $this->currency->getRestCurrencyCode());
                    $price_raw = $unit_price;
                    $total_raw = $unit_price * $product['quantity'];
                } else {
                    $price = 0;
                    $total = 0;
                    $price_raw = 0;
                    $total_raw = 0;
                }


                $recurring = '';

                if ($product['recurring']) {
                    $frequencies = array(
                        'day'        => $this->language->get('text_day'),
                        'week'       => $this->language->get('text_week'),
                        'semi_month' => $this->language->get('text_semi_month'),
                        'month'      => $this->language->get('text_month'),
                        'year'       => $this->language->get('text_year')
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
                    'thumb' => $image,
                    'name' =>  html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
                    'points' => isset($product['points']) ? $product['points'] : 0,
                    'product_id' => $product['product_id'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'quantity' => $product['quantity'],
                    'recurring' => $recurring,
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price' => $price,
                    'total' => $total,
                    'price_raw' => ceil($price_raw),
                    'total_raw' => ceil($total_raw)
                    );
            }

            // Gift Voucher
            $data['vouchers'] = array();

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $key => $voucher) {
                    $data['vouchers'][] = array(
                        'key' => $key,
                        'description' => $voucher['description'],
                        'amount' => $this->currency->format($voucher['amount'], $this->currency->getRestCurrencyCode()),
                        //'remove' => $this->url->link('checkout/cart', 'remove=' . $key)
                    );
                }
            }

            $data['coupon_status'] = $this->config->get('total_coupon_status');

            if (isset($data['coupon'])) {
                $data['coupon'] = $data['coupon'];
            } elseif (isset($this->session->data['coupon'])) {
                $data['coupon'] = $this->session->data['coupon'];
            } else {
                $data['coupon'] = '';
            }

            $data['voucher_status'] = $this->config->get('total_voucher_status');

            if (isset($data['voucher'])) {
                $data['voucher'] = $data['voucher'];
            } elseif (isset($this->session->data['voucher'])) {
                $data['voucher'] = $this->session->data['voucher'];
            } else {
                $data['voucher'] = '';
            }

            $data['reward_status'] = ($points && $points_total && $this->config->get('total_reward_status'));

            if (isset($data['reward'])) {
                $data['reward'] = $data['reward'];
            } elseif (isset($this->session->data['reward'])) {
                $data['reward'] = $this->session->data['reward'];
            } else {
                $data['reward'] = '';
            }

            // Totals
            $this->load->model('setting/extension');

            $totals = array();

            $total = 0;
            $taxes = $this->cart->getTaxes();
            $sort_order = array();

            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {

                $total_data = array(
                    'totals' => &$totals,
                    'taxes' => &$taxes,
                    'total' => &$total
                );

                $sort_order = array();

                $results = $this->model_setting_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = array();

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);

                $data['totals'] = array();

                foreach ($totals as $t) {
                    $data['totals'][] = array(
                        'title' => $t['title'],
                        'text' => $this->currency->format($t['value'], $this->currency->getRestCurrencyCode()),
                        'value' => $t['value']
                    );
                }


            }

            $data['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->currency->getRestCurrencyCode()));
            $data['total_raw'] = $total;
            $data['total_product_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);

            $data['has_shipping'] = (int)$this->cart->hasShipping();
            $data['has_download'] = (int)$this->cart->hasDownload();
            $data['has_recurring_products'] = $this->cart->hasRecurringProducts();

            $data['currency'] = array(
                'currency_id'   => $this->currency->getId($this->currency->getRestCurrencyCode()),
                'symbol_left'   => $this->currency->getSymbolLeft($this->currency->getRestCurrencyCode()),
                'symbol_right'  => $this->currency->getSymbolRight($this->currency->getRestCurrencyCode()),
                'decimal_place' => $this->currency->getDecimalPlace($this->currency->getRestCurrencyCode()),
                'value'         => $this->currency->getValue($this->currency->getRestCurrencyCode())
            );

            $this->json["data"] = $data;

        }
    }


    private function addItemCart($data)
    {


        $this->language->load('checkout/cart');

        if (isset($data['product_id'])) {
            $product_id = $data['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if (isset($data['quantity'])) {
                $quantity = $data['quantity'];
            } else {
                $quantity = 1;
                $data['quantity'] = 1;
            }


            if (isset($data['option'])) {
                $option = array_filter($data['option']);
            } else {
                $option = array();
            }

            if ($quantity < $product_info['minimum'])
            {
                $this->json['error'][] = sprintf($this->language->get('error_minimum'), $product_info['name'], $product_info['minimum']);
            }

            $product_options = $this->model_catalog_product->getProductOptions($data['product_id']);


            if (isset($data['options_child_not_required']) && $data['options_child_not_required']) {
                $pcop_not_required =  explode(',', $data['options_child_not_required']);

                foreach ($product_options as &$product_option) {
                    if ( in_array($product_option['product_option_id'], $pcop_not_required) ) {
                        $product_option['required'] = false;
                        unset($data['copu_product_id'][$product_option['product_option_id']]); // custom file upload
                    }
                }
                unset($product_option);
            }


            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                    $this->json['error'][] = sprintf($this->language->get('error_required'), $product_option['name']);
                }
            }

            if (isset($data['recurring_id'])) {
                $recurring_id = $data['recurring_id'];
            } else {
                $recurring_id = 0;
            }

            $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

            if ($recurrings) {
                $recurring_ids = array();

                foreach ($recurrings as $recurring) {
                    $recurring_ids[] = $recurring['recurring_id'];
                }

                if (!in_array($recurring_id, $recurring_ids)) {
                    $this->json['error'][] = $this->language->get('error_recurring_required');
                }
            }

            if (empty($this->json['error'])) {

                $this->cart->add($data['product_id'], $data['quantity'], $option, $recurring_id);

                $this->json["data"]['product']['product_id'] = $product_info['product_id'];
                $this->json["data"]['product']['name'] = isset($product_info['name']) ? $product_info['name'] : "";
                $this->json["data"]['product']['quantity'] = $quantity;

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);

                // Totals
                $this->load->model('setting/extension');

                $total_data = array();
                $totals = array();
                $total = 0;
                $taxes = $this->cart->getTaxes();

                // Because __call can not keep var references so we put them into an array.
                $total_data = array(
                    'totals' => &$totals,
                    'taxes' => &$taxes,
                    'total' => &$total
                );


                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $sort_order = array();

                    $results = $this->model_setting_extension->getExtensions('total');

                    foreach ($results as $key => $value) {
                        $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                    }

                    array_multisort($sort_order, SORT_ASC, $results);

                    foreach ($results as $result) {
                        if ($this->config->get('total_' . $result['code'] . '_status')) {
                            $this->load->model('extension/total/' . $result['code']);
                            $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                        }
                    }
                }


                $sort_order = array();

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);

                $this->json['data']['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->currency->getRestCurrencyCode()));
                $this->json['data']['total_product_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
                $this->json['data']['total_price'] = $this->currency->format($total, $this->currency->getRestCurrencyCode());
            }

        } else {
            $this->json['error'][] = "Product not found";
            $this->statusCode = 400;
        }
    }

    /*
     * Deprecated
     *
     *  */
    private function updateCart($data)
    {

        $this->load->model('catalog/product');

        if (!empty($data['quantity'])) {

            foreach ($data['quantity'] as $key => $value) {
                $this->cart->update($key, $value);
            }

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);
        } else {
            $this->statusCode = 400;
            $this->json['error'][] = "Quantity is required";
        }
    }

    public function deleteCartItem($data, $key = null)
    {

        $this->language->load('checkout/cart');
        $this->load->model('catalog/product');

        $id = null;

        if(!empty($key)){
            $id = $key;
        } else {
            if (isset($data['product_id'])) {
                $id = $data['product_id'];
            }

            if (isset($data['key'])) {
                $id = $data['key'];
            }
        }

        if (!empty($id)) {
            $this->cart->remove($id);

            unset($this->session->data['vouchers'][$id]);

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);

            // Totals
            $this->load->model('setting/extension');

            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;

            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );


            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();

                $results = $this->model_setting_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = array();

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);
            }

            $this->json["data"]['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->currency->getRestCurrencyCode()));
            $this->json["data"]['total_product_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
            $this->json["data"]['total_price'] = $this->currency->format($total, $this->currency->getRestCurrencyCode());
        } else {
            $this->statusCode = 400;
            $this->json['error'][] = "No item found in cart with id: " . $id;
        }
    }

    public function updateCartV2()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

            $post = $this->getPost();

            $this->updateCartQuantity($post);

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("PUT");
        }
        $this->sendResponse();
    }

//    private function updateCartQuantity($post)
//    {
//
//        if (isset($post['quantity']) && isset($post['key'])) {
//            $this->language->load('checkout/cart');
//
//            $this->load->model('catalog/product');
//
//            if (isset($post['product_id'])) {
//                $product_info = $this->model_catalog_product->getProduct($post['product_id']);
//                if ($post['quantity'] < $product_info['minimum'])
//                {
//                    $this->json['error'][] = sprintf($this->language->get('error_minimum'), $product_info['name'], $product_info['minimum']);
//                } else {
//                    $item = $this->cart->update($post['key'], (int)$post['quantity']);
//                    if ($item == -1){
//                        $this->json['error'][] = $this->language->get('error_product_not_exist');
//                        $this->statusCode = 400;
//                    } else {
//                        unset($this->session->data['shipping_method']);
//                        unset($this->session->data['shipping_methods']);
//                        unset($this->session->data['payment_method']);
//                        unset($this->session->data['payment_methods']);
//                        unset($this->session->data['reward']);
//                    }
//                }
//
//            } else {
//                $this->json['error'][] = $this->language->get('error_product_not_found');
//                $this->statusCode = 404;
//            }
//
//        } else {
//            $this->json['error'][] = $this->language->get('error_quantity_key');
//            $this->statusCode = 400;
//        }
//    }



    private function updateCartQuantity($post)
    {

        if (isset($post['quantity']) && isset($post['key'])) {
            $this->language->load('checkout/cart');



            $this->deleteCartItem(array(), $post['key']);
            $this->addItemCart($post);




            // $item = $this->cart->update($post['key'], (int)$post['quantity']);
            // if ($item == -1){
            //     $this->json['error'][] = $this->language->get('error_product_not_exist');
            //     $this->statusCode = 400;
            // } else {
            //     unset($this->session->data['shipping_method']);
            //     unset($this->session->data['shipping_methods']);
            //     unset($this->session->data['payment_method']);
            //     unset($this->session->data['payment_methods']);
            //     unset($this->session->data['reward']);
            // }

        } else {
            $this->json['error'][] = $this->language->get('error_quantity_key');
            $this->statusCode = 400;
        }
    }




    public function emptycart()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $this->cart->clear();
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("DELETE");
        }

        $this->sendResponse();
    }


    public function coupon()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->load->language('extension/total/coupon');
            $post = $this->getPost();

            if (isset($post["coupon"]) && $this->validateCoupon($post["coupon"])) {
                $this->session->data['coupon'] = $post["coupon"];
                $this->json['message'][] = sprintf($this->language->get('text_coupon'), $this->session->data['coupon']);
            } else {
                $this->statusCode = 400;
            }

        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

            if (isset($this->session->data['coupon'])) {
                unset($this->session->data['coupon']);
            } else {
                $this->load->language('extension/total/coupon');

                $this->statusCode = 404;
                $this->json['error'][] = $this->language->get('error_coupon_not_found');
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST", "DELETE");
        }
        $this->sendResponse();
    }

    private function validateCoupon($coupon)
    {

        $this->load->language('extension/total/coupon');
        $this->load->model('extension/total/coupon');

        $coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

        if (!$coupon_info) {
            $this->json['error'][] = $this->language->get('error_coupon');
        }

        if (empty($this->json['error'])) {
            return true;
        } else {
            return false;
        }
    }

    public function voucher()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->load->language('extension/total/voucher');
            $post = $this->getPost();

            if (isset($post["voucher"]) && $this->validateVoucher($post["voucher"])) {
                $this->session->data['voucher'] = $post["voucher"];
                $this->json['message'][] = sprintf($this->language->get('text_voucher'), $this->session->data['voucher']);
            } else {
                $this->statusCode = 400;
            }

        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            if (isset($this->session->data['voucher'])) {
                unset($this->session->data['voucher']);
            } else {
                $this->statusCode = 404;
                $this->json['error'][] = "No voucher found.";
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST", "DELETE");
        }
        $this->sendResponse();
    }


    private function validateVoucher($voucher)
    {

        $this->load->language('extension/total/voucher');
        $this->load->model('extension/total/voucher');
        $voucher_info = $this->model_extension_total_voucher->getVoucher($voucher);

        if (!$voucher_info) {
            $this->json['error'][] = $this->language->get('error_voucher');
        }

        if (empty($this->json['error'])) {
            return true;
        } else {
            return false;
        }
    }


    public function reward()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->load->language('extension/total/reward');
            $post = $this->getPost();

            if (isset($post["reward"]) && $this->validateReward($post["reward"])) {

                $this->session->data['reward'] = abs($post["reward"]);
                $this->json['message'][] = sprintf($this->language->get('text_reward'), $this->session->data['reward']);
            } else {
                $this->statusCode = 400;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            if (isset($this->session->data['reward'])) {
                unset($this->session->data['reward']);
            } else {
                $this->statusCode = 404;
                $this->json['error'][] = "No reward found.";
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST", "DELETE");
        }
        $this->sendResponse();
    }


    private function validateReward($reward)
    {
        $this->auth();

        $this->language->load('checkout/cart');
        $points = $this->customer->getRewardPoints();

        $points_total = 0;

        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }


        if (empty($reward)) {
            $this->json['error'][] = $this->language->get('error_reward');
        }

        if ($reward > $points) {
            $this->json['error'][] = sprintf($this->language->get('error_points'), $reward);
        }

        if ($reward > $points_total) {
            $this->json['error'][] = sprintf($this->language->get('error_maximum'), $points_total);
        }

        if (empty($this->json['error'])) {
            return true;
        } else {
            return false;
        }
    }

    public function bulkcart()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->addItemsToCart($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }

        $this->sendResponse();
    }

    public function addItemsToCart($products)
    {
        $this->load->model('catalog/product');

        foreach ($products as $product) {
            $this->addItemCart($product);
            if (!empty($this->json['error'])) {
                $this->statusCode = 400;
                break;
            }
        }
    }


    public function shippingquotes()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (isset($this->request->get['id']) && !empty($this->request->get['id'])) {
                $this->shipping($this->request->get['id']);
            } else {
                $this->statusCode = 400;
                $this->json['error'][] = "Id is required";
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->quote($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST", "PUT");
        }

        $this->sendResponse();
    }

    public function shipping($shipping_method)
    {


        $this->load->language('extension/total/shipping');

        if (!empty($shipping_method)) {
            $shipping = explode('.', $shipping_method);

            if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $this->json['error'][] = $this->language->get('error_shipping');
            }
        } else {
            $this->json['error'][] = $this->language->get('error_shipping');
        }

        if (empty($this->json['error'])) {
            $shipping = explode('.', $shipping_method);
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
        }
    }

    public function quote($post)
    {

        $this->load->language('total/shipping');
        $this->load->language('extension/total/shipping');

        if (!$this->cart->hasProducts()) {
            $this->json['error'][] = "Your cart is empty or a product is out of stock.";
        }

        if (!$this->cart->hasShipping()) {
            $this->json['error'][] = $this->language->get('error_no_shipping');
        }

        if ($post['country_id'] == '') {
            $this->json['error'][] = $this->language->get('error_country');
        }

        if (!isset($post['zone_id']) || $post['zone_id'] == '') {
            $this->json['error'][] = $this->language->get('error_zone');
        }

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($post['country_id']);

        if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($post['postcode'])) < 2 || utf8_strlen(trim($post['postcode'])) > 10)) {
            $this->json['error'][] = $this->language->get('error_postcode');
        }

        if (empty($this->json['error'])) {
            $this->tax->setShippingAddress($post['country_id'], $post['zone_id']);

            if ($country_info) {
                $country = $country_info['name'];
                $iso_code_2 = $country_info['iso_code_2'];
                $iso_code_3 = $country_info['iso_code_3'];
                $address_format = $country_info['address_format'];
            } else {
                $country = '';
                $iso_code_2 = '';
                $iso_code_3 = '';
                $address_format = '';
            }

            $this->load->model('localisation/zone');

            $zone_info = $this->model_localisation_zone->getZone($post['zone_id']);

            if ($zone_info) {
                $zone = $zone_info['name'];
                $zone_code = $zone_info['code'];
            } else {
                $zone = '';
                $zone_code = '';
            }

            $this->session->data['shipping_address'] = array(
                'firstname' => '',
                'lastname' => '',
                'company' => '',
                'address_1' => '',
                'address_2' => '',
                'postcode' => $post['postcode'],
                'city' => '',
                'zone_id' => $post['zone_id'],
                'zone' => $zone,
                'zone_code' => $zone_code,
                'country_id' => $post['country_id'],
                'country' => $country,
                'iso_code_2' => $iso_code_2,
                'iso_code_3' => $iso_code_3,
                'address_format' => $address_format
            );

            $quote_data = array();

            $this->load->model('setting/extension');

            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);

                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

                    if ($quote) {
                        $quote_data[$result['code']] = array(
                            'title'      => $quote['title'],
                            'quote'      => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error'      => $quote['error']
                        );
                    }
                }
            }

            $sort_order = array();

            foreach ($quote_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $quote_data);

            $this->session->data['shipping_methods'] = $quote_data;

            if ($this->session->data['shipping_methods']) {
                $this->json["data"]['shipping_method'] = $this->session->data['shipping_methods'];
            } else {
                $this->json['error'][] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
            }
        }
    }

}