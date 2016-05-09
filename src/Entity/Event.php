<?php

namespace Drupal\event\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
/**
 * Provides an Event Entity.
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   base_table = "event",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/event/{event}",
 *     "add-form" = "/admin/content/events/add",
 *     "edit-form" = "/admin/content/events/manage/{event}/edit",
 *     "delete-form" = "/admin/content/events/manage/{event}/delete",
 *   },
 *   admin_permission = "administer events",
 * )
 *
 * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!ContentEntityBase.php/class/ContentEntityBase
 * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!EntityViewBuilder.php/class/EntityViewBuilder
 */
class Event extends ContentEntityBase {

  /**
   * Building a API for ourselves. Similar to osprey_utils.entity.inc!!
   */
  public function setTitle($title) {
    $this->set('title', $title);
  }
  public function setDate(\DateTimeInterface $date) {
    $this->set('date', $date->format(DATETIME_DATE_STORAGE_FORMAT));
  }
  public function setDescription($description, $format) {
    $this->set('description', [
      'value' => $description,
      'format' => $format,
    ]);
  }

  public function getTitle() {
    return $this->label();
  }
  /**
   * @return \DateTimeInterface
   */
  public function getDate() {
    return $this->get('date')->date;
  }

  public function getDescription() {
    return $this->get('description')->processed;
  }

  /**
   * {@inheritdoc}
   *
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!EntityTypeInterface.php/interface/EntityTypeInterface
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Field!BaseFieldDefinition.php/class/BaseFieldDefinition
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21TypedData%21FieldItemDataDefinition.php/class/FieldItemDataDefinition
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Creates an FieldItemDataDefinition object.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'weight' => -5,
        ]);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Date'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      // D7: Manage Display.
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'settings' => [
          'format_type' => 'html_date',
        ],
        'weight' => 0,
        ])
      ->setDisplayOptions('form', [
        'weight' => 0,
        ]);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 5,
        ])
      ->setDisplayOptions('form', [
        'weight' => 5,
        ]);

    $fields['attendees'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Attendees'))
      ->setSetting('target_type', 'user')
      // @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Field!FieldStorageDefinitionInterface.php/interface/FieldStorageDefinitionInterface
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'weight' => 10,
        ])
      ->setDisplayOptions('form', [
        'weight' => 10,
        ]);

    $fields['alias'] = BaseFieldDefinition::create('path')
      ->setLabel(new TranslatableMarkup('Path Alias'))
      ->setDisplayOptions('form', [
        'weight' => 10,
      ]);

    return $fields;
  }
}
