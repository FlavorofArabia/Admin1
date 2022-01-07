<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featured');

		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		$this->load->model('tool/image');

		$data['products'] = array();

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

        $latestProductsCategory = $this->model_catalog_category->getCategory($this->config->get('config_latest_product'));
        
        if(!empty($latestProductsCategory)){
           $latestProducts = $this->model_catalog_product->getProducts(['filter_category_id' => $latestProductsCategory['category_id']]);
        if (!empty($latestProductsCategory) and !empty($latestProducts))
        {
            $data['latest_products_category'] =$latestProductsCategory['name'];
            foreach ($latestProducts as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }

                    $data['latest_products'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }
        }


        $bestSellingCategory = $this->model_catalog_category->getCategory($this->config->get('config_best_selling'));
        
        
                if(!empty($bestSellingCategory)){

        $bestSellings = $this->model_catalog_product->getProducts(['filter_category_id' => $bestSellingCategory['category_id']]);
        if (!empty($bestSellingCategory) and !empty($bestSellings))
        {
            $data['best_selling_category'] =$bestSellingCategory['name'];
            foreach ($bestSellings as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
                    $data['best_sellings'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }
}



        $mostViewCategory = $this->model_catalog_category->getCategory($this->config->get('config_most_view'));
        
                        if(!empty($bestSellingCategory)){

        $mostViews = $this->model_catalog_product->getProducts(['filter_category_id' => $mostViewCategory['category_id']]);
        if (!empty($mostViewCategory) and !empty($mostViews))
        {
            $data['most_view_category'] =$mostViewCategory['name'];
            foreach ($mostViews as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
                    $data['most_views'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }
}
        




        $dailyOfferCategory = $this->model_catalog_category->getCategory($this->config->get('config_daily_offer'));
        
        
                                if(!empty($dailyOffers)){
        $dailyOffers = $this->model_catalog_product->getProducts(['filter_category_id' => $dailyOfferCategory['category_id']]);
        if (!empty($dailyOffers) and !empty($dailyOfferCategory))
        {
            $data['daily_offer_category'] =$dailyOfferCategory['name'];
            foreach ($dailyOffers as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
                    $data['daily_offers'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'date_available'        => $product_info['date_available'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }



}



        $offerDiscountCategory = $this->model_catalog_category->getCategory($this->config->get('config_offer_discount'));
        
        
                                        if(!empty($offerDiscountCategory)){

        $offerDiscounts = $this->model_catalog_product->getProducts(['filter_category_id' => $offerDiscountCategory['category_id']]);
        if (!empty($offerDiscountCategory) and !empty($offerDiscounts))
        {
            $data['offer_discount_category'] =$offerDiscountCategory['name'];

            foreach ($offerDiscounts as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
                    $data['offer_discounts'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }
}
        

        if(!empty($data['offer_discounts'])){
                    $data['offer_discounts'] = array_chunk($data['offer_discounts'],4);
        }

        $topRatingCategory = $this->model_catalog_category->getCategory($this->config->get('config_top_rating'));
        
        
        if(!empty($topRatingCategory)){
                    $topRatings = $this->model_catalog_product->getProducts(['filter_category_id' => $topRatingCategory['category_id']]);
        if (!empty($topRatingCategory) and !empty($topRatings))
        {
            $data['top_rating_category'] =$topRatingCategory['name'];
            foreach ($topRatings as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                if ($product_info) {
                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                    }
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
                    $data['top_ratings'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

        }

        }


        if(!empty($data['top_ratings'])){
                    $data['top_ratings'] = array_chunk($data['top_ratings'],4);
        }

        $this->load->model('catalog/information');
        $information_info = $this->model_catalog_information->getInformation($this->config->get('config_newsletter'));

        $data['title'] = $information_info['title'];
        $data['desc'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');


        $this->load->model('design/banner');
        $data['ads_middle'] = array();
        $results = $this->model_design_banner->getBannerByKey('ads_middle',1);

        foreach ($results as $result) {
            $data['ads_middle'][] = array(
                'title' => $result['title'],
                'link'  => $result['link'],
                'image' => $this->request->server['HTTPS']? ($this->config->get('config_ssl') . 'image/' . $result['image'])  : ($this->config->get('config_url') . 'image/' . $result['image'])
            );
        }
        $data['ads_bottom'] = array();
        $results = $this->model_design_banner->getBannerByKey('ads_bottom',2);
        foreach ($results as $result) {
            $data['ads_bottom'][] = array(
                'title' => $result['title'],
                'link'  => $result['link'],
                'image' => $this->request->server['HTTPS']? ($this->config->get('config_ssl') . 'image/' . $result['image'])  : ($this->config->get('config_url') . 'image/' . $result['image'])
            );
        }

        $data['ads_aside'] = array();
        $results = $this->model_design_banner->getBannerByKey('ads_aside',1);

        foreach ($results as $result) {
            $data['ads_aside'][] = array(
                'title' => $result['title'],
                'link'  => $result['link'],
                'image' => $this->request->server['HTTPS']? ($this->config->get('config_ssl') . 'image/' . $result['image'])  : ($this->config->get('config_url') . 'image/' . $result['image'])
            );
        }

        return $this->load->view('extension/module/featured', $data);
	}
}