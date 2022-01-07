<?php
class ControllerCommonFooter extends Controller {
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
		$this->load->language('common/footer');
        $this->load->language('account/login');
        $this->load->language('account/register');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}
        $this->load->language('common/header');


        $data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
        $data['newsletter_form'] = $this->url->link('account/newsletter/save', '', true);
        $data['product'] = preg_replace("/^http:/i", "https:", $this->url->link('product/product/info'));
        
        
		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');


        $data['facebook'] = $this->config->get('config_facebook');
        $data['twitter'] = $this->config->get('config_twitter');
        $data['instagram'] = $this->config->get('config_instagram');
        $data['whatsapp'] = $this->config->get('config_whatsapp');


        $this->load->model('design/banner');
        $data['footer_menu'] = array();
        $results = $this->model_design_banner->getBannerByKey('footer_menu');

        foreach ($results as $result) {
            $data['footer_menu'][] = array(
                'title' => $result['title'],
                'link'  => $result['link'],
                'image' => $this->request->server['HTTPS']? ($this->config->get('config_ssl') . 'image/' . $result['image'])  : ($this->config->get('config_url') . 'image/' . $result['image'])
            );
        }


        $this->load->model('catalog/category');

        $this->load->model('catalog/product');

        $data['categories'] = array();

        $categories = $this->model_catalog_category->getCategories(0);
        $this->load->model('tool/image');
        foreach ($categories as $category) {
            if ($category['top']) {
                // Level 2
                $children_data = array();

                $children = $this->model_catalog_category->getCategories($category['category_id']);

                foreach ($children as $child) {
                    $filter_data = array(
                        'filter_category_id'  => $child['category_id'],
                        'filter_sub_category' => true
                    );

                    $children_data[] = array(
                        'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                        'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
                    );
                }

                if ($category['image']) {
                    $image = $this->model_tool_image->resize($category['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
                } else {
                    $image = '';
                }

                // Level 1
                $data['categories'][] = array(
                    'name'     => $category['name'],
                    'image'     => $image,
                    'children' => $children_data,
                    'column'   => $category['column'] ? $category['column'] : 1,
                    'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
                );
            }
        }

        $data['login'] = $this->url->link('account/login', '', true);
        $data['register'] = $this->url->link('account/register', '', true);
        $data['forgotten'] = $this->url->link('account/forgotten', '', true);


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
        $data['shopping_cart'] = $this->url->link('checkout/cart');
        $data['checkout'] = $this->url->link('checkout/checkout', '', true);

		return $this->load->view('common/footer', $data);
	}
}
