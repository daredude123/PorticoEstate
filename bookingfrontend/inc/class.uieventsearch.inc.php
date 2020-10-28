<?php
phpgw::import_class("booking.uicommon");
phpgw::import_class('bookingfrontend.bosearch');
phpgw::import_class('booking.bobooking');


class bookingfrontend_uieventsearch extends booking_uicommon
{

	public $public_functions = array
	(
		'index' => true,
		'show'  => true,
		'upcomingEvents' => true
	);

	protected $module;
	protected $bosearch;
	protected $bo_booking;

	public function __construct()
	{
		parent::__construct();
		$this->module= "bookingfrontend";
		$this->bosearch = new bookingfrontend_bosearch();
		$this->bo_booking = new booking_bobooking();
	}

	public function show()
	{
		phpgwapi_jquery::load_widget('autocomplete');

		$event_search['dickens'] = "test";
		$config = CreateObject('phpgwapi.config', 'booking');
		$config->read();
		phpgwapi_jquery::load_widget("core");

		self::add_javascript('bookingfrontend', 'base', 'event_search.js', 'text/javascript', true);
		self::render_template_xsl('event_search', array('event_search' => $event_search));

	}

	/***
	 * Metode for å hente events til søkesiden
	 */
	public function upcomingEvents()
	{
		$orgName = phpgw::get_var('orgName', 'string', 'REQUEST', null);
		$currentDate = date('Y-m-d H:i:s');
		$events = $this->bosearch->soevent->get_events_from_date($currentDate, $orgName);
		return $events;
	}

	public function query()
	{
		// TODO: Implement query() method.
	}
	public function index()
	{
		if (phpgw::get_var('phpgw_return_as') == 'json')
		{
			return $this->query();
		}

		phpgw::no_access();
	}

	private function addOrganizationUrl($event)
	{
		$event["org_url"] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uiorganization.show', 'id' => $event['org_id']));
		return $event;
	}

	private function addBuildingUrl($event)
	{
		$event["building_url"] = $GLOBALS['phpgw']->link('/bookingfrontend/', array('menuaction' => 'bookingfrontend.uibuilding.show', 'id' => $event['building_id']));
		return $event;
	}
}