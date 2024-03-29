<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Defines a common interface for all entity objects.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 */
interface EntityInterface extends ComplexDataInterface, AccessibleInterface, TranslatableInterface {

  /**
   * Returns the entity identifier (the entity's machine name or numeric ID).
   *
   * @return
   *   The identifier of the entity, or NULL if the entity does not yet have
   *   an identifier.
   */
  public function id();

  /**
   * Returns the entity UUID (Universally Unique Identifier).
   *
   * The UUID is guaranteed to be unique and can be used to identify an entity
   * across multiple systems.
   *
   * @return string
   *   The UUID of the entity, or NULL if the entity does not have one.
   */
  public function uuid();

  /**
   * Returns whether the entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see Drupal\Core\Entity\EntityInterface::enforceIsNew()
   */
  public function isNew();

  /**
   * Returns whether a new revision should be created on save.
   *
   * @return bool
   *   TRUE if a new revision should be created.
   *
   * @see Drupal\Core\Entity\EntityInterface::setNewRevision()
   */
  public function isNewRevision();

  /**
   * Enforces an entity to be saved as a new revision.
   *
   * @param bool $value
   *   (optional) Whether a new revision should be saved.
   *
   * @see Drupal\Core\Entity\EntityInterface::isNewRevision()
   */
  public function setNewRevision($value = TRUE);

  /**
   * Enforces an entity to be new.
   *
   * Allows migrations to create entities with pre-defined IDs by forcing the
   * entity to be new before saving.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be new. Defaults to
   *   TRUE.
   *
   * @see Drupal\Core\Entity\EntityInterface::isNew()
   */
  public function enforceIsNew($value = TRUE);

  /**
   * Returns the type of the entity.
   *
   * @return
   *   The type of the entity.
   */
  public function entityType();

  /**
   * Returns the bundle of the entity.
   *
   * @return
   *   The bundle of the entity. Defaults to the entity type if the entity type
   *   does not make use of different bundles.
   */
  public function bundle();

  /**
   * Returns the label of the entity.
   *
   * @param $langcode
   *   (optional) The language code of the language that should be used for
   *   getting the label. If set to NULL, the entity's default language is
   *   used.
   *
   * @return
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label($langcode = NULL);

  /**
   * Returns the URI elements of the entity.
   *
   * @return
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url(). NULL if the entity
   *   has no URI of its own.
   */
  public function uri();

  /**
   * Saves an entity permanently.
   *
   * @return
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save();

  /**
   * Deletes an entity permanently.
   *
   * @throws Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete();

  /**
   * Creates a duplicate of the entity.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   A clone of the current entity with all identifiers unset, so saving
   *   it inserts a new entity into the storage system.
   */
  public function createDuplicate();

  /**
   * Returns the info of the type of the entity.
   *
   * @see entity_get_info()
   */
  public function entityInfo();

  /**
   * Returns the revision identifier of the entity.
   *
   * @return
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

  /**
   * Checks if this entity is the default revision.
   *
   * @param bool $new_value
   *   (optional) A Boolean to (re)set the isDefaultRevision flag.
   *
   * @return bool
   *   TRUE if the entity is the default revision, FALSE otherwise. If
   *   $new_value was passed, the previous value is returned.
   */
  public function isDefaultRevision($new_value = NULL);

  /**
   * Retrieves the exportable properties of the entity.
   *
   * @return array
   *   An array of exportable properties and their values.
   */
  public function getExportProperties();

}
