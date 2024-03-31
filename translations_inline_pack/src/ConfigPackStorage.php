<?php
namespace Drupal\translations_inline_pack;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 */
class ConfigPackStorage extends ConfigEntityStorage {

  public function getEntityClass(?string $bundle = NULL) : string {
    return PackInlineFormDisplay::class;
  }

}
