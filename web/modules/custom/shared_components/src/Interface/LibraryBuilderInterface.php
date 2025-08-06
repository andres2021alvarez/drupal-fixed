<?php

namespace Drupal\shared_components\Interface;

/**
 * Interface para construir definiciones de librerías.
 */
interface LibraryBuilderInterface {

  /**
   * Construye la definición de librería para un componente.
   *
   * @param string $component_name
   *   Nombre del componente.
   * @param string $component_path
   *   Ruta relativa al componente.
   *
   * @return array
   *   Definición de librería de Drupal.
   */
  public function buildLibrary(string $component_name, string $component_path): array;

}
