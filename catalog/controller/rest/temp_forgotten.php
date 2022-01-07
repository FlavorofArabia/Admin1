<?php

/**
 * forgotten.php
 *
 * Forgotten password
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */

require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestForgotten extends RestController
{

    /*
    * forgotten password
    */
    public function forgotten()
    {

        $this->checkPlugin();


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if ($this->customer->isLogged()) {
                $this->json['error'][] = "User is already logged";
                $this->statusCode = 400;
            } else {
                $this->load->model('account/customer');
                $this->load->language('account/forgotten');

                $post = $this->getPost();
                $error = $this->validate($post);

                if (empty($error)) {
                    $code = token(40);
                    $this->model_account_customer->editCode($post['email'], $code);
                } else {
                    $this->json["error"] = $error;
                    $this->statusCode = 400;
                }
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }

        return $this->sendResponse();

    }

    protected function validate($post)
    {
        $error = array();
        if (!isset($post['email'])) {
            $error[] = $this->language->get('error_email');
        } elseif (!$this->model_account_customer->getTotalCustomersByEmail($post['email'])) {
            $error[] = $this->language->get('error_email');
        }
        return $error;
    }
}
