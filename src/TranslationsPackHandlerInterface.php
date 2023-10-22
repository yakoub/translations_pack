<?php
namespace Drupal\translations_pack;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\PackConfig;

interface TranslationsPackHandlerInterface {

  public function alterCreateRoute(RouteCollection $collection);

  public function alterUpdateRoute(RouteCollection $collection);

  public function deriveLocalTasks(array &$derivatives, $base_plugin_definition);
}
