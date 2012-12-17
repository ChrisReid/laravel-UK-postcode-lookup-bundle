<?php

/*
 * Postcode Lookup Class
 * Author: Chris Reid chrisreiduk@gmail.com
 * 
 */

class Postcode
{
	public static $endpoint = 'http://maps.googleapis.com/maps/api/geocode/json';

	/**
	 * If set to true, we will allow the postcode search functions to auto detect if an address
	 * search is attempted and search accordinly. If set to false, an error will be returned if
	 * we attempt to search an address with the postcode search functions.
	 * @var boolean
	 */
	public static $auto_address_default = true;

	/**
	 * The class default region which can be set using Postcode::set_default_region('uk');
	 * @var string
	 */
	private static $default_region = 'uk'; //default value to be safe

	public $data = array();
	public $error = null;

	/**
	 * This is the instance region setting which can the class $default_region setting above
	 * @var string
	 */
	private $region = null;

	private $stored_postcode = null;
	private $address = null;
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

    /**
     * Set the postcode to search for (or address if $auto_address = true)
     * @param string  $postcode     [Postcode to lookup (or address if $auto_address = true)]
     * @param string  $region       [Google supported region. If not set, we default to 'uk']
     * @param boolean $auto_address	[Allows us to override the default setting in the Class declaration at the top]
     */
    public function set_postcode($postcode, $region = null, $auto_address = null)
	{
		if ($auto_address === null)
		{
			$auto_address = $this->auto_address;
		}

		if (static::is_valid_uk($postcode))
		{
			$this->stored_postcode = $postcode;
		}
		elseif ($auto_address)
		{
			$this->address = $postcode;
		}
		else
		{
			$this->error = $postcode . ' is not a valid postcode.';
			return array('error' => $this->error);
		}

		if ($region !== null)
		{
			$this->region = $region;
		}
		$this->lat = $this->lng = null;
	}

	public function __construct($postcode = null, $region = null, $auto_address = null)
    {
    	if ($postcode !== null)
    	{
    		$this->set_postcode($postcode, $region, $auto_address);
    	}
    }

 	public static function is($postcode, $region = null, $auto_address = null)
	{
		return new static($postcode, $region, $auto_address);
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

	public static function address_is($address)
	{
		$obj = new static;
		$obj->address = $address;

		return $obj;
	}

	/**
	 * Populates the class lat and lng variables based on $stored_postcode
	 * @return [array] [latitude and longitude]
	 */
	public function get_lat_lng()
	{
		if ($this->error !== null)
		{
			 array('error' => $this->error);
		}

		if ($this->region === null)
		{
			$this->region = static::$default_region;
		}

		if ($this->stored_postcode === null)
		{
			if ($this->address === null)
			{
				$this->error = 'Please set a postcode or address to search on.';
				return array('error' => $this->error);
			}
			$search = $this->address;
		}
		else
		{
			$search = $this->stored_postcode;
		}

		$query = json_decode(file_get_contents(static::$endpoint . '?address=' . $search .
			'&sensor=false&region=' . $this->region));

	    if ($query->status !== 'OK')
	    {
	        $this->error = 'Could not get location data. Please check postcode or address and try again';
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
		if ($this->error !== null)
		{
			return array('error' => $this->error);
		}

		if ($this->region === null)
		{
			$this->region = static::$default_region;
		}

		if ($this->stored_postcode !== null && ($this->lat === null || $this->lng === null))
		{	
			$this->get_lat_lng();
			if ($this->error !== null)
			{
				return array('error' => $this->error);
			}
		}
		
		if ($this->lat !== null && $this->lng !== null)
		{
			$query = json_decode(file_get_contents(static::$endpoint . '?latlng=' .
				$this->lat . ',' . $this->lng . '&sensor=false&region=' . $this->region));
		}
		elseif ($this->address !== null)
		{
		    $search = $this->address;
		    if ($house_number !== null)
		    {
		    	$search = $house_number . '_' . $search;
		    }
		    $query = json_decode(file_get_contents(static::$endpoint . '?address=' .
		    	$search . '&sensor=false&region=' . $this->region));
		}
		else
		{
			$this->error = 'Please set a postcode or address to search on.';
			return array('error' => $this->error);
		}

	    if ($query->status !== 'OK')
	    {
	        $this->error = 'Could not get location data. Looks like the location is not a recognised street address';
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
	                $this->data['postcode'] = $component->long_name;
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

	    // Avoid repition where postal_town was duplicated in sub_locality
	    if ($this->data['city'] === $this->data['address3'])
	    {
	        $this->data['address3'] = '';
	    }

	    // Replace short names ie. St > Street, Rd > Road etc
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
		if (empty($this->data['postcode']))
		{
			//$this->data['postcode'] = static::format($this->stored_postcode, true);
		}
		
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
	    if ( ! static::is_valid_uk($postcode)) return $postcode;

	    $postcode = str_replace(' ', '', $postcode);
	    $postcode = substr($postcode, 0, -3) . ($human ? ' ' : '') . substr($postcode, -3);
	    return $human ? strtoupper($postcode) : strtolower($postcode);
	}

	/**
	 * Returns true if postcode format is value
	 * @param  [type]  $postcode [Postcode to check]
	 * @return boolean           [true if valid, false if not]
	 */
	public static function is_valid_uk($postcode)
	{
		$regex = '/^((((A[BL]|B[ABDHLNRSTX]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[HNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|'.
			'I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTY]?|'.
			'T[ADFNQRSW]|UB|W[ADFNRSV]|YO|ZE)[1-9]?[0-9]|((E|N|NW|SE|SW|W)1|EC[1-4]|WC[12])[A-HJKMNPR-Y]'.
			'|(SW|W)([2-9]|[1-9][0-9])|EC[1-9][0-9])[0-9][ABD-HJLNP-UW-Z]{2}))$/';

		return preg_match($regex, str_replace(' ', '', strtoupper($postcode))) ? true : false;
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
		elseif ($key === 'address')
		{
			$this->address = str_replace(' ', '_', $value);
		}
		else
		{
			$this->data['$key'] = $value;
		}
	}

}
