<?php
namespace Drupal\translations_moderation_pack;
use Drupal\content_moderation\EntityOperations;
use Drupal\content_moderation\Entity\ContentModerationState as ContentModerationStateEntity;
use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\Core\Entity\EntityInterface;

class PackedEntityOperations extends EntityOperations {

  protected function updateOrCreateFromEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity_revision_id = $entity->getRevisionId();
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    $content_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($entity);
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('content_moderation_state');

    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id(),
        // Make sure that the moderation state entity has the same language code
        // as the moderated entity.
        'langcode' => $entity->language()->getId(),
      ]);
      $content_moderation_state->workflow->target_id = $workflow->id();
    }

    // Sync translations.
    if ($entity->getEntityType()->hasKey('langcode')) {
      $entity_langcode = $entity->language()->getId();
      if ($entity->isDefaultTranslation()) {
        $content_moderation_state->langcode = $entity_langcode;
      }
      else {
        if (!$content_moderation_state->hasTranslation($entity_langcode)) {
          $content_moderation_state->addTranslation($entity_langcode);
        }
        if ($content_moderation_state->language()->getId() !== $entity_langcode) {
          $content_moderation_state = $content_moderation_state->getTranslation($entity_langcode);
        }
      }
    }

    // If a new revision of the content has been created, add a new content
    // moderation state revision.
    if (!$content_moderation_state->isNew() && $content_moderation_state->content_entity_revision_id->value != $entity_revision_id) {
      $content_moderation_state = $storage->createRevision($content_moderation_state, $entity->isDefaultRevision());
    }

    // Create the ContentModerationState entity for the inserted entity.
    $moderation_state = $entity->moderation_state->value;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = $workflow->getTypePlugin()->getInitialState($entity)->id();
    }

    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    // custom pack translations
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      if ($content_moderation_state->hasTranslation($langcode)) {
        $state_translation = $content_moderation_state->getTranslation($langcode);
      }
      else {
        $state_translation = $content_moderation_state->addTranslation($langcode);
      }
      $state_translation->set('moderation_state', $translation->moderation_state->value);
    }
    //custom done
    ContentModerationStateEntity::updateOrCreateFromEntity($content_moderation_state);
  }
}
