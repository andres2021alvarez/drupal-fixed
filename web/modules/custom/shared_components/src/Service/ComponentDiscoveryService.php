<?php

namespace Drupal\shared_components\Service;

use Drupal\shared_components\Interface\ComponentDiscoveryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Servicio para descubrir componentes de manera segura y eficiente.
 */
class ComponentDiscoveryService implements ComponentDiscoveryInterface {

  private FileSystemInterface $fileSystem;
  private LoggerChannelInterface $logger;
  private ConfigFactoryInterface $configFactory;

  public function __construct(
    FileSystemInterface $file_system,
    LoggerChannelInterface $logger,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   *
   */
  public function discoverComponents(string $path): array {
    try {
      if (!$this->validatePath($path)) {
        return [];
      }

      $components = [];
      $max_components = $this->getMaxComponents();
      $handle = opendir($path);

      if ($handle === FALSE) {
        $this->logger->warning('Cannot open components directory: @path', ['@path' => $path]);
        return [];
      }

      $count = 0;
      while (($item = readdir($handle)) !== FALSE && $count < $max_components) {
        if ($this->isValidComponent($path, $item)) {
          $components[] = $item;
          $count++;
        }
      }

      closedir($handle);

      $this->logger->info('Discovered @count components in @path', [
        '@count' => count($components),
        '@path' => $path,
      ]);

      return $components;

    }
    catch (\Exception $e) {
      $this->logger->error('Error discovering components: @error', [
        '@error' => $e->getMessage(),
        '@path' => $path,
      ]);
      return [];
    }
  }

  /**
   * Valida que el path sea seguro y accesible.
   */
  private function validatePath(string $path): bool {

    if (!is_dir($path)) {
      $this->logger->notice('Components directory does not exist: @path', ['@path' => $path]);
      return FALSE;
    }

    if (!is_readable($path)) {
      $this->logger->error('Components directory is not readable: @path', ['@path' => $path]);
      return FALSE;
    }

    $real_path = realpath($path);
    $drupal_root = realpath(DRUPAL_ROOT);

    if ($real_path === FALSE || strpos($real_path, $drupal_root) !== 0) {
      $this->logger->error('Invalid or unsafe components path: @path', ['@path' => $path]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Verifica si un item es un componente válido.
   */
  private function isValidComponent(string $base_path, string $item): bool {

    if ($item === '.' || $item === '..') {
      return FALSE;
    }

    $full_path = $base_path . '/' . $item;
    if (!is_dir($full_path)) {
      return FALSE;
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $item)) {
      $this->logger->warning('Invalid component name: @name', ['@name' => $item]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Obtiene el número máximo de componentes a procesar.
   */
  private function getMaxComponents(): int {
    return $this->configFactory
      ->get('shared_components.settings')
      ->get('max_components') ?? 200;
  }

}
