<?php

/*
 * Postcode Lookup Class
 * Author: Chris Reid chrisreiduk@gmail.com
 * 
 */

class Postcode
{
	public static $endpoint = 'http://maps.googleapis.com/maps/api/geocode/json';

	public $data = array();
	public $error;

	private static $default_region = 'uk'; //default value to be safe
	private $region = null;

	private $stored_postcode;
	private $lat = null;
	private $lng = null;

    private static $short_addr = array(
        '/Dr$/',
        '/Ct$/',
        '/Cl$/',
        '/St$/',
        '/Pl$/',
        '/Rd$/',
        '/Ln$/',
        '/Ave$/'
    );

    private static $long_addr = array(
        'Drive',
        'Court',
        'Close',
        'Street',
        'Place',
        'Road',
        'Lane',
        'Avenue'
    );

    public static function default_region($default_region)
    {
    	static::$default_region = $default_region;
    }

    public function set_postcode($postcode, $region = null)
	{
		$this->postcode = $postcode;
		if ($region !== null)
		{
			$this->region = $region;
		}
		$this->lat = $this->lng = null;
	}

	public function __construct($postcode = null, $region = null)
    {
    	if ($postcode !== null)
    	{
    		$this->set_postcode($postcode, $region);
    	}
    }

 	public static function is($postcode, $region = null)
	{
		return new static($postcode, $region);
	}

	/**
	 * Set the lat and lng values statically ( eg. Postcode::lat_lng_is({lat}, {lng}) )
	 * @param  [type] $lat [latitude]
	 * @param  [type] $lng [longitude]
	 * @return [Object]    [Postcode Object for method chaining]
	 */
	public static function lat_lng_is($lat, $lng)
	{
		$obj = new static;
		$obj->lat = $lat;
		$obj->lng = $lng;

		return $obj;
	}

	/**
	 * Populates the class lat and lng variables based on $stored_postcode
	 * @return [array] [latitude and longitude]
	 */
	public function get_lat_lng()
	{
		if ($this->region === null)
		{
			$this->region = static::$default_region;
		}

		$query = json_decode(file_get_contents(static::$endpoint . '?address=' . $this->stored_postcode . '&sensor=false&region=' . $this->region));

	    if ($query->status !== 'OK')
	    {
	        $this->error = 'Could not get location data. Please check postcode and try again';
	        return array('error' => $this->error);
	    }

	    $this->lat = $this->data['lat'] = $query->results[0]->geometry->location->lat;
	    $this->lng = $this->data['lng'] = $query->results[0]->geometry->location->lng;

	    return array('result' => $this->data);
	}

	/**
	 * Uses the class lat and lng values to get an address. If lat and lng are not set, call get_lat_lng first
	 * @param  [type] $house_number [House name or number]
	 * @return [array] [Address values. These values can also be accessed as object properties.]
	 */
	public function get_address($house_number = null)
	{
		if ($this->region === null)
		{
			$this->region = static::$default_region;
		}

		if ($this->lat === null || $this->lng === null)
		{
			$this->get_lat_lng();
		}

	    $query = json_decode(file_get_contents(static::$endpoint . '?latlng=' . $this->lat . ',' . $this->lng . '&sensor=false&region=' . $this->region));

	    if ($query->status !== 'OK')
	    {
	        $this->error = 'Could not get location data. Looks like the location is not a street address';
	        return array('error' => $this->error);
	    }

	    $this->data = array(
			'company_name'	=> '',
			'address1'		=> '',
			'address2'		=> '',
			'address3'		=> '',
			'city'			=> '',
			'county'		=> '',
			'country'		=> '',
			'postcode'		=> '',
			'lat'			=> $query->results[0]->geometry->location->lat,
	    	'lng'			=> $query->results[0]->geometry->location->lng,
		);

	    foreach ($query->results[0]->address_components as $component)
	    {
	        switch ($component->types[0])
	        {
	            case 'postal_code':
	                //$this->data['postcode'] = $component->long_name; TODO check this
	                break;
	            case 'administrative_area_level_2':
	                $this->data['county'] = $component->long_name;
	                break;
	            case 'postal_town':
	                $this->data['city'] = $component->long_name;
	                break;
	            case 'locality':
	            case 'sublocality':
	                $this->data['address3'] = $component->long_name;
	                break;
	            case 'route':
	                $this->data['address1'] = $component->long_name;
	                break;
	            case 'establishment':
	                $this->data['company_name'] = $component->long_name;
	        }
	    }

	    foreach ($query->results as $result)
	    {
	        foreach ($result->address_components as $component)
	        {
	            if ($component->types[0] == 'administrative_area_level_1')
	            {
	                $this->data['country'] = $component->long_name;
	                break;
	            }
	        }
	    }

	    if ($this->data['city'] === $this->data['address3'])
	    {
	        $this->data['address3'] = '';
	    }

	    $this->data['address1'] = preg_replace(static::$short_addr, static::$long_addr, $this->data['address1']);

	    if ($house_number !== null)
	    {
		    if (is_numeric($house_number))
	    	{
	    		$this->data['address1'] = $house_number . ' ' . $this->data['address1'];
			}
			else // $house_number is actually a house name
			{
				$this->data['address2'] = $this->data['address1'];
				$this->data['address1'] = ucwords(str_replace('_', ' ', $house_number));
			}
		}
		$this->data['postcode'] = static::format($this->stored_postcode, true);

	    return array('result' => $this->data);
	}

	/**
	 * Returns a formatted postcode with correct spaces and capitals. If $human = false, returns lower case stipped of
	 * spaces, suitable for storing in a Database or for inserting into a URI
	 * @param  string  $postcode [Postcode to be formatted]
	 * @param  boolean $human    [true = human formatted, false = suitable for database storage]
	 * @return string            [Formatted postcode]
	 */
	public static function format($postcode, $human = true)
	{
	    $postcode = str_replace(' ', '', $postcode);
	    $postcode = substr($postcode, 0, -3) . ($human ? ' ' : '') . substr($postcode, -3);
	    return $human ? strtoupper($postcode) : strtolower($postcode);
	}

	public function __get($key)
	{
		if (isset($this->data[$key]))
		{
			return $this->data[$key];
		}
	}

	public function __set($key, $value)
	{
		if ($key === 'postcode')
		{
			$this->stored_postcode = static::format($value, false);
		}
		else
		{
			$this->data['$key'] = $value;
		}
	}

}
