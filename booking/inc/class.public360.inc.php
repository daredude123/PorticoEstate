<?php

	/**
	 * phpGroupWare
	 *
	 * @author Sigurd Nes <sigurdne@online.no>
	 * @copyright Copyright (C) 2020 Free Software Foundation, Inc. http://www.fsf.org/
	 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	 * @internal Development of this application was funded by http://www.bergen.kommune.no/
	 * @package phpgroupware
	 * @subpackage communication
	 * @category core
	 */
	/*
	  This program is free software: you can redistribute it and/or modify
	  it under the terms of the GNU General Public License as published by
	  the Free Software Foundation, either version 2 of the License, or
	  (at your option) any later version.

	  This program is distributed in the hope that it will be useful,
	  but WITHOUT ANY WARRANTY; without even the implied warranty of
	  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	  GNU General Public License for more details.

	  You should have received a copy of the GNU General Public License
	  along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	phpgw::import_class('phpgwapi.datetime');

	class booking_public360
	{

		private $debug, $webservicehost, $authkey, $proxy, $archive_user_id;

		public function __construct()
		{
			$location_id = $GLOBALS['phpgw']->locations->get_id('booking', 'run');
			$custom_config = CreateObject('admin.soconfig', $location_id);
			$custom_config_data = $custom_config->config_data['public360'];
			$config	= CreateObject('phpgwapi.config', 'booking')->read();

			if (!empty($custom_config_data['debug']))
			{
				$this->debug = true;
			}

			$this->webservicehost	 = !empty($custom_config_data['webservicehost']) ? $custom_config_data['webservicehost'] : '';
			$this->authkey			 = !empty($custom_config_data['authkey']) ? $custom_config_data['authkey'] : '';
			$this->proxy			 = !empty($config['proxy']) ? $config['proxy'] : '';
			$this->archive_user_id	 = $GLOBALS['phpgw_info']['user']['preferences']['common']['archive_user_id'];
		}

		public function get_cases()
		{
			$data = array
			(
				'parameter' => array(
					'Recno'=> '201362',
				//	'CaseNumber' => '2020000693'
					)
			);

			$method = 'CaseService/GetCases';
			$ret = $this->transfer_data($method, $data);

			return $ret;
		}


		public function export_data( $_title, $application, $files )
		{
			if(!$this->archive_user_id)
			{
				phpgwapi_cache::message_set( 'Ansvarlig arkiv-bruker er ikke angitt under innstillinger', 'error');
				return;
			}

			$title = str_replace(array('(', ')'), array('[', ']'), $_title);
			if($application['customer_ssn'])
			{
				$person_data = $this->get_person( $application['customer_ssn'] );

				if(!$person_data || empty($person_data['PostAddress']['StreetAddress']))
				{
					$person_data = $this->add_update_person( $application, $person_data );
				}
			}

			if(!empty($application['customer_organization_number']))
			{
				$enterprise_data = $this->get_enterprise( $application['customer_organization_number']);
				if(!$enterprise_data || empty($enterprise_data['OfficeAddress']['StreetAddress']))
				{
					$enterprise_data = $this->add_update_enterprise( $application, $enterprise_data);
				}
			}

			$case_result = $this->create_case($title, $application);

			$document_result = array();

			if($case_result['Successful'])
			{
				$document_result = $this->create_document($case_result, $title, $files, $application);
			}

			return array(
				'external_archive_key'	 => $case_result['CaseNumber'],
				'case_result'			 => $case_result,
				'document_result'		 => $document_result
			);
		}


		public function get_person( $ssn )
		{

			$data = array(
				'ExternalID' => $ssn
				);

			$input = array('parameter' => $data);
			$method = 'ContactService/GetPrivatePersons';
			$person_data = $this->transfer_data($method, $input);
			return current($person_data['PrivatePersons']);
		}

		public function add_update_person ( $application, $person_data )
		{
			phpgw::import_class('bookingfrontend.bouser');

			$data = array(
				'ssn'	=> $application['customer_ssn'],
//				'phone' => (string)$_SERVER['HTTP_MOBILTELEFONNUMMER'],
//				'email'	=> (string)$_SERVER['HTTP_EPOSTADRESSE']
				);

			$configfrontend	= CreateObject('phpgwapi.config','bookingfrontend')->read();
			$get_name_from_external = isset($configfrontend['get_name_from_external']) && $configfrontend['get_name_from_external'] ? $configfrontend['get_name_from_external'] : '';

			$file = PHPGW_SERVER_ROOT . "/bookingfrontend/inc/custom/default/{$get_name_from_external}";

			if (is_file($file))
			{
				require_once $file;
				$external_user = new bookingfrontend_external_user_name();
				try
				{
					$external_user->get_name_from_external_service( $data );
				}
				catch (Exception $exc)
				{
				}
			}

			if(!empty($person_data['PostAddress']))
			{
				$PostAddress = $person_data['PostAddress'];
			}
			else
			{
				$PostAddress		 = array(
					'StreetAddress'	 => $data['street'],
					'ZipCode'		 => $data['zip_code'],
					'ZipPlace'		 => $data['city'],
					'Country'		 => 'NOR',
				);
			}
			if(!empty($person_data['PrivateAddress']))
			{
				$PrivateAddress = $person_data['PrivateAddress'];
			}
			else
			{
				$PrivateAddress = $PostAddress;
			}

			$name_array = explode(' ', trim(str_replace('  ', ' ', $application['contact_name'])));
			$last_name = end($name_array);
			array_pop($name_array);
			$first_name = implode(' ', $name_array);

			$data = array(
				'ExternalID'		 => $application['customer_ssn'],
				'PersonalIdNumber'	 => $application['customer_ssn'],
				'FirstName'			 => $person_data['FirstName'] ? $person_data['FirstName'] : $data['first_name'],
				'MiddleName'		 => $person_data['MiddleName'] ? $person_data['MiddleName'] : '',
				'LastName'			 => $person_data['LastName'] ? $person_data['LastName'] : $data['last_name'],
				'Email'				 => $person_data['Email'] ? $person_data['Email'] : $application['contact_email'],
				'PhoneNumber'		 => $person_data['PhoneNumber'] ? $person_data['PhoneNumber'] : $application['contact_phone'],
				'PostAddress'		 => $PostAddress,
				'PrivateAddress'	 => $PrivateAddress
			);

			if(!empty($person_data['PrivateAddress']))
			{
				$data['PrivateAddress'] = $person_data['PrivateAddress'];
			}
			if(!empty($person_data['WorkAddress']))
			{
				$data['WorkAddress'] = $person_data['WorkAddress'];
			}

			$input = array('parameter' => $data);
			$method = 'ContactService/SynchronizePrivatePerson';
			$result = $this->transfer_data($method, $input);
			return $result;

		}

		public function get_enterprise( $organization_number )
		{

			$data = array(
				'EnterpriseNumber' => $organization_number,
				'Active' => true
				);

			$input = array('parameter' => $data);
			$method = 'ContactService/GetEnterprises';
			$enterprise_data = $this->transfer_data($method, $input);
			return current($enterprise_data['Enterprises']);
		}

		public function add_update_enterprise ( $application, $enterprise_data )
		{
			$organization = $this->get_organization($application['customer_organization_number']);

			if(empty($organization['forretningsadresse']) && $organization['beliggenhetsadresse'])
			{
				$organization['forretningsadresse']	= $organization['beliggenhetsadresse'];
			}

			if(empty($organization['forretningsadresse']) && $organization['postadresse'])
			{
				$organization['forretningsadresse']	= $organization['postadresse'];
			}

			$PostAddress = array();
			if(!empty($enterprise_data['PostAddress']['StreetAddress']))
			{
				$PostAddress = $enterprise_data['PostAddress'];
			}
			else
			{
				$PostAddress		 = array(
					'StreetAddress'	 => implode(', ', $organization['postadresse']['adresse']),
					'ZipCode'		 => $organization['postadresse']['postnummer'],
					'ZipPlace'		 => $organization['postadresse']['poststed'],
					'Country'		 => 'NOR',
				);
			}

			if (!empty($enterprise_data['OfficeAddress']['StreetAddress']))
			{
				$OfficeAddress = $enterprise_data['OfficeAddress'];
			}
			else
			{
				$OfficeAddress = array(
					'StreetAddress'	 => implode(', ', $organization['forretningsadresse']['adresse']),
					'ZipCode'		 => $organization['forretningsadresse']['postnummer'],
					'ZipPlace'		 => $organization['forretningsadresse']['poststed'],
					'Country'		 => 'NOR',
				);
			}

			$data = array(
				'EnterpriseNumber'	 => $application['customer_organization_number'],
				'Name'				 => $enterprise_data['Name'] ? $enterprise_data['Name'] : $organization['navn'],
				'Email'				 => $enterprise_data['Email'] ? $enterprise_data['Email'] : '',
				'PhoneNumber'		 => $enterprise_data['PhoneNumber'] ? $enterprise_data['PhoneNumber'] : '',
				'OfficeAddress'		 => $OfficeAddress,
				'web'				 => $enterprise_data['web'] ? $enterprise_data['web'] : $organization['hjemmeside']
			);

			if($PostAddress)
			{
				$data['PostAddress'] = $PostAddress;
			}

			$input = array('parameter' => $data);
			$method = 'ContactService/SynchronizeEnterprise';
			$result = $this->transfer_data($method, $input);
			return $result;
		}

		public function create_case( $title, $application )
		{
			$data = array(
				'Title' => $title,
				'ExternalId' => array('Id' => $application['id'], 'Type' => 'portico'),
				'Status' => 'B',//'Under behandling',
				'AccessCodeCode' => 'U',
//				'ResponsibleEnterprise' => Array
//					(
//						'Recno' => '201665',
//					),
//				'ResponsiblePerson' => array
//					(
//						'Recno' => $this->archive_user_id,
//					),
				'ResponsiblePersonRecno' => $this->archive_user_id,
				'ArchiveCodes' => array
				(
					array
					(
						'Sort' => 1,
						'ArchiveCode' => '614',
						'ArchiveType' => 'FELLESKLASSE PRINSIPP',
					)
				),
				'Contacts' => array(),
				'SubArchive' => '60001',
				'SubArchiveCode' => 'SAK',
			);

			if($application['customer_ssn'])
			{
				$data['Contacts'][] = 	array(
						'Role' => 'Sakspart', //Sakspart
						'ReferenceNumber' => $application['customer_ssn'],
//						'ExternalId' => $application['customer_ssn'],
					);
			}
			if($application['customer_organization_number'])
			{
				$data['Contacts'][] = 	array(
						'Role' => 'Sakspart', //Sakspart
						'ReferenceNumber' => $application['customer_organization_number'],
					);
			}

			$method = 'CaseService/CreateCase';

			$input = array('parameter' => $data);
			$case_data = $this->transfer_data($method, $input);
			return $case_data;
		}

		public function create_document( $case_data, $title, $files, $application )
		{
			$data = array(
				'CaseNumber' => $case_data['CaseNumber'],
				'Title' => $title,
				'Category' => 110, //Dokument inn
				'Status'	=> 'J',
				'Files'		=> array(),
				'Contacts' => array(),
				'ResponsiblePersonRecno' => $this->archive_user_id,
				'DocumentDate'			=> date('Y-m-d\TH:i:s', phpgwapi_datetime::user_localtime()),
			);

			$ssn_role = 5;//'Avsender'
			if($application['customer_organization_number'])
			{
				$ssn_role = 1;//'Contact'

				$data['Contacts'][] = 	array(
						'Role' => 5,//'Avsender',
						'ReferenceNumber' => $application['customer_organization_number'],
					);
			}

			if($application['customer_ssn'])
			{
				$data['Contacts'][] = array(
						'Role' => $ssn_role,
						'ExternalId' => $application['customer_ssn'],
				//		'ReferenceNumber' => $application['customer_ssn'],
					);
			}


			foreach ($files as $file)
			{
				$path_parts = pathinfo($file['file_name']);
				$data['Files'][] = array(
					'Title' => $file['file_name'],
					'Status'	=> 'F', //Ferdig
					'Format' => strtolower($path_parts['extension']),
//					'Data' => $file['file_data'],
					'Base64Data' => base64_encode($file['file_data'])
				);
			}

			$method = 'DocumentService/CreateDocument';
			$input = array('parameter' => $data);
			$cocument_data = $this->transfer_data($method, $input);
			return $cocument_data;
		}

		private function transfer_data( $method, $data )
		{
			$data_json	 = json_encode($data);

			$url = "{$this->webservicehost}/{$method}?authkey={$this->authkey}";

			$this->log('webservicehost', print_r($url, true));
			$this->log('POST data', print_r($data, true));

			$ch		 = curl_init();
			if ($this->proxy)
			{
				curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'accept: application/json',
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_json)
				));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);


			if($this->debug)
			{
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				$verbose = fopen('php://temp', 'w+');
				curl_setopt($ch, CURLOPT_STDERR, $verbose);
			}
			$result	 = curl_exec($ch);

			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$ret = json_decode($result, true);

			if ($this->debug)
			{
				rewind($verbose);
				$verboseLog = stream_get_contents($verbose);
				echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
			}

			$this->log('webservice httpCode', print_r($httpCode, true));
			$this->log('webservice returdata as json', $result);
			$this->log('webservice returdata as array', print_r($ret, true));

			return $ret;
		}

		private function get_organization( $organization_number )
		{
			$url = "https://data.brreg.no/enhetsregisteret/api/enheter/{$organization_number}";

			$ch		 = curl_init();
			if ($this->proxy)
			{
				curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'accept: application/json',
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_json)
				));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result	 = curl_exec($ch);

			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$ret = json_decode($result, true);

			if($ret)
			{
				return $ret;
			}
			else
			{
				return $this->get_sub_organization($organization_number);
			}
		}


		private function get_sub_organization( $organization_number )
		{
			$url = "https://data.brreg.no/enhetsregisteret/api/underenheter/{$organization_number}";

			$ch		 = curl_init();
			if ($this->proxy)
			{
				curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'accept: application/json',
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_json)
				));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result	 = curl_exec($ch);

			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$ret = json_decode($result, true);

			return $ret;
		}


		private function log( $what, $value = '' )
		{
			if (!empty($GLOBALS['phpgw_info']['server']['log_levels']['module']['booking']))
			{
				$GLOBALS['phpgw']->log->debug(array(
					'text' => "what: %1, <br/>value: %2",
					'p1' => $what,
					'p2' => $value ? $value : ' ',
					'line' => __LINE__,
					'file' => __FILE__
				));
			}
		}
	}