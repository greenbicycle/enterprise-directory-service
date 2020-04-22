<?php
declare(strict_types=1);

namespace EdsUser;

class EdsUserInfo
{
    /**
     * Used to query attributes
     */
    const queryString = "//dsml:entry/dsml:attr[@name='%s']/dsml:value";

    /**
     * Attributes that we want to retrieve. Index is our internal name,
     * the value is the name in the xml response.
     *
     * @var string[]
     */
    public $attributes = [
        'email' => 'mail',
        'title' => 'employeeTitle',
        'name' => 'preferredCn',
        'fname' => 'preferredGivenname',
        'lname' => 'sn',
        'deptName' => 'employeePrimaryDeptName',
        'phone' => 'employeePhone',
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
     * @var string dsml response
     */
    protected $xml;

    public function setOptionsFromEnvironment()
    {
        $options = [];
        if (getenv('EDS_USER')) {
            $options['EDS_USER'] = getenv('EDS_USER');
        }
        if (getenv('EDS_PASSWORD')) {
            $options['EDS_PASSWORD'] = getenv('EDS_PASSWORD');
        }
        if (getenv('EDS_URL')) {
            $options['EDS_URL'] = getenv('EDS_URL');
        }
        $this->setOptions($options);
        return $this;
    }

    public function setOptions($options)
    {
        $this->baseUrl = $options['EDS_URL'];
        $this->username = $options['EDS_USER'];
        $this->password = $options['EDS_PASSWORD'];
        return $this;
    }

    /**
     * Make sure all fields are set
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
        $url = $this->baseUrl . '/' . $userId;
        $cred = sprintf(
            'Authorization: Basic %s',
            base64_encode($this->username . ':' . $this->password)
        );
        $ctx = stream_context_create([
            'http' => [
                'method'=>'GET',
                'header'=>$cred
            ]
        ]);

        // send our request and retrieve the DSML response
        $dsml =  file_get_contents($url, false, $ctx);
        $this->xml = new \SimpleXMLElement($dsml);
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
            $this->xml->xpath(sprintf($this::queryString, $attribute))
        );
    }

    /**
     * This will always return the first attribute.
     * There is usually only one anyway.
     *
     * @param $attribute
     * @return array
     */
    public function queryFirstAttribute($attribute) {
        return $this->xml->xpath(
            sprintf($this::queryString, $attribute)
        )[0]->__toString();
    }

    /**
     * Return an array of desired attributes
     *
     * @param  bool  $firstOnly first value returned as string
     * @return array
     */
    public function getAllAttributes($firstOnly = true)
    {
        if (is_null($this->xml)) {
            $this->requestResponse();
        }
        $results = [];
        foreach ($this->attributes as $field => $attribute) {
            if ($firstOnly) {
                $results[$field] = $this->queryFirstAttribute($attribute);
            } else {
                $results[$field] = $this->queryAttribute($attribute);
            }
        }
        return $results;
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
    public function addAttributes($array) {
        foreach( $array as $key => $value) {
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
    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * @param  string  $xml
     */
    public function setXml(string $xml)
    {
        $this->xml = $xml;
    }
}
