<?php
	/*
	 * This file will only work for the implementation of LRS
	 */

	/**
	 * Intended for custom validation of ajax-request from form.
	 *
	 * @author Sigurd Nes <sigurdne@online.no>
	 */
	if (!class_exists("ticket_LRS_reverse_assignee"))
	{

		class ticket_LRS_reverse_assignee
		{

			protected $config, $db;

			function __construct()
			{
				$this->config = CreateObject('admin.soconfig', $GLOBALS['phpgw']->locations->get_id('property', '.admin'));
			}

			function ping( $host )
			{
				exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
				return $rval === 0;
			}

			public function get_db()
			{
				if ($this->db && is_object($this->db))
				{
					return $this->db;
				}

				if (!$this->config->config_data['fellesdata']['host'] || !$this->ping($this->config->config_data['fellesdata']['host']))
				{
					$message = "Database server {$this->config->config_data['fellesdata']['host']} is not accessible";
					phpgwapi_cache::message_set($message, 'error');
					return false;
				}

				$db				 = createObject('phpgwapi.db_adodb', null, null, true);
				$db->debug		 = false;
				$db->Host		 = $this->config->config_data['fellesdata']['host'];
				$db->Port		 = $this->config->config_data['fellesdata']['port'];
				$db->Type		 = 'oracle';
				$db->Database	 = $this->config->config_data['fellesdata']['db_name'];
				$db->User		 = $this->config->config_data['fellesdata']['user'];
				$db->Password	 = $this->config->config_data['fellesdata']['password'];

				try
				{
					$db->connect();
					$this->connected = true;
				}
				catch (Exception $e)
				{
					$status = lang('unable_to_connect_to_database');
				}

				$this->db = $db;
				return $db;
			}

			function get_user_info()
			{
				if (!$db = $this->get_db())
				{
					return;
				}

				$account_lid = phpgw::get_var('account_lid');

				if((int)$account_lid)
				{
					$account_lid = $GLOBALS['phpgw']->accounts->id2lid((int)$account_lid);
				}

				$account_lid = $db->db_addslashes($account_lid);

				$filtermethod = "BRUKERNAVN = '{$account_lid}'";

				if (preg_match("/^dummy\:\:/i", $account_lid))
				{
					$identificator_arr	 = explode("::", $account_lid);
					$filtermethod = "RESSURSNR = '{$identificator_arr[1]}'";
				}

				$sql = "SELECT ORG_ENHET_ID, ORG_NAVN FROM V_SOA_ANSATT WHERE {$filtermethod}";

				$db->query($sql, __LINE__, __FILE__);
				$values = array();

				if ($db->next_record())
				{
					$values = array(
						'org_unit_id'	 => $db->f('ORG_ENHET_ID'),
						'org_unit'		 => $db->f('ORG_NAVN', true)
					);
				}
				return $values;
			}

			function get_on_behalf_of()
			{
				$query = phpgw::get_var('query');
				$search_options = phpgw::get_var('search_options');

				if (!$db = $this->get_db())
				{
					return;
				}

				if(strlen($query) < 4)
				{
					return array('ResultSet' => array('Result' => $values));
				}
				$query_arr	 = explode(" ", str_replace("  ", " ", $query));
				$query_arr2	 = explode(",", str_replace(" ", "", $query));

				$filtermethod = '';

				if($search_options == 'ressurs_nr')
				{
					$filtermethod =	"RESSURSNR = '{$query}'";
				}
				else if($search_options == 'resultat_enhet')
				{
					$filtermethod =	"BRUKERNAVN = '{$query}'"
					. " OR FODSELSNR  = '{$query}'"
					. " OR RESSURSNR  = '{$query}'";

					if(!empty($query_arr[1]) && empty($query_arr2[1]))
					{
						$filtermethod .= " OR (lower(FORNAVN)  LIKE '" . strtolower($query_arr[0]) ."%'"
						 . " AND lower(ETTERNAVN)  LIKE '" . strtolower($query_arr[1]) ."%')";
					}
					else if(!empty($query_arr[0]) && !isset($query_arr2[1]))
					{
						$filtermethod .= " OR lower(ETTERNAVN)  LIKE '" . strtolower($query_arr[0]) ."%'";
					}
					else if(isset($query_arr2[1]))
					{
						$filtermethod .= " OR (lower(ETTERNAVN)  LIKE '" . strtolower($query_arr2[0]) ."%'"
						 . " AND lower(FORNAVN)  LIKE '" . strtolower($query_arr2[1]) ."%')";
					}

					$ticket_id = (int)phpgw::get_var('ticket_id');

					$GLOBALS['phpgw']->db->query("SELECT user_id FROM phpgw_helpdesk_tickets WHERE id = {$ticket_id}", __LINE__, __FILE__);
					$GLOBALS['phpgw']->db->next_record();
					$user_id = $GLOBALS['phpgw']->db->f('user_id');
					$user_lid = $GLOBALS['phpgw']->accounts->get($user_id)->lid;

					$sql = "SELECT ORG_ENHET_ID, ORG_NIVAA FROM V_SOA_ANSATT WHERE BRUKERNAVN = '{$user_lid}'";

					$db->query($sql, __LINE__, __FILE__);

					if ($db->next_record())
					{
						$org_unit	 = $db->f('ORG_ENHET_ID');
						$level		 = $db->f('ORG_NIVAA');
					}

					if (!$org_unit)
					{
						return;
					}

					$path = CreateObject('property.sogeneric')->get_path(array(
						'type' => 'org_unit',
						'id' => $org_unit,
						'path_by_id' => true
						));

					$levels = count($path);

					if ($levels > 1)
					{
						$parent_id = (int)$path[($levels - 2)];
					}
					else
					{
						$parent_id = (int)$path[0];
					}

					$sql = "SELECT id FROM fm_org_unit WHERE parent_id  = {$parent_id}";

					$GLOBALS['phpgw']->db->query($sql, __LINE__, __FILE__);

					$org_units = array(-1);

					while ($GLOBALS['phpgw']->db->next_record())
					{
						$org_units[] = (int)$GLOBALS['phpgw']->db->f('id');
					}

					$filtermethod .= " AND V_SOA_ANSATT.ORG_ENHET_ID IN (" . implode(',', $org_units) . ')';

				}
				else
				{
					$filtermethod =	"BRUKERNAVN = '{$query}'"
					. " OR FODSELSNR  = '{$query}'"
					. " OR RESSURSNR  = '{$query}'";

					if(!empty($query_arr[1]) && empty($query_arr2[1]))
					{
						$filtermethod .= " OR (lower(FORNAVN)  LIKE '" . strtolower($query_arr[0]) ."%'"
						 . " AND lower(ETTERNAVN)  LIKE '" . strtolower($query_arr[1]) ."%')";
					}
					else if(!empty($query_arr[0]) && !isset($query_arr2[1]))
					{
						$filtermethod .= " OR lower(ETTERNAVN)  LIKE '" . strtolower($query_arr[0]) ."%'";
					}
					else if(isset($query_arr2[1]))
					{
						$filtermethod .= " OR (lower(ETTERNAVN)  LIKE '" . strtolower($query_arr2[0]) ."%'"
						 . " AND lower(FORNAVN)  LIKE '" . strtolower($query_arr2[1]) ."%')";
					}
				}

				$sql = "SELECT ORG_ENHET_ID, ORG_NIVAA, BRUKERNAVN, FORNAVN, ETTERNAVN,STILLINGSTEKST, RESSURSNR FROM V_SOA_ANSATT"
					. " WHERE {$filtermethod}";


				$db->limit_query($sql, 0, __LINE__, __FILE__, 10);
				$values = array();

				while ($db->next_record())
				{
					$user_lid = $db->f('BRUKERNAVN');
					$values[] = array(
						'id'		 => $user_lid ? $user_lid : 'dummy::' . $db->f('RESSURSNR'),
						'name'		 => $db->f('BRUKERNAVN') . ' [' . $db->f('RESSURSNR') .': ' . $db->f('ETTERNAVN', true) . ', ' . $db->f('FORNAVN', true) . ', ' . $db->f('STILLINGSTEKST', true) . '] ' ,
						'org_unit'	 => $db->f('ORG_ENHET_ID'),
						'level'		 => $db->f('ORG_NIVAA'),
					);
				}

				foreach ($values as &$value)
				{
					$path = CreateObject('property.sogeneric')->get_path(array(
						'type'			 => 'org_unit',
						'id'			 => $value['org_unit'],
						'path_by_id'	 => true
					));

					$levels = count($path);

					if ($levels > 1)
					{
						$parent_id = (int)$path[($levels - 2)];
					}
					else
					{
						$parent_id = (int)$path[0];
					}
					$sql = "SELECT name FROM fm_org_unit WHERE id  = {$parent_id}";

					$GLOBALS['phpgw']->db->query($sql, __LINE__, __FILE__);

					$GLOBALS['phpgw']->db->next_record();
					{
						$org_unit_name = $GLOBALS['phpgw']->db->f('name', true);
					}

					$value['name'] .= " {$org_unit_name}";
				}

				return array('ResultSet' => array('Result' => $values));
			}

			function get_reverse_assignee()
			{
				$on_behalf_of_lid = phpgw::get_var('on_behalf_of_lid', 'string');


				if (!$on_behalf_of_lid)
				{
					return;
				}

				if (!$db = $this->get_db())
				{
					return;
				}

				$filtermethod = "BRUKERNAVN = '{$on_behalf_of_lid}'";

				if (preg_match("/^dummy\:\:/i", $on_behalf_of_lid))
				{
					$identificator_arr	 = explode("::", $on_behalf_of_lid);
					$filtermethod = "RESSURSNR = '{$identificator_arr[1]}'";
				}

				$sql = "SELECT ORG_ENHET_ID, ORG_NIVAA FROM V_SOA_ANSATT WHERE {$filtermethod}";

				$db->query($sql, __LINE__, __FILE__);

				if ($db->next_record())
				{
					$org_unit	 = $db->f('ORG_ENHET_ID');
					$level		 = $db->f('ORG_NIVAA');
				}

				if (!$org_unit)
				{
					return;
				}

				$path = CreateObject('property.sogeneric')->get_path(array(
					'type' => 'org_unit',
					'id' => $org_unit,
					'path_by_id' => true
					));

				$levels = count($path);

				if ($levels > 1)
				{
					$parent_id = (int)$path[($levels - 2)];
				}
				else
				{
					$parent_id = (int)$path[0];
				}



				$sql = "SELECT id FROM fm_org_unit WHERE parent_id  = {$parent_id}";

				$GLOBALS['phpgw']->db->query($sql, __LINE__, __FILE__);

				$org_units = array();

				while ($GLOBALS['phpgw']->db->next_record())
				{
					$org_units[] = (int)$GLOBALS['phpgw']->db->f('id');
				}

				$sql = "SELECT BRUKERNAVN, STILLINGSTEKST, V_ORG_ENHET.ORG_NAVN FROM V_SOA_ANSATT"
					. " JOIN V_ORG_ENHET ON V_ORG_ENHET.ORG_ENHET_ID = V_SOA_ANSATT.ORG_ENHET_ID"
					. " WHERE V_SOA_ANSATT.ORG_ENHET_ID IN (" . implode(',', $org_units) . ')';

				$db->query($sql, __LINE__, __FILE__);

				$candidates = array();
				while ($db->next_record())
				{
					$candidates[$db->f('BRUKERNAVN')] = array(
						'office' =>  $db->f('ORG_NAVN',true),
						'stilling' =>  $db->f('STILLINGSTEKST',true)
						);
				}

				$sql = "SELECT DISTINCT alias, name  FROM phpgw_helpdesk_email_out_recipient_list WHERE alias IN ('" . implode("','", array_keys($candidates)) . "')";

				$GLOBALS['phpgw']->db->query($sql, __LINE__, __FILE__);

				$candidate_assignees = array();

				while ($GLOBALS['phpgw']->db->next_record())
				{
					$lid					 = $GLOBALS['phpgw']->db->f('alias', true);
					$candidate_assignees[]	 = array
						(
						'lid'		 => $lid,
						'name'		 => $GLOBALS['phpgw']->db->f('name', true),
						'stilling'	 => $candidates[$lid]['stilling'],
						'office'	 => $candidates[$lid]['office'],
					);
				}

				$values = array();
				foreach ($candidate_assignees as $candidate_assignee)
				{
					$candidate_assignee['id'] = $GLOBALS['phpgw']->accounts->name2id($candidate_assignee['lid']);

					if (!$candidate_assignee['id'])
					{
						continue;
					}
					$values[] = $candidate_assignee;
				}

				return array(
					'total_records'	 => count($values),
					'results'		 => $values
				);
			}
		}
	}

	$method = phpgw::get_var('method');

	if ($method == 'get_reverse_assignee')
	{
		$reverse_assignee	 = new ticket_LRS_reverse_assignee();
		$ajax_result		 = $reverse_assignee->get_reverse_assignee();
	}
	else if ($method == 'get_on_behalf_of')
	{
		$reverse_assignee	 = new ticket_LRS_reverse_assignee();
		$ajax_result		 = $reverse_assignee->get_on_behalf_of();
	}
	else if ($method == 'get_user_info')
	{
		$reverse_assignee	 = new ticket_LRS_reverse_assignee();
		$ajax_result		 = $reverse_assignee->get_user_info();
	}
