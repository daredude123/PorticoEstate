<?php
	/**
	* phpGroupWare - registration
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2011,2012 Free Software Foundation, Inc. http://www.fsf.org/
	* This file is part of phpGroupWare.
	*
	* phpGroupWare is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* phpGroupWare is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpGroupWare; if not, write to the Free Software
	* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/
	* @package registration
 	* @version $Id: class.uicheck_list.inc.php 8628 2012-01-21 10:42:05Z vator $
	*/

	phpgw::import_class('phpgwapi.yui');
	phpgw::import_class('registration.uicommon');
/*
	include_class('registration', 'check_list', 'inc/model/');
	include_class('registration', 'date_generator', 'inc/component/');
	include_class('registration', 'status_checker', 'inc/helper/');
	include_class('registration', 'date_helper', 'inc/helper/');
*/	
	class registration_uipending extends registration_uicommon
	{
		var $cat_id;
		var $start;
		var $query;
		var $sort;
		var $order;
		var $filter;
		var $currentapp;
		var $type_id;
		var $location_code;
	
		private $so_control_area;
		private $so_control;
		private $so_check_list;
		private $so_control_item;
		private $so_check_item;
		private $so_procedure;

		var $public_functions = array
		(
			'index'								=> true,
			'query'								=> true,
			'edit'						 		=> true
		);

		function __construct()
		{
			parent::__construct();
		
			$this->bo					= CreateObject('registration.bopending',true);
			$this->bocommon				= CreateObject('property.bocommon');
			$this->start				= $this->bo->start;
			$this->query				= $this->bo->query;
			$this->sort					= $this->bo->sort;
			$this->order				= $this->bo->order;
			$this->filter				= $this->bo->filter;
			$this->status_id			= $this->bo->status_id;
			$this->allrows				= $this->bo->allrows;
		
			self::set_active_menu('registration::pending');
		}

		function index()
		{
			if($values = phpgw::get_var('values'))
			{
				$values['pending_users'] = isset($values['pending_users']) && $values['pending_users'] ? array_unique($values['pending_users']) : array();
				$values['pending_users_orig'] = isset($values['pending_users_orig']) && $values['pending_users_orig'] ? array_unique($values['pending_users_orig']) : array();
				
				$receipt = $this->bo->approve_users($values);
				$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction' => 'registration.uipending.index'));
			}
			else
			{
				if(phpgw::get_var('phpgw_return_as') == 'json')
				{
					return $this->query();
				}

				$status_list = array
				(
					array
					(
						'id'	=> 0,
						'name'	=> lang('Select status')
					),
					array
					(
						'id'	=> 1,
						'name'	=> lang('approved')
					),
					array
					(
						'id'	=> 2,
						'name'	=> lang('pending')
					),
				);
		
				$data = array(
					'filter_form' 				=> array(
						'status_list' 			=> array('options' => $status_list)
					),
					'datatable' => array(
						'source' => self::link(array('menuaction' => 'registration.uipending.query', 'phpgw_return_as' => 'json')),
						'field' => array(
							array(
								'key' => 'id',
								'hidden' => true
							),
							array(
								'key' => 'reg_id',
								'label' => lang('id'),
								'sortable'	=> true,
								'formatter' => 'formatLinkPending'
							),
							array(
								'key'	=>	'reg_lid',
								'label'	=>	lang('user'),
								'sortable'	=>	true
							),
							array(
								'key' => 'reg_dla',
								'label' => lang('time'),
								'sortable'	=> true
							),
							array(
								'key' => 'reg_approved',
								'label' => lang('approved'),
								'sortable'	=> true,
								'formatter' => 'FormatterCenter'
							),
							array(
								'key' => 'location_code',
								'label' => lang('location'),
								'sortable'	=> false
							),

							array(
									'key' => 'checked',
									'label' => lang('approve'),
									'sortable' => false,
									'formatter' => 'formatterCheckPending',
									'className' => 'mychecks'
							),
							array(
								'key' => 'actions',
								'hidden' => true
							),
							array(
								'key' => 'labels',
								'hidden' => true
							),
							array(
								'key' => 'ajax',
								'hidden' => true
							),array(
								'key' => 'parameters',
								'hidden' => true
							)					
						)
					)
				);
			
				phpgwapi_yui::load_widget('paginator');

				self::add_javascript('registration', 'yahoo', 'pending.index.js');
//				self::add_javascript('registration', 'registration', 'jquery.js');
//				self::add_javascript('registration', 'registration', 'ajax.js');

				self::render_template_xsl(array('pending_users', 'common'), $data);
			}	
		}
	

		public function edit()
		{
			$id = phpgw::get_var('id', 'string');
			$bo = createobject('registration.boreg');

			if(isset($_POST['save']) && $id)
			{
				$values = phpgw::get_var('values');

				$insert_record = $GLOBALS['phpgw']->session->appsession('insert_record','property');
				$insert_record_entity = $GLOBALS['phpgw']->session->appsession('insert_record_entity','property');

				if(isset($insert_record_entity) && is_array($insert_record_entity))
				{
					for ($j=0;$j<count($insert_record_entity);$j++)
					{
						$insert_record['extra'][$insert_record_entity[$j]]	= $insert_record_entity[$j];
					}
				}

				$values = $this->bocommon->collect_locationdata($values,$insert_record);

				$values['id'] = $id;

				if($this->bo->update_pending_user($values))
				{
					$message = lang('messages_saved_form');
					phpgwapi_cache::message_set($message, 'message');
				}
				else
				{
					$error = lang('messages_form_error');
					phpgwapi_cache::message_set($message, 'error');
				}

			}

			if (isset($_POST['cancel'])) // The user has pressed the cancel button
			{
				$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction' => 'registration.uipending.index'));
			}

			if($id)
			{
				$user = $bo->get_pending_user($id);
			}
				
			$fields = $bo->fields;
				
			$user_data = array();
			$user_data[] = array
			(
				'text'	=> 'username',
				'value' => $user['reg_lid']
			);
				
			foreach ($fields as $key => $field_info)
			{
				if($user['reg_info'][$field_info['field_name']])
				{
					$user_data[] = array
					(
						'text'	=> $field_info['field_text'],
						'value' => $user['reg_info'][$field_info['field_name']]
					);
				}
			}

			$bolocation	= CreateObject('property.bolocation');
			$user['location_data'] = isset($user['reg_info']['location_code']) && $user['reg_info']['location_code'] ? $bolocation->read_single($user['reg_info']['location_code'],array('view' => true)) : '';
				

/*
_debug_array($user);
_debug_array($user_data);
_debug_array($fields);
die();
*/

			$location_data=$bolocation->initiate_ui_location(array(
				'values'	=> $user['location_data'],
				'type_id'	=> -1,
				'no_link'	=> false, // disable lookup links for location type less than type_id
				'tenant'	=> false,
				'lookup_type'	=> 'form',
				'lookup_entity'	=> false,
				'entity_data'	=> false
				));


			$data = array
			(
				'value_id'				=> $id,
				'img_go_home'			=> 'rental/templates/base/images/32x32/actions/go-home.png',
				'editable' 				=> true,
				'user_data'				=> $user_data,
				'location_data'			=> $location_data
			);

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang('registration') . '::' . lang('edit user');

			self::render_template_xsl('user_edit', $data);
		}
	
		public function query()
		{
			$status_id = phpgw::get_var('status_id');

			$this->bo->start = phpgw::get_var('startIndex');
		
			$user_list = $this->bo->read(array('user_id' => $user_id, 'role_id' =>$role_id, 'type_id'=>$type_id,'lookup_tenant'=>$lookup_tenant,
												   'lookup'=>$lookup,'allrows'=>$this->allrows,'dry_run' =>$dry_run));
			
			foreach($user_list as &$user)
			{
				$reg_info = unserialize(base64_decode($user['reg_info']));
				$user['location_code'] = $reg_info['location_code'];
				$results['results'][]= $user;
			}
			$results['total_records'] = $this->bo->total_records;
			$results['start'] = $this->start;
			$results['sort'] = 'location_code';
			$results['dir'] = $this->bo->sort ? $this->bo->sort : 'ASC';
					
			array_walk($results['results'], array($this, 'add_links'), array($type));
						
			return $this->yui_results($results);
		}
	}
