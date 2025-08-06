<?php

namespace Drupal\shared_components\Interface;

/**
 * Interface para descubrir componentes.
 */
interface ComponentDiscoveryInterface {

  /**
   * Descubre componentes en un directorio.
   *
   * @param string $path
   *   Ruta al directorio de componentes.
   *
   * @return array
   *   Array de nombres de componentes encontrados.
   */
  public function discoverComponents(string $path): array;

}
