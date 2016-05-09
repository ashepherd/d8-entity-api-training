<?php

namespace Drupal\event_devel\Controller;

use Drupal\event\Entity\Event;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a test controller.
 */
class TestController implements ContainerInjectionInterface {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TestController object.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entity_definition_update_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.definition_update_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides an empty test controller to easily execute arbitrary code.
   *
   * This is exposed at the '/test' path on your site.
   *
   * If Drush is available you can also run arbitrary code in the context of a
   * bootstrapped Drupal site with the "drush php-eval", "drush php-script" or
   * "drush core-cli" commands.
   *
   * @return array
   *   A renderable array that contains instruction text for this controller.
   *
   * @see event_devel.routing.yml
   */
  public function evaluateTestCode() {

    // Place any test code here.

    /*
    $event = Event::create();
    $event->save();
    drupal_set_message('Saved Event: ' . $event->id());
    $existing_event = Event::load(1);
    drupal_set_message($existing_event->uuid());
    drupal_set_message($existing_event->id());
    */

    /* Set some fields.
    $event = Event::load(1);
    // Set the Title.
    $event->set('title', 'DrupalCon New Orleans');

    // Set the date.
    $date = new \DateTime();
    $event->set('date', $date->format(DATETIME_DATE_STORAGE_FORMAT));
    // Also works like....
    //$event->set('date', '2016-05-09');

    // Set the Description.
    $event->set('description', [
      'value' => '<strong>Lorem ipsum</strong>',
      'format' => 'basic_html',
    ]);
    $event->save();
    */

    /*
    $event = Event::load(1);
    // From "label" = "title",
    $label = $event->label();
    drupal_set_message('Label: ' . $label);

    $title = $event->get('title')->value;
    $date = $event->get('date')->value;
    drupal_set_message(t('@title on @date', array('@title' => $title, '@date' => $date)));
    $date_obj = $event->get('date')->date;
    $formatted_date =$date_obj->format('m/d/Y');
    drupal_set_message(t('@title on @date', array('@title' => $title, '@date' => $formatted_date)));
    $desc_format = $event->get('description')->format;
    $desc_value = $event->get('description')->value;
    $html = $event->get('description')->processed;
    drupal_set_message(t('Value: @desc', array('@desc' => $desc_value)));
    drupal_set_message(t('Format: @desc', array('@desc' => $desc_format)));
    drupal_set_message(t('HTML: @desc', array('@desc' => $html)));
    */

    /*
    $event = Event::load(1);
    $event->setTitle('DrupalCon Dublin');
    $date = new \DateTime();
    $event->setDate($date);
    $event->setDescription('<em>Lorem ipsum</em>', 'basic_html');
    $event->save();
    */

    /*
    $event = Event::load(1);
    $title = $event->getTitle();
    $date = $event->getDate();
    $date_value = $date->format('m/d/Y');
    $desc = $event->getDescription();
    drupal_set_message(t('Title: @title', array('@title' => $title)));
    drupal_set_message(t('Date: @date', array('@date' => $date_value)));
    drupal_set_message(t('Desc: @desc', array('@desc' => $desc)));
    */

    return ['#markup' => 'Any code placed in \\' . __METHOD__ . '() is executed on this page.'];
  }

  /**
   * Provides a test controller to update entity/field definitions.
   *
   * This is exposed at the '/update-entity-field-definitions' path on your
   * site.
   *
   * If Drush is available, this can be achieved by running
   * "drush entity-updates" (or "drush entup") instead.
   *
   * @return array
   *   A renderable array that contains a summary of the applied entity/field
   *   definitions.
   *
   * @see \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface::applyUpdates()
   */
  public function updateEntityFieldDefinitions() {
    $build = [];

    // This code mimics the code that displays the list of needed entity/field
    // definition updates on the status report at /admin/reports/status.
    /** @see system_requirements() */
    if ($change_summary = $this->entityDefinitionUpdateManager->getChangeSummary()) {
      foreach ($change_summary as $entity_type_id => $changes) {
        $build[] = [
          '#theme' => 'item_list',
          '#title' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
          '#items' => $changes,
        ];
      }

      // This line of code is the only one that is not related to the output of
      // this controller. It proves that the functionality to update the
      // entity/field definitions is given by Drupal core itself although no UI
      // exists for it at this point.
      $this->entityDefinitionUpdateManager->applyUpdates();

      drupal_set_message('The entity/field definition updates listed below have been applied successfully.');
    }
    else {
      $build[] = ['#markup' => 'No outstanding entity/field definition updates.'];
    }

    return $build;
  }

}
