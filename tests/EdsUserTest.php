<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use EnterpriseDirectoryService\EdsUser;

class EdsUserTest extends TestCase
{
    public $edsUser;
    public $extraAttributes;

    public function setUp() : void
    {
        $this->edsUser = new EdsUser();
        $this->extraAttributes = [
          'statusHistory' => 'studentStatusHistory'
        ];
    }
    public function testThatClassExists()
    {
        $this->assertTrue(class_exists(EdsUser::class));
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
        $this->edsUser->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUser->queryAttribute('uid')) > 0
        );
        $this->assertTrue(
            count($this->edsUser->queryAttribute('cn')) > 0
        );
    }
    public function testThatNonexistentAttributeCannotBeQueried()
    {
        $this->edsUser->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertEquals(0, count($this->edsUser->queryAttribute('foo')));
    }

    public function testThatGetAllAttributesMethodWorks()
    {
        $this->edsUser->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUser->getAllAttributes()) > 0
        );
    }
    public function testThatGetAllAttributesMethodWorksWithFirstOnly()
    {
        $this->edsUser->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUser->getAllAttributes(true)) > 0
        );
    }

    /**
     *
     */
    public function testThatExtraAttributesCanBeAdded()
    {
        $this->edsUser->setOptionsFromEnvironment()
            ->addAttributes($this->extraAttributes)
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            array_key_exists(
                array_key_first($this->extraAttributes),
                $this->edsUser->getAllAttributes()
            )
        );
    }
}
