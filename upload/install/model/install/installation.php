<?php
class ModelInstallInstallation extends Model {
	public function database($data) {
		$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

		$file = DIR_APPLICATION . 'install.sql';

		if (!file_exists($file)) {
			exit('Could not load sql file: ' . $file);
		}

		$lines = file($file);

		if ($lines) {
			$sql = '';

			foreach($lines as $line) {
				if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
					$sql .= $line;

					if (preg_match('/;\s*$/', $line)) {
						$sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $sql);
						$sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $data['db_prefix'], $sql);
						$sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql);
                        #mod
						$sql = str_replace("REFERENCES `oc_", "REFERENCES `" . $data['db_prefix'], $sql);
						$sql = str_replace("UPDATE `oc_", "UPDATE `" . $data['db_prefix'], $sql);
						$sql = str_replace("ALTER TABLE `oc_", "ALTER TABLE `" . $data['db_prefix'], $sql);

						$db->query($sql);

						$sql = '';
					}
				}
			}
			
		/*** Original data
		$lines = file(DIR_APPLICATION . 'opencart.sql', FILE_IGNORE_NEW_LINES);

		if ($lines) {
			$sql = '';

			$start = false;

			foreach($lines as $line) {
				if (substr($line, 0, 12) == 'INSERT INTO ') {
					$sql = '';

					$start = true;
				}

				if ($start) {
					$sql .= $line;
				}

				if (substr($line, -2) == ');') {
					$db->query(str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql));
					$db->query(str_replace("UPDATE `oc_", "UPDATE `" . $data['db_prefix'], $sql));
					$db->query(str_replace("ALTER TABLE `oc_", "ALTER TABLE `" . $data['db_prefix'], $sql));

					$start = false;
				}
			}
		} ***/

            $db->query("SET CHARACTER SET utf8");

            $db->query("SET @@session.sql_mode = 'MYSQL40'");

            $db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");

            $db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $db->escape($data['username']) . "', salt = '', password = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', firstname = 'Admin', lastname = 'Root', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");
            $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");

            $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $db->escape($data['email']) . "'");

            $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");

            $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $db->escape(token(1024)) . "'");

            $db->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");

            $db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET username = 'Default', `key` = '" . $db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");

            $api_id = $db->getLastId();

            $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");

            $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "'");

            // set the current years prefix
            #$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");

            #mod add seller to sellers group and test customer to default customers group
			$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '2', user_group_id = '2', username = 'seller1', salt = '', password = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', firstname = 'Seller', lastname = 'First', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");
            $db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '3', user_group_id = '2', username = 'seller2', salt = '', password = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', firstname = 'Seller', lastname = 'Second', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");
			$db->query("INSERT INTO `" . $data['db_prefix'] . "customer` SET language_id = '1', customer_group_id = '1', store_id = '0', salt = '', password = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', firstname = 'Customer', lastname = 'First', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");
		}
	}
}
