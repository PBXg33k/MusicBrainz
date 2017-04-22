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
     */
    public function willValidateFilter()
    {
        $this->assertTrue($this->brainz->validateFilter(['official'], MusicBrainz\MusicBrainz::$validReleaseStatuses));
    }

    /**
     * @test
     * @expectedException MusicBrainz\Exception
     */
    public function willThrowExceptionIfFilterValidationFails()
    {
        $this->brainz->validateFilter(['Invalid'], MusicBrainz\MusicBrainz::$validReleaseTypes);
    }

    /**
     * @test
     */
    public function willValidateInclude()
    {
        $includes = array(
            'releases',
            'recordings',
            'release-groups',
            'user-ratings'
        );

        $this->assertTrue($this->brainz->validateInclude($includes, MusicBrainz\MusicBrainz::$validIncludes['artist']));
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function willThrowOutOfBoundsExceptionIfIncludeValidationFails()
    {
        $this->brainz->validateInclude(['out-of-bound'], MusicBrainz\MusicBrainz::$validIncludes['artist']);
    }

    /**
     * @test
     * @expectedException MusicBRainz\Exception
     */
    public function userAgentVersionCannotContainDash()
    {
        $this->brainz->setUserAgent('application', '1.0-beta', 'test');
    }

    public function testLookup()
    {
        $includes = array(
            'releases',
            'recordings',
            'release-groups',
            'user-ratings'
        );

        $this->httpAdapter->expects($this->once())
            ->method('call')
            ->willReturn('{"secondary-type-ids":["dd2a21e1-0c00-3729-a7a0-de60b84eb5d1","0c60f497-ff81-3818-befd-abfc84a4858b"],"id":"e4307c5f-1959-4163-b4b1-ded4f9d786b0","title":"Born This Way: The Remix","secondary-types":["Compilation","Remix"],"disambiguation":"","first-release-date":"2011-11-18","primary-type-id":"f529b476-6e62-324f-b0aa-1f3e33d313fc","primary-type":"Album"}');

        $this->brainz->lookup('artist', '4dbf5678-7a31-406a-abbe-232f8ac2cd63', $includes);
    }

    public function testSearch()
    {
        $args = [
            'artist' => 'Weezer'
        ];

        $this->httpAdapter->expects($this->once())
            ->method('call')
            ->willReturn(get_object_vars(\GuzzleHttp\json_decode('{"created":"2017-04-22T00:34:45.859Z","count":89,"offset":0,"release-groups":[{"id":"37e08cb3-ef16-321a-a76c-efdb2f90d4bf","score":"100","count":1,"title":"Lion and the Witch \'redux\'","primary-type":"Single","secondary-types":["Live"],"artist-credit":[{"artist":{"id":"6fe07aa5-fec0-4eca-a456-f29bff451b04","name":"Weezer","sort-name":"Weezer"}}],"releases":[{"id":"15d527b3-f248-4b5f-bb01-7b3c78e20b7c","title":"Lion and the Witch \'redux\'","status":"Official"}]}]}')));

        $releaseGroups = $this->brainz->search(new MusicBrainz\Filters\ReleaseGroupFilter($args));

        $this->assertEquals(MusicBrainz\ReleaseGroup::class, get_class($releaseGroups[0]));
    }
}
