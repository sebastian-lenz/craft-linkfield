<?php

namespace lenz\linkfield\models;

/**
 * Class Url
 */
class Url
{
  /**
   * @var array
   */
  private $_parts;

  /**
   * @var array
   */
  const GLUES = [
    ['scheme',   '',  '://'],
    ['auth',     '',  '@'],
    ['host',     '',  ''],
    ['port',     ':', ''],
    ['path',     '',  ''],
    ['query',    '?', ''],
    ['fragment', '#', ''],
  ];


  /**
   * Url constructor.
   * @param string $url
   */
  public function __construct(string $url) {
    $this->_parts = parse_url($url);
  }

  /**
   * @return string
   */
  public function __toString() {
    $result = [];
    $parts = $this->_parts + [
      'auth' => $this->getAuthentication(),
    ];

    foreach (self::GLUES as list($key, $prefix, $suffix)) {
      $value = isset($parts[$key]) ? $parts[$key] : '';
      if (!empty($value)) {
        array_push($result, $prefix, $value, $suffix);
      }
    }

    if (isset($parts['host']) && !isset($parts['scheme'])) {
      array_unshift($result, '//');
    }

    return implode('', $result);
  }

  /**
   * @return string
   */
  public function getAuthentication() {
    $parts = $this->_parts;

    return implode(':', array_filter([
      isset($parts['user']) ? $parts['user'] : '',
      isset($parts['pass']) ? $parts['pass'] : '',
    ]));
  }

  /**
   * @return string|null
   */
  public function getFragment() {
    return isset($this->_parts['fragment']) ? (string)$this->_parts['fragment'] : null;
  }

  /**
   * @return array
   */
  public function getQuery() {
    if (!isset($this->_parts['query'])) {
      return array();
    }

    $result = array();
    foreach (explode('&', $this->_parts['query']) as $param) {
      $parts = explode('=', $param, 2);
      if (count($parts) !== 2) {
        continue;
      }

      list($key, $value) = $parts;
      $result[$key] = urldecode($value);
    }

    return $result;
  }

  /**
   * @param string|null $fragment
   */
  public function setFragment(string $fragment = null) {
    if (empty($fragment)) {
      unset($this->_parts['fragment']);
    } else {
      $this->_parts['fragment'] = $fragment;
    }
  }

  /**
   * @param array $query
   */
  public function setQuery(array $query) {
    if (count($query) === 0) {
      unset($this->_parts['query']);
    } else {
      $parts = array();
      foreach ($query as $key => $value) {
        $parts[] = $key . '=' . urlencode($value);
      }

      $this->_parts['query'] = implode('&', $parts);
    }
  }
}
