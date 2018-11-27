<?php

namespace typedlinkfield\utilities;

/**
 * Class Url
 */
class Url
{
  /**
   * @var array
   */
  private $parts;


  /**
   * Url constructor.
   * @param string $url
   */
  public function __construct($url) {
    $this->parts = parse_url($url);
  }

  /**
   * @return string
   */
  public function __toString() {
    $parts    = $this->parts;
    $scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host     = isset($parts['host']) ? $parts['host'] : '';
    $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
    $user     = isset($parts['user']) ? $parts['user'] : '';
    $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parts['path']) ? $parts['path'] : '';
    $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    return "$scheme$user$pass$host$port$path$query$fragment";
  }

  /**
   * @return string|null
   */
  public function getFragment() {
    return isset($this->parts['fragment']) ? (string)$this->parts['fragment'] : null;
  }

  /**
   * @return array
   */
  public function getQuery() {
    if (!isset($this->parts['query'])) {
      return array();
    }

    $result = array();
    foreach (explode('&', $this->parts['query']) as $param) {
      $parts = explode('=', $param, 2);
      if (count($parts) !== 2) continue;
      
      list($key, $value) = $parts;
      $result[$key] = urldecode($value);
    }

    return $result;
  }

  /**
   * @param string|null $fragment
   */
  public function setFragment($fragment) {
    if (empty($fragment)) {
      unset($this->parts['fragment']);
    } else {
      $this->parts['fragment'] = $fragment;
    }
  }

  /**
   * @param array $query
   */
  public function setQuery(array $query) {
    if (count($query) === 0) {
      unset($this->parts['query']);
    } else {
      $parts = array();
      foreach ($query as $key => $value) {
        $parts[] = $key . '=' . urlencode($value);
      }

      $this->parts['query'] = implode('&', $parts);
    }
  }
}
