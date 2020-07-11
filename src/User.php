<?php
/**
 * @author Jeff Davis
 */
declare(strict_types=1);

namespace  EnterpriseDirectoryService;

/**
 * Class EdsUser
 *
 */
class User
{
    /**
     * Used to query attributes
     */
    const QUERYSTRING = "//dsml:entry/dsml:attr[@name='%s']/dsml:value";

    /**
     * Attributes that we want to retrieve. Index is our internal name,
     * the value is the name in the xml response.
     *
     * @var string[]
     */
    public $attributes = [
        'netid' => 'uid',
        'name' => 'preferredCn',
        'first_name' => 'preferredGivenname',
        'last_name' => 'preferredSn',
        'emplid' => 'emplId',
        'affiliation' => 'eduPersonPrimaryAffiliation',
        'email' => 'mail',
        'title' => 'employeeTitle',
        'ferpa' => 'employeeIsFerpaTrained',
        'dept_name' => 'employeePrimaryDeptName',
        'dept' => 'employeePrimaryDept',
        'building_number' => 'employeeBldgNum',
        'building_name' => 'employeeBldgName',
        'room_number' => 'employeeRoomNum',
        'phone' => 'employeePhone',
        'employee_type' => 'employeeType'
    ];

    /**
     * @var string for eds authentication
     */
    protected $username;

    /**
     * @var string for eds authentication
     */
    protected $password;

    /**
     * @var string for eds authentication
     */
    protected $baseUrl;

    /**
     * @var string raw result from eds request
     */
    protected $dsml;

    /**
     * @var string dsml response converted to SimpleXml
     */
    protected $xml;

    /**
     * @return $this
     */
    public function setOptionsFromEnvironment()
    {
        $options = [];
        if ($_ENV['EDS_USER']) {
            $options['EDS_USER'] = $_ENV['EDS_USER'];
        }
        if ($_ENV['EDS_PASSWORD']) {
            $options['EDS_PASSWORD'] = $_ENV['EDS_PASSWORD'];
        }
        if ($_ENV['EDS_URL']) {
            $options['EDS_URL'] = $_ENV['EDS_URL'];
        }
        $this->setOptions($options);
        return $this;
    }

    /**
     * Sometimes you might want to manually set them instead of using
     * environment variables
     *
     * @param $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->baseUrl  = $options['EDS_URL'];
        $this->username = $options['EDS_USER'];
        $this->password = $options['EDS_PASSWORD'];
        return $this;
    }

    /**
     * Make sure all options to (eds user, pass and url)
     *
     * @return bool
     */
    public function validate()
    {
        if (isset($this->username) && isset($this->password) && isset($this->baseUrl)) {
            return true;
        }
        return false;
    }

    /**
     * @param $userId
     * @return false|string
     */
    public function requestResponse($userId)
    {
        $url = $this->baseUrl.'/'.$userId;
        $cred = sprintf(
            'Authorization: Basic %s',
            base64_encode($this->username.':'.$this->password)
        );
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $cred
            ]
        ]);

        // send our request and retrieve the DSML response
        $this->dsml = file_get_contents($url, false, $ctx);
        return $this->convertDsml();
    }

    /**
     * We convert the dsml to SimpleXML for easy
     * querying
     *
     * @return \SimpleXMLElement|string
     */
    public function convertDsml()
    {
        $this->xml = new \SimpleXMLElement($this->dsml);
        return $this->xml;
    }

    /**
     * This always returns an array. Most of the time there is one
     * element. Sometimes more.
     *
     * @param $attribute
     * @return array
     */
    public function queryAttribute($attribute)
    {
        return array_map(
            function ($item) {
                return $item->__toString();
            },
            $this->xml->xpath(sprintf($this::QUERYSTRING, $attribute))
        );
    }

    /**
     * This will always return the first attribute.
     * There is usually only one anyway.
     *
     * @param $attribute
     * @return string
     */
    public function queryFirstAttribute($attribute)
    {
        $result = $this->xml->xpath(
            sprintf($this::QUERYSTRING, $attribute)
        );
        if (is_array($result) && isset($result[0])) {
            return $result[0]->__toString();
        } else {
            return null;
        }
    }

    /**
     * Return an array of desired attributes
     *
     * @param  bool  $firstOnly  first value returned as string
     * @return array
     */
    public function getAllAttributes($firstOnly = true)
    {
        if (is_null($this->xml)) {
            $this->requestResponse();
        }
        $results = [];
        foreach ($this->attributes as $field => $attribute) {
            /**
             * Some fields needs special handling like first and last name
             *    first_name => queryFieldFirstName()
             */
            $specialFieldMethod = 'queryField' . str_replace('_','',ucwords($field, '_'));
            if (method_exists($this, $specialFieldMethod)) {
                $results[$field] = $this->{$specialFieldMethod}();
            } else {
                if ($firstOnly) {
                    $results[$field] = $this->queryFirstAttribute($attribute);
                } else {
                    $results[$field] = $this->queryAttribute($attribute);
                }
            }
        }
        return $results;
    }

    /**
     * Special handing for first_name field
     * - If there is a preferred name, get that
     *   otherwise use the given name
     *
     * @return string
     */
    public function queryFieldFirstName()
    {
        $preferred = $this->queryFirstAttribute("preferredGivenname");
        if (is_null($preferred)) {
            return $this->queryFirstAttribute("givenName");
        }
        return $preferred;
    }

    /**
     * Special handing for last_name field
     * - If there is a preferred name, get that
     *   otherwise use the given name
     *
     * @return string
     */
    public function queryFieldLastName()
    {
        $preferred = $this->queryFirstAttribute("preferredSn");
        if (is_null($preferred)) {
            return $this->queryFirstAttribute("sn");
        }
        return $preferred;
    }

    /**
     * Special handing for name (first and last) field
     * - If there is a preferred name, get that
     *   otherwise use the given name
     *
     * @return string
     */
    public function queryFieldName()
    {
        $preferred = $this->queryFirstAttribute("preferredCn");
        if (is_null($preferred)) {
            return $this->queryFirstAttribute("cn");
        }
        return $preferred;
    }

    /**
     * Most of the time, this will work
     * If the environment variables are set
     * See validate() method
     *
     * @param $userId
     * @return mixed
     */
    public static function retrieveById($userId)
    {
        $edsUserInfo = new self;
        $edsUserInfo->setOptionsFromEnvironment()
            ->requestResponse($userId);
        return $edsUserInfo->getAllAttributes();
    }

    /**
     * This would be helpful if you wanted to add
     * an extra field or two to query. The key
     * is what you want to call the returned
     * attribute.
     *
     * @param $array
     * @return $this
     */
    public function addAttributes($array)
    {
        foreach ($array as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /** getters and setters **/

    /**
     * @return string[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  string[]  $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }


    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param  mixed  $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param  mixed  $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param  mixed  $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return string
     */
    public function getDsml(): ?string
    {
        return $this->dsml;
    }

    /**
     * @param  string  $dsml
     */
    public function setDsml(string $dsml): void
    {
        $this->dsml = $dsml;
    }

    /**
     * @return object
     */
    public function getXml(): object
    {
        return $this->xml;
    }

    /**
     * @param  \SimpleXMLElement  $xml
     */
    public function setXml(\SimpleXMLElement $xml)
    {
        $this->xml = $xml;
    }
}
