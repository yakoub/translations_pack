<?php
namespace Drupal\translations_pack;

use Drupal\language\Entity\ContentLanguageSettings;

class PackConfig {

  public static function enabled($entity_type_id, $bundle) {
    $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    if (!$config) {
      return FALSE;
    }
    return $config->getThirdPartySetting('translations_pack', 'pack_enabled');
  }
}
