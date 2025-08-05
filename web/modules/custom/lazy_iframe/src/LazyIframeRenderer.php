<?php

namespace Drupal\lazy_iframe;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides trusted callbacks for lazy loading iframes.
 */
class LazyIframeRenderer implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processElement'];
  }

  /**
   * Pre-render callback to add lazy loading to iframes.
   */
  public static function processElement($element) {
    if (isset($element['#markup'])) {
      $element['#markup'] = static::processContent($element['#markup']);
    }

    foreach (Element::children($element) as $key) {
      $element[$key] = static::processElement($element[$key]);
    }

    return $element;
  }

  /**
   * Process content to add lazy loading to iframes.
   */
  public static function processContent($content) {
    return preg_replace_callback(
      '/<iframe\b([^>]*)>/i',
      function($matches) {
        if (strpos($matches[1], 'loading=') !== false) {
          return $matches[0];
        }

        return '<iframe ' . trim($matches[1]) . ' loading="lazy">';
      },
      $content
    );
  }

}