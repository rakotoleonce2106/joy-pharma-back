<?php

namespace Tests\Functional;
/**
 * Class HomepageTest
 * @package Tests\Functional
 */
class HomepageTest extends BaseTestCase
{
    /**
     * Test that the index route returns a rendered response containing the text 'SlimFramework' but not a greeting
     */
    public function testGetHomepageWithoutName()
    {
        $response = $this->runApp('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Gateway Sample PHP Code', (string)$response->getBody());
    }


    /**
     * Test that the index route won't accept a post request
     */
    public function testPostHomepageNotAllowed()
    {
        $response = $this->runApp('POST', '/', ['test']);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertContains('Method not allowed', (string)$response->getBody());
    }

    /**
     * Test that the authorize route returns a rendered response with session.js , PhpSample and Authorize
     */
    public function testGetAuthorize()
    {
        $response = $this->runApp('GET', '/authorize');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Authorize', (string)$response->getBody());
        $this->assertContains('session.js', (string)$response->getBody());
        $this->assertContains('PhpSample', (string)$response->getBody());
    }

    /**
     * Test that the capture route renders a response without the session.js url
     */
    public function testGetCapture()
    {
        $response = $this->runApp('GET', '/capture');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Capture', (string)$response->getBody());
        $this->assertNotContains('session.js', (string)$response->getBody());
        $this->assertNotContains('PhpSample', (string)$response->getBody());
    }



}