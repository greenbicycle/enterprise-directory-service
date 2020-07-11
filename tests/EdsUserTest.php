<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use EnterpriseDirectoryService\User;

class EdsUserTest extends TestCase
{
    public $edsUser;
    public $extraAttributes;

    public function setUp() : void
    {
        $this->edsUser = new User();
        $this->extraAttributes = [
          'statusHistory' => 'studentStatusHistory'
        ];
        // Fake a request
        // todo: maybe have more than one example file?
        $this->dsml = file_get_contents(__DIR__ . "/example.dsml.xml");
        $this->edsUser->setDsml($this->dsml);
        $this->edsUser->convertDsml();

    }

    public function testThatOptionsCanBeSetFromEnvironment()
    {
        // This should fail. Options haven't been set yet.
        $this->assertFalse(
            $this->edsUser->validate()
        );

        $this->assertTrue(
            $this->edsUser->setOptionsFromEnvironment()
                ->validate()
        );
    }

    public function testThatAttributeCanBeQueried()
    {
        $this->assertTrue(
            count($this->edsUser->queryAttribute('uid')) > 0
        );
        $this->assertTrue(
            count($this->edsUser->queryAttribute('cn')) > 0
        );
    }

    public function testThatGetAllAttributesMethodWorks()
    {
        $this->assertTrue(
            count($this->edsUser->getAllAttributes()) > 0
        );
    }

    public function testThatGetAllAttributesMethodWorksWithFirstOnly()
    {
        $results = $this->edsUser->getAllAttributes(true);
        $this->assertTrue(
            count($results) > 0
        );
    }

    /**
     * @dataProvider userFieldProvider
     */
    public function testThatCertainFieldsAreNotNull($field)
    {
        $results = $this->edsUser->getAllAttributes(true);
        $this->assertFalse(
            is_null($results[$field])
        );
    }

    public function userFieldProvider()
    {
        return [
            ['affiliation'],
            ['first_name'],
            ['last_name'],
            ['name'],
            ['netid'],
            ['email']
        ];
    }


    public function testThatExtraAttributesCanBeAdded()
    {
        $this->edsUser->addAttributes($this->extraAttributes);
        $this->assertTrue(
            array_key_exists(
                array_key_first($this->extraAttributes),
                $this->edsUser->getAllAttributes()
            )
        );
    }
}
