<?php
class ControllerCommonSearch extends Controller {
	public function index() {
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');

		if (isset($this->request->get['search'])) {
			$data['search'] = $this->request->get['search'];
		} else {
			$data['search'] = ' ';
		}
        if (isset($this->request->get['category_search'])) {
            $data['category_search'] = $this->request->get['category_search'];
        } else {
            $data['category_search'] = ' ';
        }


		return $this->load->view('common/search', $data);
	}
}