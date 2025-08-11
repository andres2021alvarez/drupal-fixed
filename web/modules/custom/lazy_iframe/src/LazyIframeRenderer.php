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
  public static function processContent(string $content): string {

    libxml_use_internal_errors(TRUE);

    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $iframes = $doc->getElementsByTagName('iframe');

    foreach ($iframes as $iframe) {
      if (!$iframe->hasAttribute('loading')) {
        $iframe->setAttribute('loading', 'lazy');
      }
    }

    $body = $doc->getElementsByTagName('div')->item(0);
    $newHtml = '';
    foreach ($body->childNodes as $child) {
      $newHtml .= $doc->saveHTML($child);
    }

    return $newHtml;
  }

}
