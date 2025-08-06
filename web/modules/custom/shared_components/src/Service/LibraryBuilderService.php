<?php

namespace Drupal\shared_components\Service;

use Drupal\shared_components\Interface\LibraryBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Construye definiciones de librerías para componentes.
 */
class LibraryBuilderService implements LibraryBuilderInterface {

  private ConfigFactoryInterface $configFactory;
  private LoggerChannelInterface $logger;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  public function buildLibrary(string $component_name, string $component_path): array {
    $library = [];
    $config = $this->configFactory->get('shared_components.settings');

    try {
      // CSS
      $css_extensions = $config->get('css_extensions') ?? ['css'];
      foreach ($css_extensions as $ext) {
        $css_file = $component_path . '/' . $component_name . '.' . $ext;
        if ($this->fileExistsAndReadable($css_file)) {
          $library['css']['theme'][$css_file] = [
            'weight' => $config->get('css_weight') ?? 0,
            'preprocess' => $config->get('css_preprocess') ?? TRUE,
          ];
          break;
        }
      }

      // JavaScript
      $js_extensions = $config->get('js_extensions') ?? ['js'];
      foreach ($js_extensions as $ext) {
        $js_file = $component_path . '/' . $component_name . '.' . $ext;
        if ($this->fileExistsAndReadable($js_file)) {
          $library['js'][$js_file] = [
            'weight' => $config->get('js_weight') ?? 0,
            'preprocess' => $config->get('js_preprocess') ?? TRUE,
          ];
          break;
        }
      }

      // Dependencies (si existe un archivo .info.yml)
      $info_file = DRUPAL_ROOT . '/' . $component_path . '/' . $component_name . '.info.yml';
      if ($this->fileExistsAndReadable($info_file)) {
        $info = $this->parseInfoFile($info_file);
        if (!empty($info['dependencies'])) {
          $library['dependencies'] = $info['dependencies'];
        }
      }

      if (!empty($library)) {
        $this->logger->debug('Built library for component: @component', [
          '@component' => $component_name
        ]);
      }

    } catch (\Exception $e) {
      $this->logger->error('Error building library for @component: @error', [
        '@component' => $component_name,
        '@error' => $e->getMessage()
      ]);
    }

    return $library;
  }

  /**
   * Verifica si un archivo existe y es legible de manera segura.
   */
  private function fileExistsAndReadable(string $file_path): bool {
    $full_path = DRUPAL_ROOT . '/' . $file_path;
    return file_exists($full_path) && is_readable($full_path);
  }

  /**
   * Parsea archivo .info.yml de componente.
   */
  private function parseInfoFile(string $info_file): array {
    try {
      $content = file_get_contents($info_file);
      if ($content === FALSE) {
        return [];
      }

      return \Drupal::service('serialization.yaml')->decode($content) ?? [];
    } catch (\Exception $e) {
      $this->logger->warning('Cannot parse info file @file: @error', [
        '@file' => $info_file,
        '@error' => $e->getMessage()
      ]);
      return [];
    }
  }
}