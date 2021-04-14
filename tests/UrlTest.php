<?php

use lenz\linkfield\models\Url;
use PHPUnit\Framework\TestCase;

/**
 * Class UrlTest
 */
class UrlTest extends TestCase
{
  /**
   * @var array
   */
  const URIS = [
    'https://craftcms.com/',
    'https://craftcms.com',
    'https://craftcms.com/my/path',
    'https://craftcms.com/my/path/',
    'https://craftcms.com/my/path/index.html',
    'https://craftcms.com/my/path/index.html?param=value',
    'https://craftcms.com/my/path/index.html?param=value#home',

    // No scheme
    '//craftcms.com',
    '//craftcms.com/my/path',
    '//craftcms.com/my/path/',
    '//craftcms.com/my/path/index.html',
    '//craftcms.com/my/path/index.html?param=value',
    '//craftcms.com/my/path/index.html?param=value#home',

    // No protocol
    'craftcms.com',
    'craftcms.com/path',
    '/',
    '/my/path',
    '/my/path/',
    '/my/path/index.html',
    '/my/path/index.html?param=value',
    '/my/path/index.html?param=value#home',

    // Auth
    'http://user:pass@craftcms.com',
    'http://user@craftcms.com',
    '//user:pass@craftcms.com',
    '//user@craftcms.com',
  ];


  /**
   * @throws Exception
   */
  public function testFragment() {
    $url = new Url('craftcms.com?p=home#fragment');
    $url->setFragment('newFragment');
    $this->assertEquals('craftcms.com?p=home#newFragment', (string)$url);
  }

  public function testMailTo() {
    $url = new Url('mailto:craft-linkfield@craft.com');
    $this->assertEquals('mailto:craft-linkfield@craft.com', (string)$url);

    $url->setQuery(['subject' => 'Test']);
    $this->assertEquals('mailto:craft-linkfield@craft.com?subject=Test', (string)$url);

    $url->setQuery(['subject' => 'Mail subject <with> &special=chars']);
    $this->assertEquals('mailto:craft-linkfield@craft.com?subject=Mail%20subject%20%3Cwith%3E%20%26special%3Dchars', (string)$url);
  }

  /**
   * @throws Exception
   */
  public function testQuery() {
    $url = new Url('craftcms.com?testParamName=testValue1#fragment');
    $query = $url->getQuery();
    $this->assertEquals(1, count($query));
    $this->assertEquals('testValue1', $query['testParamName']);

    $url->setQuery(['newParamName' => 'newValue1']);
    $this->assertEquals('craftcms.com?newParamName=newValue1#fragment', (string)$url);
  }

  /**
   * @throws Exception
   */
  public function testToString() {
    foreach (self::URIS as $uri) {
      $url = new Url($uri);
      $this->assertEquals($uri, (string)$url);
    }
  }
}
