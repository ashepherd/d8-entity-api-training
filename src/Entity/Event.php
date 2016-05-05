<?php

namespace Drupal\event\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\UserInterface;

/**
 * Defines the event entity.
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   base_table = "event",
 *   data_table = "event_field_data",
 *   revision_table = "event_revision",
 *   revision_data_table = "event_field_revision",
 *   translatable = true,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "title",
 *   },
 *   bundle_entity_type = "event_type",
 *   bundle_label = @Translation("Type"),
 *   handlers = {
 *     "access" = "Drupal\event\Entity\EventAccessControlHandler",
 *     "view_builder" = "Drupal\event\Entity\EventViewBuilder",
 *     "list_builder" = "Drupal\event\Entity\EventListBuilder",
 *     "form" = {
 *       "add" = "Drupal\event\Form\EventAddForm",
 *       "edit" = "Drupal\event\Form\EventEditForm",
 *       "default" = "Drupal\event\Form\EventEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html_default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "html_collection" = "Drupal\event\Routing\EventCollectionHtmlRouteProvider",
 *       "html_revision" = "Drupal\event\Routing\EventRevisionHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/events/{event}",
 *     "revision" = "/events/{event}/revisions/{event_revision}",
 *     "add-form" = "/admin/content/events/add/{event_type}",
 *     "edit-form" = "/admin/content/events/manage/{event}/edit",
 *     "delete-form" = "/admin/content/events/manage/{event}/delete",
 *     "add-page" = "/admin/content/events/add",
 *     "collection" = "/admin/content/events",
 *   },
 *   field_ui_base_route = "entity.event_type.edit_form",
 *   admin_permission = "administer events",
 * )
 */
class Event extends RevisionableContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setType(EventTypeInterface $type) {
    return $this->set('type', $type);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    return $this->set('title', $title);
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    // The interface dictates that we return a datetime object in all cases, so
    // in case one is not yet available we return one corresponding to the
    // beginning of the Unix epoch so that it is probably clear to the consumer
    // that something has gone wrong.
    return $this->get('date')->date ?: new \DateTime('@0');
  }

  /**
   * {@inheritdoc}
   */
  public function setDate(\DateTimeInterface $date) {
    // We need to set the date in the specific format that is expected by Drupal
    // date fields without time information.
    $this->set('date', $date->format(DATETIME_DATE_STORAGE_FORMAT));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->processed ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($text, $format) {
    return $this->set('description', [
      'value' => $text,
      'format' => $format,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttendees() {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $attendees_item_list */
    $attendees_item_list = $this->get('attendees');
    return $attendees_item_list->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function addAttendee(UserInterface $attendee) {
    // Check if the attendee is already registered.
    $attendees_item_list = $this->get('attendees');
    foreach ($attendees_item_list as $attendee_item) {
      if ($attendee_item->target_id === $attendee->id()) {
        return $this;
      }
    }

    $attendees_item_list->appendItem($attendee);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAttendee(UserInterface $attendee) {
    $attendees_item_list = $this->get('attendees');
    foreach ($attendees_item_list as $delta => $attendee_item) {
      if ($attendee_item->target_id === $attendee->id()) {
        $attendees_item_list->removeItem($delta);
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('owner', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this->set('owner', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['revision_user']->setDefaultValueCallback(static::class . '::getDefaultOwnerIds');

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->addPropertyConstraints('value', ['FutureDate' => []])
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['attendees'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Attendees'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => 15,
      ])
      ->setDisplayOptions('view', [
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('Path'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultOwnerIds');

    return $fields;
  }

  /**
   * Returns the default value for the owner field.
   *
   * It always returns a single value which is the current user's ID.
   *
   * @see \Drupal\event\Entity\Event::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultOwnerIds() {
    return [\Drupal::currentUser()->id()];
  }

}
