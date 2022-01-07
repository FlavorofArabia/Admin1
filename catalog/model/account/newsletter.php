<?php
class ModelAccountNewsletter extends Model {
	public function checkNewsletter(string $email) {
        $newsletters = $this->db->query("SELECT count('email') as count FROM newsletter WHERE email = '" . $email . "'" );

        if ($newsletters->row['count'] == 0){
           return true;
        }

        return false;

	}

    public function saveNewsletter(string $email) {
        $this->db->query("INSERT INTO `newsletter` SET `email` = '" . $email . "'" );

        return ;
    }


}