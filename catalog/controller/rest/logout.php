<?php
/**
 * logout.php
 *
 * Logout management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestLogout extends RestController
{

    public function logout()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if ($this->customer->isLogged()) {
                $this->event->trigger('pre.customer.logout');

                $this->load->model('account/customer');
                $account = $this->model_account_customer->resetOtpVerified();

                $this->customer->logout();
                $this->cart->clear();



                unset($this->session->data['wishlist']);
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_address']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
                unset($this->session->data['comment']);
                unset($this->session->data['order_id']);
                unset($this->session->data['coupon']);
                unset($this->session->data['reward']);
                unset($this->session->data['voucher']);
                unset($this->session->data['vouchers']);

                $this->event->trigger('post.customer.logout');
            } else {
                $this->json['error'][] = "User is not logged.";
                $this->statusCode = 400;
            }

        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }

        $this->sendResponse();

    }

}