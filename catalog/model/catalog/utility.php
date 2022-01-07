<?php
class ModelCatalogUtility extends Model {

    public function saveCompanyOrder($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "company_order SET customer_id = '" . (int)$this->customer->getId() . "',
            company_name = '" . $this->db->escape($data['company_name']) . "',
            phone_number = '" . $this->db->escape($data['phone_number']) . "',
            email = '" . $this->db->escape($data['email'])  . "',
            address = '" . $this->db->escape($data['address'])  . "',
            content = '" . $this->db->escape($data['content'])  ."'");

    }


    public function checkCustomerHasThisOrder($order_id){
        $order = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
        return count($order->row);
    }

    public function checkCustomerHasReviewBefore($order_id){
        $order = $this->db->query("SELECT * FROM " . DB_PREFIX . "review_order WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
        return count($order->row);
    }


    public function saveReview($data){
        $this->db->query("INSERT INTO " . DB_PREFIX . "review_order SET customer_id = '" . (int)$this->customer->getId() . "',
            order_id = '" . $this->db->escape($data['order_id']) . "',
            content = '" . $this->db->escape($data['content'])  ."'");
    }

    public function getAvailableDays(){
//        $query = $this->db->query("select  count(od.id) as order_count , d.name , d.id , d.quantity as quantity , d.code from " . DB_PREFIX . "days as d left join " . DB_PREFIX . "order_days as od on  d.id = od.day_id
//where od.created_at >= curdate() - INTERVAL DAYOFWEEK(curdate()) + 6 DAY group by day_id , d.quantity HAVING order_count < d.quantity union
//select 0, d.name , d.id , d.quantity as quantity , d.code from " . DB_PREFIX . "days d left join " . DB_PREFIX . "order_days od on  d.id = od.day_id where od.day_id is null");

        $query = $this->db->query("select  count(od.id) as order_count , d.name , d.id , d.quantity as quantity , d.code from " . DB_PREFIX . "days as d left join " . DB_PREFIX . "order_days as od on  d.id = od.day_id
where od.created_at >= curdate() - INTERVAL DAYOFWEEK(curdate()) + 6 DAY group by day_id , d.quantity HAVING order_count < d.quantity union 
SELECT 0 as order_count, d.name, d.id, d.quantity as quantity, d.code from mc_days as d where id not in ( select day_id from mc_order_days where created_at >= curdate() - INTERVAL DAYOFWEEK(curdate()) + 6 DAY );
");
        return $query->rows;
    }
}