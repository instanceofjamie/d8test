<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\rest\resource\EntityResource.
 */

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents entities as resources.
 *
 * @Plugin(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   serialization_class = "Drupal\Core\Entity\Entity",
 *   derivative = "Drupal\rest\Plugin\Derivative\EntityDerivative"
 * )
 */
class EntityResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the loaded entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id) {
    $definition = $this->getDefinition();
    $entity = entity_load($definition['entity_type'], $id);
    if ($entity) {
      return new ResourceResponse($entity);
    }
    throw new NotFoundHttpException(t('Entity with ID @id not found', array('@id' => $id)));
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param mixed $id
   *   Ignored. A new entity is created with a new ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post($id, EntityInterface $entity) {
    // Verify that the deserialized entity is of the type that we expect to
    // prevent security issues.
    $definition = $this->getDefinition();
    if ($entity->entityType() != $definition['entity_type']) {
      throw new BadRequestHttpException();
    }
    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$entity->isNew()) {
      throw new BadRequestHttpException();
    }
    try {
      $entity->save();
      $url = url(strtr($this->plugin_id, ':', '/') . '/' . $entity->id(), array('absolute' => TRUE));
      // 201 Created responses have an empty body.
      return new ResourceResponse(NULL, 201, array('Location' => $url));
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Responds to entity DELETE requests.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete($id) {
    $definition = $this->getDefinition();
    $entity = entity_load($definition['entity_type'], $id);
    if ($entity) {
      try {
        $entity->delete();
        // Delete responses have an empty body.
        return new ResourceResponse(NULL, 204);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }
    }
    throw new NotFoundHttpException(t('Entity with ID @id not found', array('@id' => $id)));
  }
}
