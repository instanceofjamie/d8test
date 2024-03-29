<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Language\Language;
use IteratorAggregate;

/**
 * Defines a base entity class.
 *
 * Default implementation of EntityInterface.
 *
 * This class can be used as-is by simple entity types. Entity types requiring
 * special handling can extend the class.
 */
class Entity implements IteratorAggregate, EntityInterface {

  /**
   * The language code of the entity's default language.
   *
   * @var string
   */
  public $langcode = LANGUAGE_NOT_SPECIFIED;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Boolean indicating whether the entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * Boolean indicating whether a new revision should be created on save.
   *
   * @var bool
   */
  protected $newRevision = FALSE;

  /**
   * Indicates whether this is the default revision.
   *
   * @var bool
   */
  protected $isDefaultRevision = TRUE;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param string $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type) {
    $this->entityType = $entity_type;
    // Set initial values.
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Implements EntityInterface::id().
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * Implements EntityInterface::uuid().
   */
  public function uuid() {
    return isset($this->uuid) ? $this->uuid : NULL;
  }

  /**
   * Implements EntityInterface::isNew().
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
  }

  /**
   * Implements EntityInterface::isNewRevision().
   */
  public function isNewRevision() {
    $info = $this->entityInfo();
    return $this->newRevision || (!empty($info['entity_keys']['revision']) && !$this->getRevisionId());
  }

  /**
   * Implements EntityInterface::enforceIsNew().
   */
  public function enforceIsNew($value = TRUE) {
    $this->enforceIsNew = $value;
  }

  /**
   * Implements EntityInterface::setNewRevision().
   */
  public function setNewRevision($value = TRUE) {
    $this->newRevision = $value;
  }

  /**
   * Implements EntityInterface::entityType().
   */
  public function entityType() {
    return $this->entityType;
  }

  /**
   * Implements EntityInterface::bundle().
   */
  public function bundle() {
    return $this->entityType;
  }

  /**
   * Implements EntityInterface::label().
   */
  public function label($langcode = NULL) {
    $label = NULL;
    $entity_info = $this->entityInfo();
    if (isset($entity_info['label_callback']) && function_exists($entity_info['label_callback'])) {
      $label = $entity_info['label_callback']($this->entityType, $this, $langcode);
    }
    elseif (!empty($entity_info['entity_keys']['label']) && isset($this->{$entity_info['entity_keys']['label']})) {
      $label = $this->{$entity_info['entity_keys']['label']};
    }
    return $label;
  }

  /**
   * Implements EntityInterface::uri().
   */
  public function uri() {
    $bundle = $this->bundle();
    // A bundle-specific callback takes precedence over the generic one for the
    // entity type.
    $entity_info = $this->entityInfo();
    if (isset($entity_info['bundles'][$bundle]['uri_callback'])) {
      $uri_callback = $entity_info['bundles'][$bundle]['uri_callback'];
    }
    elseif (isset($entity_info['uri_callback'])) {
      $uri_callback = $entity_info['uri_callback'];
    }
    else {
      return NULL;
    }

    // Invoke the callback to get the URI. If there is no callback, return NULL.
    if (isset($uri_callback) && function_exists($uri_callback)) {
      $uri = $uri_callback($this);
      // Pass the entity data to url() so that alter functions do not need to
      // look up this entity again.
      $uri['options']['entity_type'] = $this->entityType;
      $uri['options']['entity'] = $this;
      return $uri;
    }
  }

  /**
   * Implements EntityInterface::get().
   */
  public function get($property_name, $langcode = NULL) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Implements ComplexDataInterface::set().
   */
  public function set($property_name, $value) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    $this->{$property_name} = $value;
  }

  /**
   * Implements ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements ComplexDataInterface::getIterator().
   */
  public function getIterator() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements AccessibleInterface::access().
   */
  public function access($operation = 'view', \Drupal\user\Plugin\Core\Entity\User $account = NULL) {
    $method = $operation . 'Access';
    return entity_access_controller($this->entityType)->$method($this, LANGUAGE_DEFAULT, $account);
  }

  /**
   * Implements TranslatableInterface::language().
   */
  public function language() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    return !empty($this->langcode) ? language_load($this->langcode) : new Language(array('langcode' => LANGUAGE_NOT_SPECIFIED));
  }

  /**
   * Implements TranslatableInterface::getTranslation().
   */
  public function getTranslation($langcode, $strict = TRUE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Returns the languages the entity is translated to.
   *
   * @todo: Remove once all entity types implement the entity field API.
   *   This is deprecated by
   *   Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages().
   */
  public function translations() {
    return $this->getTranslationLanguages(FALSE);
  }

  /**
   * Implements TranslatableInterface::getTranslationLanguages().
   */
  public function getTranslationLanguages($include_default = TRUE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    $default_language = $this->language();
    $languages = array($default_language->langcode => $default_language);
    $entity_info = $this->entityInfo();

    if ($entity_info['fieldable']) {
      // Go through translatable properties and determine all languages for
      // which translated values are available.
      foreach (field_info_instances($this->entityType, $this->bundle()) as $field_name => $instance) {
        $field = field_info_field($field_name);
        if (field_is_translatable($this->entityType, $field) && isset($this->$field_name)) {
          foreach (array_filter($this->$field_name) as $langcode => $value)  {
            $languages[$langcode] = TRUE;
          }
        }
      }
      $languages = array_intersect_key(language_list(LANGUAGE_ALL), $languages);
    }

    if (empty($include_default)) {
      unset($languages[$default_language->langcode]);
    }

    return $languages;
  }

  /**
   * Implements EntityInterface::save().
   */
  public function save() {
    return entity_get_controller($this->entityType)->save($this);
  }

  /**
   * Implements EntityInterface::delete().
   */
  public function delete() {
    if (!$this->isNew()) {
      entity_get_controller($this->entityType)->delete(array($this->id() => $this));
    }
  }

  /**
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity_keys']['id']} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity_keys']['uuid'])) {
      $uuid = new Uuid();
      $duplicate->{$entity_info['entity_keys']['uuid']} = $uuid->generate();
    }
    return $duplicate;
  }

  /**
   * Implements EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return entity_get_info($this->entityType);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::isDefaultRevision().
   */
  public function isDefaultRevision($new_value = NULL) {
    $return = $this->isDefaultRevision;
    if (isset($new_value)) {
      $this->isDefaultRevision = (bool) $new_value;
    }
    return $return;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getExportProperties().
   */
  public function getExportProperties() {
    return array();
  }

}
