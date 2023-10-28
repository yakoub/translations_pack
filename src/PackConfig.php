<?php
namespace Drupal\translations_pack;

use Drupal\language\Entity\ContentLanguageSettings;

class PackConfig {

  public static function bundleEnabled($entity_type_id, $bundle) {
    $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    if (!$config) {
      return FALSE;
    }
    return $config->getThirdPartySetting('translations_pack', 'pack_enabled');
  }

  const PARTIAL = 1;
  const DISABLED = 0;
  const ENABLED = 2;

  public static function typeStatus($entity_type_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('language_content_settings');
    $ids = $storage->getQuery()
      ->condition('target_entity_type_id', $entity_type_id)
      ->execute();
    if (!$ids) {
      return self::DISABLED;
    }
    $settings = $storage->loadMultiple($ids);
    $partial = FALSE;
    $all = TRUE;
    foreach ($settings as $setting) {
      $enabled = $setting->getThirdPartySetting('translations_pack', 'pack_enabled');
      $partial = $partial || $enabled;
      $all = $all && $enabled;
    }
    if ($all) {
      return self::ENABLED;
    }
    return $partial ? self::PARTIAL : self::DISABLED;
  }
}
