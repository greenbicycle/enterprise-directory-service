<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use EdsUser\EdsUserInfo;

class EdsUserInfoTest extends TestCase
{
    public $edsUserInfo;
    public $extraAttributes;

    public function setUp() : void
    {
        $this->edsUserInfo = new EdsUserInfo();
        $this->extraAttributes = [
          'statusHistory' => 'studentStatusHistory'
        ];
    }
    public function testThatClassExists()
    {
        $this->assertTrue(class_exists(EdsUserInfo::class));
    }

    public function testThatOptionsCanBeSetFromEnvironment()
    {
        // This should fail. Options haven't been set yet.
        $this->assertFalse(
            $this->edsUserInfo->validate()
        );

        $this->assertTrue(
            $this->edsUserInfo->setOptionsFromEnvironment()
                ->validate()
        );
    }

    public function testThatAttributeCanBeQueried()
    {
        $this->edsUserInfo->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUserInfo->queryAttribute('uid')) > 0
        );
        $this->assertTrue(
            count($this->edsUserInfo->queryAttribute('cn')) > 0
        );
    }
    public function testThatNonexistentAttributeCannotBeQueried()
    {
        $this->edsUserInfo->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertEquals(0, count($this->edsUserInfo->queryAttribute('foo')));
    }

    public function testThatGetAllAttributesMethodWorks()
    {
        $this->edsUserInfo->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUserInfo->getAllAttributes()) > 0
        );
    }
    public function testThatGetAllAttributesMethodWorksWithFirstOnly()
    {
        $this->edsUserInfo->setOptionsFromEnvironment()
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            count($this->edsUserInfo->getAllAttributes(true)) > 0
        );
    }

    /**
     *
     */
    public function testThatExtraAttributesCanBeAdded()
    {
        $this->edsUserInfo->setOptionsFromEnvironment()
            ->addAttributes($this->extraAttributes)
            ->requestResponse(getenv('EDS_TEST_ID'));
        $this->assertTrue(
            array_key_exists(
                array_key_first($this->extraAttributes),
                $this->edsUserInfo->getAllAttributes()
            )
        );
    }
}
