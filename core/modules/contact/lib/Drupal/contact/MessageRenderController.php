<?php

/**
 * @file
 * Contains Drupal\contact\MessageRenderController.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for contact messages.
 */
class MessageRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    parent::buildContent($entities, $view_mode, $langcode);

    foreach ($entities as $entity) {
      // Add the message extra field, if enabled.
      $bundle = $entity->bundle();
      $entity_view_mode = $entity->content['#view_mode'];
      $fields = field_extra_fields_get_display($this->entityType, $bundle, $entity_view_mode);
      if (!empty($entity->message) && !empty($fields['message']['visible'])) {
        $entity->content['message'] = array(
          '#type' => 'item',
          '#title' => t('Message'),
          '#markup' => check_plain($entity->message),
          '#weight' => $fields['message']['weight'],
        );
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::view().
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    if ($view_mode == 'mail') {
      // Convert field labels into headings.
      // @todo Improve drupal_html_to_text() to convert DIVs correctly.
      foreach (element_children($build) as $key) {
        if (isset($build[$key]['#label_display']) && $build[$key]['#label_display'] == 'above') {
          $build[$key] += array('#prefix' => '');
          $build[$key]['#prefix'] = $build[$key]['#title'] . ":\n";
          $build[$key]['#label_display'] = 'hidden';
        }
      }
      $build = array(
        '#markup' => drupal_html_to_text(drupal_render($build)),
      );
    }
    return $build;
  }

}
