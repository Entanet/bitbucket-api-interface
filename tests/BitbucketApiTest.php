<?php
namespace Tests;

use Bitbucket\Client\BitbucketApi;
use Bitbucket\Exceptions\BitbucketApiReturn401Exception;
use PHPUnit\Framework\TestCase;

class BitbucketApiTest extends TestCase
{
    protected $bitbucketApi;
    protected $bitbucketPartialMockCurlPost;
    protected $bitbucketPartialMockCurl;
    protected $reflectedBitbucketApi;

    protected $setTokenMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bitbucketApi = new BitbucketApi(
            'aabbcc1122',
            'secret_string',
            'token',
            'refresh',
            'expires');

        $this->bitbucketPartialMockCurlPost = \Mockery::mock('Bitbucket\Client\BitbucketApi[sendCurlPost]',
            ['aabbcc1122', 'secret_string', 'token', 'refresh', 'expires'])->shouldAllowMockingProtectedMethods();

        $this->bitbucketPartialMockCurl = \Mockery::mock('Bitbucket\Client\BitbucketApi[sendCurl]',
            ['aabbcc1122', 'secret_string', 'token', 'refresh', 'expires'])->shouldAllowMockingProtectedMethods();
    }

    public function testGetTokenFromRefresh()
    {
        $this->bitbucketPartialMockCurlPost->shouldReceive('sendCurlPost')
            ->with('https://bitbucket.org/site/oauth2/access_token',
                ['grant_type=refresh_token', 'refresh_token=refresh'],
                false)
            ->andReturn(['body' =>'{"access_token": "new_access", "refresh_token": "new_refresh", "expires_in": "new_expires"}']);

        $this->bitbucketPartialMockCurlPost->getTokenFromRefresh();

        $this->assertEquals('new_access', $this->bitbucketPartialMockCurlPost->getToken());
        $this->assertEquals('new_refresh', $this->bitbucketPartialMockCurlPost->getRefresh());
        $this->assertEquals('new_expires', $this->bitbucketPartialMockCurlPost->getExpiresIn());
    }

    public function testGetTokenFromRefreshWithException()
    {
        $this->bitbucketPartialMockCurlPost->shouldReceive('sendCurlPost')
            ->with('https://bitbucket.org/site/oauth2/access_token',
                ['grant_type=refresh_token', 'refresh_token=refresh'],
                false)
            ->andThrow(BitbucketApiReturn401Exception::class);

        $this->expectException(BitbucketApiReturn401Exception::class);

        $this->bitbucketPartialMockCurlPost->getTokenFromRefresh();
    }

    public function testSendCurlPostWithToken()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                CURLOPT_POST => true,
                CURLOPT_USERPWD => null,
                CURLOPT_POSTFIELDS => ['post_var' => 'value']],
                ['Authorization: Bearer token'])
            ->andReturn(['body' => 'Test response body']);

        $this->assertEquals(['body' => 'Test response body'],
            $this->bitbucketPartialMockCurl->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
            ['post_var' => 'value'],
            true));
    }

    public function testSendCurlPostWithBasicAuth()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                CURLOPT_POST => true,
                CURLOPT_USERPWD => 'aabbcc1122:secret_string',
                CURLOPT_POSTFIELDS => ['post_var' => 'value']],
                [])
            ->andReturn(['body' => 'Test response body']);

        $this->assertEquals(['body' => 'Test response body'],
            $this->bitbucketPartialMockCurl->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
                ['post_var' => 'value'],
                false));
    }

    public function testSendCurlPostThrowException()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                CURLOPT_POST => true,
                CURLOPT_USERPWD => 'aabbcc1122:secret_string',
                CURLOPT_POSTFIELDS => ['post_var' => 'value']],
                [])
            ->andThrow(BitbucketApiReturn401Exception::class);

        $this->expectException(BitbucketApiReturn401Exception::class);

        $this->bitbucketPartialMockCurl->sendCurlPost('https://bitbucket.org/site/oauth2/access_token',
            ['post_var' => 'value'],
            false);
    }

    public function testSendCurlGetWithToken()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                    CURLOPT_USERPWD => null
                ],
                ['Authorization: Bearer token'])
            ->andReturn(['body' => 'Test response body']);

        $this->assertEquals(['body' => 'Test response body'],
                $this->bitbucketPartialMockCurl->sendCurlGet('https://bitbucket.org/site/oauth2/access_token',
                true));
    }

    public function testSendCurlWithBasicAuth()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                CURLOPT_USERPWD => 'aabbcc1122:secret_string'
                ],
                [])
            ->andReturn(['body' => 'Test response body']);

        $this->assertEquals(['body' => 'Test response body'],
            $this->bitbucketPartialMockCurl->sendCurlGet('https://bitbucket.org/site/oauth2/access_token',
                false));
    }

    public function testSendCurlThrowException()
    {
        $this->bitbucketPartialMockCurl->shouldReceive('sendCurl')
            ->with('https://bitbucket.org/site/oauth2/access_token', [
                CURLOPT_USERPWD => 'aabbcc1122:secret_string'
                ],
                [])
            ->andThrow(BitbucketApiReturn401Exception::class);

        $this->expectException(BitbucketApiReturn401Exception::class);

        $this->bitbucketPartialMockCurl->sendCurlGet('https://bitbucket.org/site/oauth2/access_token',
            false);
    }

    public function testGetToken()
    {
        $this->assertEquals('token', $this->bitbucketApi->getToken());
    }

    public function testGetRefresh()
    {
        $this->assertEquals('refresh', $this->bitbucketApi->getRefresh());
    }

    public function testGetExpiresIn()
    {
        $this->assertEquals('expires', $this->bitbucketApi->getExpiresIn());
    }
}