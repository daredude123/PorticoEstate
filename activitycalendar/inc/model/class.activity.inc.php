<?php
	phpgw::import_class('activitycalendar.soorganization');
	phpgw::import_class('activitycalendar.sogroup');
	phpgw::import_class('activitycalendar.soarena');
	phpgw::import_class('activitycalendar.socontactperson');
	include_class('activitycalendar', 'model', 'inc/model/');

	class activitycalendar_activity extends activitycalendar_model
	{
		public static $so;
		
		protected $id;
		protected $title;
		protected $organization_id;
		protected $group_id;
		protected $district;
		protected $category;
		protected $target;
		protected $description;
		protected $arena;
		protected $time;
		protected $create_date;
		protected $last_change_date;
		protected $contact_person_1;
		protected $contact_person_2;
		protected $special_adaptation;
		
		/**
		 * Constructor.  Takes an optional ID.  If a contract is created from outside
		 * the database the ID should be empty so the database can add one according to its logic.
		 * 
		 * @param int $id the id of this composite
		 */
		public function __construct(int $id = null)
		{
			$this->id = (int)$id;
		}
		
		public function set_id($id)
		{
			$this->id = $id;
		}
		
		public function get_id() { return $this->id; }
		
		public function set_title($title)
		{
			$this->title = $title;
		}
		
		public function get_title() { return $this->title; }
		
		public function set_organization_id($organization_id)
		{
			$this->organization_id = $organization_id;
		}
		
		public function get_organization_id() { return $this->organization_id; }

		public function set_group_id($group_id)
		{
			$this->group_id = $group_id;
		}
		
		public function get_group_id() { return $this->group_id; }
		
		public function set_district($district)
		{
			$this->district = $district;
		}
		
		public function get_district() { return $this->district; }
		
		public function set_target($target)
		{
			$this->target = $target;
		}
		
		public function get_target() { return $this->target; }
		
		public function set_category($category)
		{
			$this->category = $category;
		}
		
		public function get_category() { return $this->category; }
		
		public function set_description($description)
		{
			$this->description = $description;
		}
		
		public function get_description() { return $this->description; }
		
		public function set_state($state)
		{
			$this->state = $state;
		}
		
		public function get_state() { return $this->state; }
		
		public function set_arena($arena)
		{
			$this->arena = $arena;
		}
		
		public function get_arena() { return $this->arena; }
		
		public function set_time($time)
		{
			$this->time = $time;
		}
		
		public function get_time() { return $this->time; }
		
/*		public function set_date_end($date_end)
		{
			$this->date_end = $date_end;
		}
		
		public function get_date_end() { return $this->date_end; }*/
		
		public function set_create_date($create_date)
		{
			$this->create_date = $create_date;
		}
		
		public function get_create_date() { return $this->create_date; }
		
		public function get_last_change_date() { return $this->last_change_date; }
		
		public function set_last_change_date($last_change_date)
		{
			$this->last_change_date = $last_change_date;
		}
		
		public function set_contact_persons($persons)
		{
			$count=0;
			foreach($persons as $person)
			{
				if($count == 0)
				{
					$this->set_contact_person_1($persons[0]);
				}
				else
				{
					$this->set_contact_person_2($persons[1]);
				}
				$count++;
			}
		}
		
		public function set_contact_person_1($contact_person_1)
		{
			$this->contact_person_1 = $contact_person_1;
		}
		
		public function get_contact_person_1() { return $this->contact_person_1; }
		
		public function set_contact_person_2($contact_person_2)
		{
			$this->contact_person_2 = $contact_person_2;
		}
		
		public function get_contact_person_2() { return $this->contact_person_2; }
		
		public function set_special_adaptation($special_adaptation)
		{
			$this->special_adaptation = $special_adaptation;
		}
		
		public function get_special_adaptation() { return $this->special_adaptation; }
		
		/**
		 * Get a static reference to the storage object associated with this model object
		 * 
		 * @return the storage object
		 */
		public static function get_so()
		{
			if (self::$so == null) {
				self::$so = CreateObject('activitycalendar.soactivity');
			}
			
			return self::$so;
		}
		
		public function serialize()
		{
			$date_format = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
			if(isset($this->organization_id) && $this->get_organization_id() > 0)
			{
				$contact_1 = activitycalendar_socontactperson::get_instance()->get_org_contact_name($this->get_contact_person_1());
				$contact_2 = activitycalendar_socontactperson::get_instance()->get_org_contact_name($this->get_contact_person_2());
			}
			else if(isset($this->group_id) && $this->get_group_id() > 0)
			{
				$contact_1 = activitycalendar_socontactperson::get_instance()->get_group_contact_name($this->get_contact_person_1());
				$contact_2 = activitycalendar_socontactperson::get_instance()->get_group_contact_name($this->get_contact_person_2());
			}
			else
			{
				$contact_1 = "";
				$contact_2 = "";
			}
			return array(
				'id' => $this->get_id(),
				'title' => $this->get_title(),
				'organization_id' => activitycalendar_soorganization::get_instance()->get_organization_name($this->get_organization_id()),
				'group_id' => activitycalendar_sogroup::get_instance()->get_group_name($this->get_group_id()),
				'district' => activitycalendar_soactivity::get_instance()->get_district_name($this->get_district()),
				'category' => $this->get_so()->get_category_name($this->get_category()),
				'description' => $this->get_description(),
				'state' => $this->get_state(),
				'arena' => activitycalendar_soarena::get_instance()->get_arena_name($this->get_arena()),
				'time' => $this->get_time(),
				'contact_person_1' => $contact_1,
				'contact_person_2' => $contact_2,
				'special_adaptation' => $this->get_special_adaptation(),
				'last_change_date' => $this->get_last_change_date()!=NULL?date($date_format, $this->get_last_change_date()):''
			);
		}
	}
?>