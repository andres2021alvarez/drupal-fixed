<?php

namespace Drupal\lazy_iframe;

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
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The processed render element.
   */
  public static function processElement(array $element): array {
    $processor = \Drupal::service('lazy_iframe.processor');
    return $processor->processElement($element);
  }

  /**
   * Processes the content of an element.
   *
   * @param string $element
   *   The element content.
   *
   * @return array
   *   The processed content.
   */
  public static function processContent(string $element): array {
    $processor = \Drupal::service('lazy_iframe.processor');
    return $processor->processContent($element);
  }

}
