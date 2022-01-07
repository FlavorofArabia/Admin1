<?php

/**
 * contact.php
 *
 * Contact message management
 *
 * @author     Opencart-api.com
 * @copyright  2017
 * @license    License.txt
 * @version    2.0
 * @link       https://opencart-api.com/product/shopping-cart-rest-api/
 * @documentations https://opencart-api.com/opencart-rest-api-documentations/
 */
require_once(DIR_SYSTEM . 'engine/restcontroller.php');

class ControllerRestContact extends RestController
{

    private $error = array();

    public function send()
    {

        $this->checkPlugin();
        $this->auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $postData = $this->getPost();

            $this->language->load('information/contact');

            if ($this->validate($postData)) {

                if ($this->opencartVersion <= 2011) {
                    $mail = new Mail($this->config->get('config_mail'));
                } else {
                    $mail = new Mail();
                    $mail->protocol = $this->config->get('config_mail_protocol');
                    $mail->parameter = $this->config->get('config_mail_parameter');

                    if ($this->opencartVersion == 2020) {
                        $mail->smtp_hostname = $this->config->get('config_mail_smtp_host');
                    } else {
                        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                    }

                    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                    $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                }

                $mail->setTo($this->config->get('config_email'));
                $mail->setFrom($this->config->get('config_email'));
                $mail->setReplyTo($postData['email']);
                $mail->setSender(html_entity_decode($postData['name'], ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode(sprintf($this->language->get('email_subject'), $postData['name']), ENT_QUOTES, 'UTF-8'));
                $mail->setText($postData['enquiry']);
                $mail->send();

            } else {
                $this->statusCode = 400;
                $this->json['error'] = $this->error;
            }
        } else {
            $this->statusCode = 405;
            $this->allowedHeaders = array("POST");
        }

        $this->sendResponse();
    }

    protected function validate($post)
    {
        if (!isset($post['name']) || (utf8_strlen($post['name']) < 3) || (utf8_strlen($post['name']) > 32)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (!isset($post['email']) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $post['email'])) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (!isset($post['enquiry']) || (utf8_strlen($post['enquiry']) < 10) || (utf8_strlen($post['enquiry']) > 3000)) {
            $this->error['enquiry'] = $this->language->get('error_enquiry');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}