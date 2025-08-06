<?php
// src/Service/SharedComponentsService.php

namespace Drupal\shared_components\Service;

use Drupal\shared_components\Interface\ComponentDiscoveryInterface;
use Drupal\shared_components\Interface\LibraryBuilderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Servicio principal para manejar componentes compartidos.
 */
class SharedComponentsService {

  private ComponentDiscoveryInterface $componentDiscovery;
  private LibraryBuilderInterface $libraryBuilder;
  private CacheBackendInterface $cache;
  private ModuleHandlerInterface $moduleHandler;
  private LoggerChannelInterface $logger;
  private ConfigFactoryInterface $configFactory;

  public function __construct(
    ComponentDiscoveryInterface $component_discovery,
    LibraryBuilderInterface $library_builder,
    CacheBackendInterface $cache,
    ModuleHandlerInterface $module_handler,
    LoggerChannelInterface $logger,
    ConfigFactoryInterface $config_factory
  ) {
    $this->componentDiscovery = $component_discovery;
    $this->libraryBuilder = $library_builder;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * Construye todas las librerías de componentes con cache.
   */
  public function buildLibraries(): array {
    $cache_enabled = $this->configFactory->get('shared_components.settings')->get('cache_enabled') ?? TRUE;
    $cache_key = 'shared_components:libraries';

    if ($cache_enabled) {
      if ($cached = $this->cache->get($cache_key)) {
        $this->logger->debug('Loaded libraries from cache');
        return $cached->data;
      }
    }

    $start_time = microtime(TRUE);
    $libraries = $this->douildLibraries();
    $build_time = microtime(TRUE) - $start_time;

    if ($cache_enabled && !empty($libraries)) {
      $cache_ttl = $this->configFactory->get('shared_components.settings')->get('cache_ttl') ?? 3600;
      $this->cache->set($cache_key, $libraries, time() + $cache_ttl, [
        'shared_components',
        'library_info'
      ]);
    }

    $this->logger->info('Built @count component libraries in @time seconds', [
      '@count' => count($libraries),
      '@time' => round($build_time, 3)
    ]);

    return $libraries;
  }

  /**
   * Construye las librerías sin cache.
   */
  private function douildLibraries(): array {
    $libraries = [];

    try {
      $module_path = $this->moduleHandler->getModule('shared_components')->getPath();
      $component_path = DRUPAL_ROOT . '/' . $module_path . '/components';

      $components = $this->componentDiscovery->discoverComponents($component_path);

      foreach ($components as $component) {
        $relative_path = $module_path . '/components/' . $component;
        $library = $this->libraryBuilder->buildLibrary($component, $relative_path);

        if (!empty($library)) {
          $libraries[$component] = $library;
        }
      }

    } catch (\Exception $e) {
      $this->logger->error('Failed to build libraries: @error', [
        '@error' => $e->getMessage()
      ]);
    }

    return $libraries;
  }

  /**
   * Limpia el cache de librerías.
   */
  public function clearCache(): void {
    $this->cache->delete('shared_components:libraries');
    $this->logger->info('Cleared shared components cache');
  }
}