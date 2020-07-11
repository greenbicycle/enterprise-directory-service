![CI](https://github.com/greenbicycle/enterprise-directory-service/workflows/CI/badge.svg)

# Enterprise Directory Services (EDS)

EDS is a DSML (Directory Service Markup Language) api.

This is a simple class with some tests. The objective is to pull data from the api 
and convert it into an usable array for either filling out a form or inserting 
into a database.

This will not work unless you have been provided credentials and access.

## Install

`composer require greenbicycle/enterprise-directory-service`

## Usage
```
# You might need an alias
use EnterpriseDirectoryService\User as EdsUser;

# This also works with emplid
$results = EdsUser::retrieveById('netid');

```

## See also

* https://confluence.arizona.edu/x/aAXjAQ
* https://www.oasis-open.org/committees/dsml/faq.php 
