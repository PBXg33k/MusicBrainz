<?php

namespace MusicBrainz\Tests;

use MusicBrainz\HttpAdapters\AbstractHttpAdapter;
use MusicBrainz\HttpAdapters\GuzzleFiveAdapter;
use MusicBrainz;

/**
 * @covers MusicBrainz\MusicBrainz
 */
class MusicBrainzTest extends \PHPUnit_Framework_TestCase
{
    const USERNAME = 'testuser';
    const PASSWORD = 'testpass';
    /**
     * @var \MusicBrainz\MusicBrainz
     */
    protected $brainz;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpAdapter;

    public function setUp()
    {
        $this->httpAdapter = $httpAdapter = $this->createMock(AbstractHttpAdapter::class);

        $this->brainz = new MusicBrainz\MusicBrainz($httpAdapter, self::USERNAME, self::PASSWORD );
    }

    /**
     * @return array
     */
    public function MBIDProvider()
    {
        return array(
            array(true, '4dbf5678-7a31-406a-abbe-232f8ac2cd63'),
            array(true, '4dbf5678-7a31-406a-abbe-232f8ac2cd63'),
            array(false, '4dbf5678-7a314-06aabb-e232f-8ac2cd63'), // invalid spacing for UUID's
            array(false, '4dbf5678-7a31-406a-abbe-232f8az2cd63') // z is an invalid character
        );
    }

    /**
     * @dataProvider MBIDProvider
     */
    public function testIsValidMBID($validation, $mbid)
    {
        $this->assertEquals($validation, $this->brainz->isValidMBID($mbid));
    }

    public function testHttpOptions()
    {
        $applicationName = 'php-musibrainz';
        $version = '1.0.0';
        $contactInfo = 'development@oguzhanuysal.eu';

        $this->brainz->setUserAgent($applicationName, $version, $contactInfo);

        $userAgent = $applicationName . '/' . $version . ' (' . $contactInfo . ')';

        $httpOptionsExpect = [
            'method'        => 'GET',
            'user-agent'    => $userAgent,
            'user'          => self::USERNAME,
            'password'      => self::PASSWORD
        ];

        $this->assertEquals($httpOptionsExpect, $this->brainz->getHttpOptions());
        $this->assertEquals($userAgent, $this->brainz->getUserAgent());
    }

    public function testGetSetters()
    {
        $this->assertEquals(self::USERNAME, $this->brainz->getUser());
        $this->assertEquals(self::PASSWORD, $this->brainz->getPassword());
    }

    /**
     * @test
     * @expectedException MusicBRainz\Exception
     */
    public function userAgentVersionCannotContainDash()
    {
        $this->brainz->setUserAgent('application', '1.0-beta', 'test');
    }
}
