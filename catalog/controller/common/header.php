<?php
class ControllerCommonHeader extends Controller {
	public function index() {
        // << Live Price
        \liveopencart\ext\liveprice::getInstance($this->registry);
        if ( $this->liveopencart_ext_liveprice->installed() ) {
            $liveprice_scripts = $this->liveopencart_ext_liveprice->getProductPageScripts();
            $liveprice_current_scripts = $this->document->getScripts();
            foreach ( $liveprice_scripts as $liveprice_script ) {
                if ( !in_array($liveprice_script, $liveprice_current_scripts) ) {
                    $this->document->addScript( $liveprice_script );
                }
            }
            $liveprice_styles = $this->liveopencart_ext_liveprice->getProductPageStyles();
            $liveprice_current_styles = $this->document->getStyles();
            foreach ( $liveprice_styles as $liveprice_style ) {
                if ( !in_array($liveprice_style, $liveprice_current_styles) ) {
                    $this->document->addStyle( $liveprice_style );
                }
            }
        }
        // >> Live Price
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

        if ($this->customer->isLogged()) {
            $data['login_register_text'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->url->link('account/logout', '', true));
        } else {
            $data['login_register_text'] = sprintf($this->language->get('login_register_text'));
        }
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');


        $categories = $this->model_catalog_category->getCategories(0);

        foreach ($categories as $category) {
            $data['categories'][] = array(
                'category_id'     => $category['category_id'],
                'name'     => $category['name']
            );
        }

        $this->load->model('catalog/information');
        $information_info = $this->model_catalog_information->getInformation($this->config->get('config_top_header_page'));

        $data['desc'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');


        $this->load->model('design/banner');
        $data['top_menu'] = array();
        $results = $this->model_design_banner->getBannerByKey('top_meue');

        foreach ($results as $result) {
            $data['top_menu'][] = array(
                'title' => $result['title'],
                'link'  => $result['link'],
                'image' => $this->request->server['HTTPS']? ($this->config->get('config_ssl') . 'image/' . $result['image'])  : ($this->config->get('config_url') . 'image/' . $result['image'])
            );
        }

        $data['search'] = $this->request->get['search'] ?? null;
        $data['category_search'] = $this->request->get['category'] ?? null;


        return $this->load->view('common/header', $data);
	}
}
