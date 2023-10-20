<?php
namespace Drupal\translations_pack;

use Drupal\language\Entity\ContentLanguageSettings;

class PackConfig {

  public static function enabled($entity_type_id, $bundle = '') {
    if ($bundle) {
      $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
      if (!$config) {
        return FALSE;
      }
      return $config->getThirdPartySetting('translations_pack', 'pack_enabled');
    }
    else {
      $storage = \Drupal::entityTypeManager()->getStorage('language_content_settings');
      $ids = $storage->getQuery()
        ->condition('target_entity_type_id', $entity_type_id)
        ->execute();
      if (!$ids) {
        return FALSE;
      }
      $settings = $storage->loadMultiple($ids);
      foreach ($settings as $setting) {
        if (!$setting->getThirdPartySetting('translations_pack', 'pack_enabled')) {
          return FALSE;
        }
      }
      return TRUE;
    }
  }

}
