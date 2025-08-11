<?php

namespace Drupal\lazy_iframe;

use Drupal\Core\Render\Element;

/**
 * Service for processing iframes to add lazy loading.
 */
class LazyIframeProcessor {

  /**
   * Process content to add lazy loading to iframes.
   *
   * @param string $content
   *   The HTML content to process.
   *
   * @return string
   *   The processed content with lazy loading attributes.
   */
  public function processContent(string $content): string {
    if (empty(trim($content))) {
      return $content;
    }

    try {
      return $this->processHtmlContent($content);
    }
    catch (\Exception $e) {
      \Drupal::logger('lazy_iframe')->error(
        'Error processing iframe content: @message',
        ['@message' => $e->getMessage()]
      );
      return $content;
    }
  }

  /**
   * Process render element to add lazy loading to iframes.
   *
   * @param array $element
   *   The render element to process.
   *
   * @return array
   *   The processed render element.
   */
  public function processElement(array $element): array {
    if (isset($element['#markup'])) {
        dump($element['#markup']);

      $element['#markup'] = $this->processContent($element['#markup']);
    }

    foreach (Element::children($element) as $key) {
      $element[$key] = $this->processElement($element[$key]);
    }

    return $element;
  }

  /**
   * Process HTML content using DOMDocument.
   *
   * @param string $content
   *   The HTML content to process.
   *
   * @return string
   *   The processed content.
   */
  protected function processHtmlContent(string $content): string {
    $previousUseErrors = libxml_use_internal_errors(TRUE);
    libxml_clear_errors();

    try {
      $doc = new \DOMDocument('1.0', 'UTF-8');

      // Load HTML with proper encoding handling.
      $success = $doc->loadHTML(
        '<?xml encoding="UTF-8"><div>' . $content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
      );

      if (!$success) {
        throw new \RuntimeException('Failed to parse HTML content');
      }

      $iframes = $doc->getElementsByTagName('iframe');

      foreach ($iframes as $iframe) {
        // Skip if already has loading attribute.
        if (!$iframe->hasAttribute('loading')) {
          $iframe->setAttribute('loading', 'lazy');
        }
      }

      // Extract processed content.
      $body = $doc->getElementsByTagName('div')->item(0);
      $processedContent = '';

      if ($body && $body->childNodes) {
        foreach ($body->childNodes as $child) {
          $processedContent .= $doc->saveHTML($child);
        }
      }

      return $processedContent;
    }
    finally {
      // Restore previous libxml error handling.
      libxml_use_internal_errors($previousUseErrors);
    }
  }

}