<?php

//namespace WoocommerceOpenPix\Tests;

//use PHPUnit\Framework\TestCase;

class WP_OpenPix_VerifyTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->class_instance = new WC_OpenPix();
    }

    // add tests
    //    public function test_google_site_verification()
    //    {
    //        $meta_tag = $this->class_instance->google_site_verification('B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g');
    //        $expected = '<meta name="google-site-verification" content="B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g">';
    //
    //        $this->assertEquals($expected, $meta_tag);
    //    }
    //
    //    public function test_bing_site_verification()
    //    {
    //        $meta_tag = $this->class_instance->bing_site_verification('B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g');
    //        $expected = '<meta name="msvalidate.01" content="B6wFaCRbzWE42SyxSvKUOyyPxZfJCb5g">';
    //
    //        $this->assertEquals($expected, $meta_tag);
    //    }
}
