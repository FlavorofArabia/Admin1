<?php
/**
 * return.php
 *
 * return management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestReturn extends RestController
{

    private $error = array();

    public function returns()
    {

        $this->checkPlugin();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($this->request->get['id']) && ctype_digit($this->request->get['id'])) {
                $this->getReturnInfo($this->request->get['id']);
            } else {
                $this->loadReturns();
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $this->getPost();
            $this->addReturn($post);
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("GET", "POST");
        }

        return $this->sendResponse();
    }

    public function getReturnInfo($return_id)
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        }

        if (empty($this->json['error'])) {

            $this->load->language('account/return');

            $this->load->model('account/return');

            $return_info = $this->model_account_return->getReturn($return_id);

            if ($return_info) {

                $data['return_id'] = $return_info['return_id'];
                $data['order_id'] = $return_info['order_id'];
                $data['date_ordered'] = date($this->language->get('date_format_short'), strtotime($return_info['date_ordered']));
                $data['date_added'] = date($this->language->get('date_format_short'), strtotime($return_info['date_added']));
                $data['firstname'] = $return_info['firstname'];
                $data['lastname'] = $return_info['lastname'];
                $data['email'] = $return_info['email'];
                $data['telephone'] = $return_info['telephone'];
                $data['product'] = $return_info['product'];
                $data['model'] = $return_info['model'];
                $data['quantity'] = $return_info['quantity'];
                $data['reason'] = $return_info['reason'];
                $data['opened'] = $return_info['opened'] ? $this->language->get('text_yes') : $this->language->get('text_no');
                $data['comment'] = nl2br($return_info['comment']);
                $data['action'] = $return_info['action'];

                $data['histories'] = array();

                $results = $this->model_account_return->getReturnHistories($return_info['return_id']);

                foreach ($results as $result) {
                    $data['histories'][] = array(
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'status' => $result['status'],
                        'comment' => nl2br($result['comment'])
                    );
                }

                $this->json['data'] = $data;
            }
        }
    }


    public function loadReturns()
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        }

        if (empty($this->json['error'])) {
            $this->load->model('account/return');
            $this->load->language('account/return');


            $results = $this->model_account_return->getReturns(0, 1000);

            foreach ($results as $result) {
                $this->json["data"][] = array(
                    'return_id' => $result['return_id'],
                    'order_id' => $result['order_id'],
                    'name' => $result['firstname'] . ' ' . $result['lastname'],
                    'status' => $result['status'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
                );
            }

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
        }
    }

    public function addReturn($request)
    {

        if (!$this->customer->isLogged()) {
            $this->json['error'][] = "User is not logged in";
            $this->statusCode = 403;
        }

        if (empty($this->json['error'])) {

            $this->load->language('account/return');

            $this->load->model('account/return');

            if ($this->validate($request)) {

                $request['comment'] =strip_tags($request['comment']);
                $this->json['data']['id'] = $this->model_account_return->addReturn($request);
            } else {
                $this->json['error'] = $this->error;
            }
        }
    }


    protected function validate($post)
    {

        if (!$post['order_id']) {
            $this->error[] = $this->language->get('error_order_id');
        }

        if (!isset($post['firstname']) || (utf8_strlen(trim($post['firstname'])) < 1) || (utf8_strlen(trim($post['firstname'])) > 32)) {
            $this->error[] = $this->language->get('error_firstname');
        }

        if (!isset($post['lastname']) || (utf8_strlen(trim($post['lastname'])) < 1) || (utf8_strlen(trim($post['lastname'])) > 32)) {
            $this->error[] = $this->language->get('error_lastname');
        }

        if (!isset($post['email']) || (utf8_strlen($post['email']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $post['email'])) {
            $this->error[] = $this->language->get('error_email');
        }

        if (!isset($post['telephone']) || (utf8_strlen($post['telephone']) < 3) || (utf8_strlen($post['telephone']) > 32)) {
            $this->error[] = $this->language->get('error_telephone');
        }

        if (!isset($post['product']) || (utf8_strlen($post['product']) < 1) || (utf8_strlen($post['product']) > 255)) {
            $this->error[] = $this->language->get('error_product');
        }

        if (!isset($post['model']) || (utf8_strlen($post['model']) < 1) || (utf8_strlen($post['model']) > 64)) {
            $this->error[] = $this->language->get('error_model');
        }

        if (empty($post['return_reason_id'])) {
            $this->error[] = $this->language->get('error_reason');
        }

        return !$this->error;
    }
}