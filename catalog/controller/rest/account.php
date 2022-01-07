<?php
/**
 * account.php
 *
 * Account management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */

require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestAccount extends RestController
{

    private $error = array();

    public function account()
    {

        $this->checkPlugin();
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get account details
            $this->getAccount();
        } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            //modify account
            $post = $this->getPost();
            $this->saveAccount($post);

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "PUT");
        }

        return $this->sendResponse();
    }

    public function getAccount()
    {

        $this->language->load('account/edit');

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        }

        if (empty($this->json['error'])) {

            $this->load->model('account/customer');

            $user = $this->model_account_customer->getCustomer($this->customer->getId());

            // Custom Fields
            $this->load->model('account/custom_field');
            $this->load->model('account/reward');

            $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'account') {
                    $user['custom_fields'][] = $custom_field;
                }
            }

            if ($this->opencartVersion < 2100) {
                $user['account_custom_field'] = unserialize($user['custom_field']);
            } else {
                $user['account_custom_field'] = json_decode($user['custom_field'], true);
            }

            $reward_total = $this->model_account_reward->getTotalRewards();
            $user['reward_total'] = $reward_total;

            $filter_data = array(
                'sort' => 'date_added',
                'order' => 'DESC'
            );

            $results = $this->model_account_reward->getRewards($filter_data);

            if (!empty($results)) {
                foreach ($results as $result) {
                    $user['rewards'][] = array(
                        'order_id' => $result['order_id'],
                        'points' => $result['points'],
                        'description' => $result['description'],
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    );
                }
            }

            unset($user["password"]);
            unset($user["salt"]);
            unset($user['custom_field']);

            $user['user_balance'] = $this->currency->format($this->customer->getBalance(), $this->currency->getRestCurrencyCode());

            $this->json["data"] = $user;
        }
    }

    public function saveAccount($post)
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            if ($this->validate($post)) {
                $customer = $this->model_account_customer->getCustomer($this->customer->getId());

                $this->load->model('account/custom_field');

                $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

                foreach ($custom_fields as $custom_field) {
                    if ($custom_field['location'] == 'account') {
                        $customer['custom_fields'][] = $custom_field;
                    }
                }

                if ($this->opencartVersion < 2100) {
                    $customer['custom_field'] = unserialize($customer['custom_field']);
                } else {
                    $customer['custom_field'] = json_decode($customer['custom_field'], true);
                }

                $this->load->model('account/customer');
                $post = array_merge($customer, $post);

                $this->model_account_customer->editCustomer($this->customer->getId(), $post);
            } else {
                $this->json['error'][] = $this->error;
                $this->statusCode = 400;
            }
        }

    }

    protected function validate($post)
    {

        $this->load->model('account/customer');
        $this->language->load('account/edit');

        if (isset($post['lastname'])) {
            if ((utf8_strlen($post['firstname']) < 1) || (utf8_strlen($post['firstname']) > 32)) {
                $this->error[] = $this->language->get('error_firstname');
            }
        }

        if (isset($post['lastname'])) {
            if ((utf8_strlen($post['lastname']) < 1) || (utf8_strlen($post['lastname']) > 32)) {
                $this->error[] = $this->language->get('error_lastname');
            }
        }

        if (isset($post['email'])) {

            if ((utf8_strlen($post['email']) > 96) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $post['email'])) {
                $this->error[] = $this->language->get('error_email');
            }
            if (!isset($post['email']) || ($this->customer->getEmail() != $post['email']) && $this->model_account_customer->getTotalCustomersByEmail($post['email'])) {
                $this->error[] = $this->language->get('error_exists');
            }
        }

        if (isset($post['telephone'])) {
            if ((utf8_strlen($post['telephone']) < 3) || (utf8_strlen($post['telephone']) > 32)) {
                $this->error[] = $this->language->get('error_telephone');
            }
        }

        // Custom field validation
        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if($this->opencartVersion < 2300) {
                if (($custom_field['location'] == 'account') && $custom_field['required'] && empty($post['custom_field'][$custom_field['custom_field_id']])) {
                    $this->error[] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
            } else {
                if ($custom_field['location'] == 'account') {
                    if ($custom_field['required'] && empty($post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                        $this->error[] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) &&
                        !filter_var($post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                        $this->error[] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                    }
                }
            }
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function password()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            //modify account password
            $post = $this->getPost();
            $this->changePassword($post);

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("PUT");
        }

        return $this->sendResponse();

    }

    public function changePassword($post)
    {

        $this->load->language('account/password');
        $this->auth();

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            if (!isset($post['password']) || (utf8_strlen($post['password']) < 4) || (utf8_strlen($post['password']) > 20)) {
                $this->json["error"][] = $this->language->get('error_password');
            }

            if (!isset($post['confirm']) || (isset($post['password']) && $post['confirm'] != $post['password'])) {
                $this->json["error"][] = $this->language->get('error_confirm');
            }

            if (empty($this->json["error"])) {
                $this->load->model('account/customer');

                $this->model_account_customer->editPassword($this->customer->getEmail(), $post['password']);

            } else {
                $this->statusCode = 400;
            }
        }

    }


    public function customfield()
    {
        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->load->model('account/custom_field');

            // Customer Group
            if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                $customer_group_id = $this->request->get['customer_group_id'];
            } else {
                $customer_group_id = $this->config->get('config_customer_group_id');
            }

            $this->json['data'] = $this->model_account_custom_field->getCustomFields($customer_group_id);

            if($this->includeMeta) {
                $this->response->addHeader('X-Total-Count: ' . count($this->json['data']));
                $this->response->addHeader('X-Pagination-Limit: '.count($this->json['data']));
                $this->response->addHeader('X-Pagination-Page: 1');
//                $data = $this->json['data'];
//
//                $this->json['data'] = array(
//                    'totalrowcount' => count($data),
//                    'pagenumber'    => 1,
//                    'pagesize'      => count($data),
//                    'items'         => $data
//                );
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET");
        }

        return $this->sendResponse();
    }

    public function address()
    {

        $this->checkPlugin();

        $this->auth();

        $this->language->load('account/address');

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                    $this->getAddress($this->request->get['id']);
                } else {
                    $this->listAddress();
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $post = $this->getPost();
                $this->addAddress($post);
            } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $post = $this->getPost();

                if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                     $this->editAddress($this->request->get['id'], $post);
                } else {
                    $this->statusCode = 400;
                    $this->json['error'][] = "Invalid identifier.";
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                    $this->deleteAddress($this->request->get['id']);
                } else {
                    $this->statusCode = 400;
                    $this->json['error'][] = "Invalid identifier.";
                }
            }
        }
        return $this->sendResponse();
    }

    private function getAddress($id)
    {

        $this->load->model('account/address');

        $address = $this->model_account_address->getAddress($id);
        if (!empty($address)) {
            // Custom Fields
            $this->load->model('account/custom_field');

            if ($this->customer->getAddressId() == $address['address_id']) {
                $address['default'] = true;
            } else {
                $address['default'] = false;
            }


            $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'address') {
                    $address['custom_fields'][] = $custom_field;
                }
            }

            $this->json["data"] = $address;
        } else {
            $this->json['error'][] = "Address not found";
            $this->statusCode = 404;
        }

    }

    private function listAddress()
    {

        $this->load->model('account/address');

        $data['addresses'] = $this->model_account_address->getAddresses();
        $data['addresses'] = array_values($data['addresses']);

        // Custom Fields
        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'address') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        if (!empty($data['addresses'])) {

            foreach ($data['addresses'] as &$address) {
                if ($this->customer->getAddressId() == $address['address_id']) {
                    $address['default'] = true;
                } else {
                    $address['default'] = false;
                }
            }

            $this->json["data"] = $data;
        }

        if($this->includeMeta) {

            $data = $this->json['data'];

            if(isset($this->json['data']['addresses'])) {
                $addressData = $this->json['data']['addresses'];
            } else {
                $addressData = array();
            }
            $this->response->addHeader('X-Total-Count: ' . count($addressData));
            $this->response->addHeader('X-Pagination-Limit: '.count($addressData));
            $this->response->addHeader('X-Pagination-Page: 1');
//            $this->json['data'] = array(
//                'totalrowcount' => count($addressData),
//                'pagenumber'    => 1,
//                'pagesize'      => count($addressData),
//                'custom_fields' => $data['custom_fields'],
//                'items'         => $addressData
//            );
        }
    }

    private function addAddress($data)
    {

        $this->validateAdress($data);

        $this->load->model('account/address');

        if (empty($this->error["error"])) {
            $data['firstname'] = ' ';
            $data['lastname'] = ' ';
            $data['company'] = ' ';
            $data['telephone'] = ' ';

            $data['address_2'] = ' ';
            $data['postcode'] = ' ';
            $id = $this->model_account_address->addAddress($this->customer->getId(), $data);

            if ($id) {
                $this->json["data"]['address_id'] = $id;
            } else {
                $this->statusCode = 400;
            }
        } else {

            $this->json["error"] = $this->error["error"];

            $this->statusCode = 400;
        }
    }

    public function validateAdress($post)
    {

        // if (empty($post['name']) || (utf8_strlen($post['name']) < 3) || (utf8_strlen($post['name']) > 32)) {
        //     $this->error['error'][] = $this->language->get('error_name');
        // }

        // if (empty($post['telephone']) || (utf8_strlen($post['telephone']) < 3) || (utf8_strlen($post['telephone']) > 32)) {
        //     $this->error['error'][] = $this->language->get('error_telephone');
        // }


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
    }

    private function editAddress($id, $data)
    {
        $this->load->model('account/address');

        $address = $this->model_account_address->getAddress($id);
        if (!empty($address)) {
            // Custom Fields
            $this->load->model('account/custom_field');

            $address['custom_fields'] = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
            $data = array_merge($address, $data);

        } else {
            $this->json["error"][] = $this->language->get('error_address_not_found');
        }

        $this->validateAdress($data);

        if (empty($this->json["error"])) {
            $this->model_account_address->editAddress($id, $data);
        } else {
            $this->statusCode = 400;
        }
    }

    private function deleteAddress($id)
    {

        $this->load->model('account/address');
        $this->model_account_address->deleteAddress($id);
    }

    public function newsletter()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (isset($this->request->get['subscribe']) && ctype_digit($this->request->get['subscribe'])) {
                $this->subscribe($this->request->get['subscribe']);
            } else {
                $this->statusCode = 400;
                $this->json['error'][] = "Invalid identifier.";
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("PUT");
        }

        return $this->sendResponse();
    }

    public function subscribe($subscribe)
    {
        $this->auth();

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            $this->load->model('account/customer');
            $this->model_account_customer->editNewsletter($subscribe);
        }
    }

    public function transactions()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->loadTransactions();
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET");
        }

        return $this->sendResponse();
    }


    public function loadTransactions()
    {

        $this->auth();

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            $this->load->model('account/transaction');
            $this->language->load('account/transaction');

            $filter = array(
                'sort' => 'date_added',
                'order' => 'DESC'
            );

            $results = $this->model_account_transaction->getTransactions($filter);

            if (!empty($results)) {
                $this->json['data']['user_balance'] = $this->currency->format($this->customer->getBalance(), $this->currency->getRestCurrencyCode());

                foreach ($results as $result) {
                    $this->json['data']['transactions'][] = array(
                        'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                        'description' => $result['description'],
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
                    );
                }
            }

            if($this->includeMeta) {

                if(isset($this->json['data']['transactions'])) {
                    $transactionData = $this->json['data']['transactions'];
                } else {
                    $transactionData = array();
                }
                $this->response->addHeader('X-Total-Count: ' . count($transactionData));
                $this->response->addHeader('X-Pagination-Limit: '.count($transactionData));
                $this->response->addHeader('X-Pagination-Page: 1');
//                if(isset($this->json['data']['user_balance'])) {
//                    $userBalance = $this->json['data']['user_balance'];
//                } else {
//                    $userBalance = array();
//                }

//                $this->json['data'] = array(
//                    'totalrowcount' => count($transactionData),
//                    'pagenumber'    => 1,
//                    'pagesize'      => count($transactionData),
//                    'user_balance'  => $userBalance,
//                    'items'         => $transactionData
//                );
            }
        }
    }

    public function voucher()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->addVoucher($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }

        return $this->sendResponse();
    }


    public function addVoucher($post)
    {

        $this->load->language('account/voucher');

        if ($this->validateVoucher($post)) {

            if ($this->opencartVersion < 2200) {
                $description = sprintf($this->language->get('text_for'), $this->currency->format($this->currency->convert($post['amount'], $this->currency->getCode(), $this->config->get('config_currency')), $this->currency->getRestCurrencyCode()), $post['to_name']);
                $amount = $this->currency->convert($post['amount'], $this->currency->getCode(), $this->config->get('config_currency'));
            } elseif ($this->opencartVersion < 2300 && $this->opencartVersion >= 2200) {
                $description = sprintf($this->language->get('text_for'), $this->currency->format($this->currency->convert($post['amount'], $this->currency->getRestCurrencyCode(), $this->config->get('config_currency')), $this->currency->getRestCurrencyCode()), $post['to_name']);
                $amount = $this->currency->convert($post['amount'], $this->currency->getRestCurrencyCode(), $this->config->get('config_currency'));
            } else {
                $description = sprintf($this->language->get('text_for'), $this->currency->format($post['amount'], $this->currency->getRestCurrencyCode()), $post['to_name']);
                $amount = $this->currency->convert($post['amount'], $this->currency->getRestCurrencyCode(), $this->config->get('config_currency'));
            }

            $this->session->data['vouchers'][mt_rand()] = array(
                'description' => $description,
                'to_name' => $post['to_name'],
                'to_email' => $post['to_email'],
                'from_name' => $post['from_name'],
                'from_email' => $post['from_email'],
                'voucher_theme_id' => $post['voucher_theme_id'],
                'message' => $post['message'],
                'amount' => $amount
            );

        } else {
            $this->json['error'] = $this->error;
            $this->statusCode = 400;
        }

    }

    protected function validateVoucher($post)
    {
        if (!isset($post['to_name']) || (utf8_strlen($post['to_name']) < 1) || (utf8_strlen($post['to_name']) > 64)) {
            $this->error[] = $this->language->get('error_to_name');
        }

        if (!isset($post['to_email']) || (utf8_strlen($post['to_email']) > 96) || !filter_var($post['to_email'], FILTER_VALIDATE_EMAIL)) {
            $this->error[] = $this->language->get('error_email');
        }

        if (!isset($post['from_name']) || (utf8_strlen($post['from_name']) < 1) || (utf8_strlen($post['from_name']) > 64)) {
            $this->error[] = $this->language->get('error_from_name');
        }

        if (!isset($post['from_email']) || (utf8_strlen($post['from_email']) > 96) || !filter_var($post['from_email'], FILTER_VALIDATE_EMAIL)) {
            $this->error[] = $this->language->get('error_email');
        }

        if (!isset($post['voucher_theme_id'])) {
            $this->error[] = $this->language->get('error_theme');
        }

        if (!isset($post['amount'])) {
            $this->error[] = "Amount is required.";
        } else {

            if (($this->currency->convert($post['amount'], $this->currency->getRestCurrencyCode(), $this->config->get('config_currency')) < $this->config->get('config_voucher_min')) || ($this->currency->convert($post['amount'], $this->currency->getRestCurrencyCode(), $this->config->get('config_currency')) > $this->config->get('config_voucher_max'))) {
                $this->error[] = sprintf($this->language->get('error_amount'), $this->currency->format($this->config->get('config_voucher_min'), $this->currency->getRestCurrencyCode()), $this->currency->format($this->config->get('config_voucher_max'), $this->currency->getRestCurrencyCode()));
            }
        }

        return !$this->error;
    }

    public function voucherthemes()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->load->model('extension/total/voucher_theme');
            $this->json['data'] = $this->model_extension_total_voucher_theme->getVoucherThemes();

            if($this->includeMeta) {
                $this->response->addHeader('X-Total-Count: ' . count($this->json['data']));
                $this->response->addHeader('X-Pagination-Limit: '.count($this->json['data']));
                $this->response->addHeader('X-Pagination-Page: 1');
//                $data = $this->json['data'];
//
//                $this->json['data'] = array(
//                    'totalrowcount' => count($data),
//                    'pagenumber'    => 1,
//                    'pagesize'      => count($data),
//                    'items'         => $data
//                );
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET");
        }

        return $this->sendResponse();
    }

    public function downloads()
    {

        $this->checkPlugin();
        $this->auth();

        $this->language->load('account/address');

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                    $this->getDownload($this->request->get['id']);
                } else {
                    $this->listDownloads();
                }
            }
        }
        return $this->sendResponse();
    }

    private function getDownload($id)
    {

        $this->load->model('account/download');

        $download_info = $this->model_account_download->getDownload($id);

        if ($download_info) {
            $file = DIR_DOWNLOAD . $download_info['filename'];
            $mask = basename($download_info['mask']);

            if (!headers_sent()) {
                if (file_exists($file)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));

                    if (ob_get_level()) {
                        ob_end_clean();
                    }

                    readfile($file, 'rb');

                    if ($this->opencartVersion >= 3100) {
                        $this->model_account_download->addDownloadReport($id, $this->request->server['REMOTE_ADDR']);
                    }

                    exit();
                } else {
                    $this->json['error'][] = 'Could not find file ' . $file . '!';
                    $this->statusCode = 400;
                }
            } else {
                $this->json['error'][] = 'Could not render file';
                $this->statusCode = 400;
            }
        } else {
            $this->json['error'][] = "File does not exist";
            $this->statusCode = 404;
        }
    }

    private function listDownloads()
    {

        $this->load->model('account/download');

        $data['downloads'] = array();

        $start = 0;
        $limit = 100;
        $page = 1;

        /*check limit parameter*/
        if (isset($this->request->get['limit']) && ctype_digit($this->request->get['limit'])) {
            $limit = $this->request->get['limit'];
        }

        /*check page parameter*/
        if (isset($this->request->get['page']) && ctype_digit($this->request->get['page'])) {
            $start  = $this->request->get['page'];
            $page   = $this->request->get['page'];
        }

        $results = $this->model_account_download->getDownloads($start, $limit);

        foreach ($results as $result) {
            if (file_exists(DIR_DOWNLOAD . $result['filename'])) {
                $size = filesize(DIR_DOWNLOAD . $result['filename']);

                $i = 0;

                $suffix = array(
                    'B',
                    'KB',
                    'MB',
                    'GB',
                    'TB',
                    'PB',
                    'EB',
                    'ZB',
                    'YB'
                );

                while (($size / 1024) > 1) {
                    $size = $size / 1024;
                    $i++;
                }

                $download_info = $this->model_account_download->getDownload($result['download_id']);

                $data['downloads'][] = array(
                    'order_id'   => $result['order_id'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'name'       => $result['name'],
                    'mask'       => $download_info['mask'],
                    'size'       => round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i],
                    'href'       => $this->url->link('account/download/download', 'download_id=' . $result['download_id'], true),
                    'download_url' => $this->url->link('rest/account/downloads', 'id=' . $result['download_id'], true)
                );
            }
        }

        if (!empty($data['downloads'])) {
            $this->json["data"] = $data['downloads'];
        }

        if($this->includeMeta) {

            $total = $this->model_account_download->getTotalDownloads();
            $this->response->addHeader('X-Total-Count: ' . (int)$total);
            $this->response->addHeader('X-Pagination-Limit: '.(int)$limit);
            $this->response->addHeader('X-Pagination-Page: '.(int)$page);
//            $data = $this->json['data'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => (int)$total,
//                'pagenumber'    => (int)$page,
//                'pagesize'      => (int)$limit,
//                'items'         => $data
//            );
        }
    }

    public function recurrings()
    {

        $this->checkPlugin();
        $this->auth();

        $this->load->language('account/recurring');

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                    $this->getRecurring($this->request->get['id']);
                } else {
                    $this->listRecurrings();
                }
            }
        }

        return $this->sendResponse();
    }

    private function listRecurrings()
    {

        $start = 0;
        $limit = 100;
        $page = 1;

        /*check limit parameter*/
        if (isset($this->request->get['limit']) && ctype_digit($this->request->get['limit'])) {
            $limit = $this->request->get['limit'];
        }

        /*check page parameter*/
        if (isset($this->request->get['page']) && ctype_digit($this->request->get['page'])) {
            $start = $this->request->get['page'];
            $page = $this->request->get['page'];
        }

        $data['recurrings'] = array();

        $this->load->model('account/recurring');
        $this->load->model('catalog/product');

        $results = $this->model_account_recurring->getOrderRecurrings($start, $limit);

        foreach ($results as $result) {
            if ($result['status']) {
                $status = $this->language->get('text_status_' . $result['status']);
            } else {
                $status = '';
            }

            $product_seo_url = $this->model_catalog_product->getProductSeoUrls($result['product_id']);

            $data['product_seo_url'] = $product_seo_url;

            $data['recurrings'][] = array(
                'order_recurring_id' => $result['order_recurring_id'],
                'product'            => $result['product_name'],
                'product_seo_url'    => $product_seo_url,
                'status'             => $status,
                'date_added'         => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
            );
        }

        if (!empty($data['recurrings'])) {
            $this->json["data"] = $data['recurrings'];
        }

        if($this->includeMeta) {

            $total = $this->model_account_recurring->getTotalOrderRecurrings();
            $this->response->addHeader('X-Total-Count: ' . (int)$total);
            $this->response->addHeader('X-Pagination-Limit: '.(int)$limit);
            $this->response->addHeader('X-Pagination-Page: '.(int)$page);
//            $data = $this->json['data'];
//
//            $this->json['data'] = array(
//                'totalrowcount' => (int)$total,
//                'pagenumber'    => (int)$page,
//                'pagesize'      => (int)$limit,
//                'items'         => $data
//            );
        }
    }

    private function getRecurring($id)
    {
        $this->load->model('account/recurring');
        $this->load->model('catalog/product');

        $recurring_info = $this->model_account_recurring->getOrderRecurring($id);

        $data = array();

        if ($recurring_info) {

            $data['order_recurring_id'] = $id;
            $data['date_added'] = date($this->language->get('date_format_short'), strtotime($recurring_info['date_added']));

            if ($recurring_info['status']) {
                $data['status'] = $this->language->get('text_status_' . $recurring_info['status']);
            } else {
                $data['status'] = '';
            }

            $data['payment_method'] = $recurring_info['payment_method'];

            $data['order_id'] = $recurring_info['order_id'];
            $data['product_id'] = $recurring_info['product_id'];
            $data['product_name'] = $recurring_info['product_name'];
            $data['product_quantity'] = $recurring_info['product_quantity'];
            $data['recurring_description'] = $recurring_info['recurring_description'];
            $data['reference'] = $recurring_info['reference'];

            $product_seo_url = $this->model_catalog_product->getProductSeoUrls($recurring_info['product_id']);
            $data['product_seo_url'] = $product_seo_url;

            // Transactions
            $data['transactions'] = array();

            $results = $this->model_account_recurring->getOrderRecurringTransactions($id);

            foreach ($results as $result) {
                $data['transactions'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'type' => $result['type'],
                    'amount' => $this->currency->format($result['amount'], $recurring_info['currency_code'])
                );
            }

            $this->json["data"] = $data;

        } else {
            $this->json['error'][] = "Recurring not found";
            $this->statusCode = 404;
        }
    }
    
    
    
    public function rewards()
    {

        $this->checkPlugin();
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            //get account details
            $this->getRewards();
        }else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET");
        }

        return $this->sendResponse();
    }
    
    
    private function getRewards () {
        
		$this->load->model('account/reward');

		$data['reward_total'] = $this->model_account_reward->getTotalRewards();

		$data['reward_total_sum'] = $this->model_account_reward->getTotalPoints();



		$results = $this->model_account_reward->getRewards();

		foreach ($results as $result) {
			$data['rewards'][] = array(
				'order_id'    => $result['order_id'],
				'points'      => $result['points'],
				'description' => $result['description'],
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'        => $this->url->link('account/order/info', 'order_id=' . $result['order_id'], true)
			);
		}
		
        $this->json["data"] = $data;

        
    }
}