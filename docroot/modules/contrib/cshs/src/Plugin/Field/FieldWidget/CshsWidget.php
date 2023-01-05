<?php

namespace Drupal\cshs\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\cshs\Element\CshsElement;
use Drupal\cshs\IsApplicable;
use Drupal\cshs\CshsOptionsFromHelper;

/**
 * Defines the field widget.
 *
 * @FieldWidget(
 *   id = "cshs",
 *   label = @Translation("Client-side hierarchical select"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class CshsWidget extends WidgetBase {

  use IsApplicable {
    isApplicable as helperIsApplicable;
  }
  use CshsOptionsFromHelper {
    defaultSettings as helperDefaultSettings;
    settingsSummary as helperSettingsSummary;
    settingsForm as helperSettingsForm;
    formElement as helperFormElement;
  }

  /**
   * The field widget settings across all bundles of the entity type.
   *
   * @var array[]|null
   */
  protected ?array $perDisplaySettings = NULL;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if (static::helperIsApplicable($field_definition)) {
      /* @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection */
      if (!empty($field_definition->getSettings()['handler_settings']['target_bundles'] ?? [])) {
        return TRUE;
      }

      \Drupal::messenger()->addWarning(\t('The client-side hierarchical select widget cannot be used for the %field because it is not using the default entity reference selection handler or has no taxonomy vocabularies selected. If you are aimed to use the CSHS widget for the %label field, please configure it accordingly or ignore this warning as its purpose to let site builders know why the CSHS widget is not an option for this taxonomy reference field.', [
        '%label' => $field_definition->getLabel(),
        // Some fields have no IDs and only names.
        '%field' => \str_replace('.', ' -> ', \method_exists($field_definition, 'id') ? $field_definition->id() : $field_definition->getName()),
      ]));
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return static::helperDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    $this->settings = parent::getSettings();

    if (empty($this->settings['save_lineage'])) {
      // If the `save_lineage` is enabled for the entity type it
      // can no longer be disabled unless the storage is empty.
      foreach ($this->getPerDisplaySettings() as $display_settings) {
        if (!empty($display_settings['save_lineage'])) {
          $this->settings['save_lineage'] = $display_settings['save_lineage'];
          break;
        }
      }
    }

    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key): mixed {
    return $this->getSettings()[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return $this->helperSettingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return $this->helperSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['target_id'] = \array_merge($element, $this->helperFormElement(), [
      '#label' => $this->fieldDefinition->getLabel(),
    ]);

    if ($items->isEmpty()) {
      return $element;
    }

    if ($this->handlesMultipleValues()) {
      $element['target_id']['#default_value'] = \array_map(
        static fn (array $item): int => $item['target_id'],
        $items->getValue(),
      );
    }
    else {
      $element['target_id']['#default_value'] = $items->get($delta)->target_id ?? NULL;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    // This is the case when `$this->handlesMultipleValues()` returns `TRUE`.
    if (!empty($values['target_id']) && \is_array($values['target_id'])) {
      $list = [];

      foreach ($values['target_id'] as $id) {
        $list[] = [
          'target_id' => $id,
        ];
      }

      return $list;
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulariesIds(): array {
    return $this->fieldDefinition->getSettings()['handler_settings']['target_bundles'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  protected function handlesMultipleValues(): bool {
    return (bool) $this->getSetting('save_lineage');
  }

  /**
   * Returns the per display widget settings for the entity type.
   *
   * @return array[]
   *   The per display widget settings.
   */
  protected function getPerDisplaySettings(): array {
    if ($this->perDisplaySettings === NULL) {
      $this->perDisplaySettings = [];

      $field_name = $this->fieldDefinition->getName();
      // Load all form displays for the entity type the field is attached to.
      $form_displays = $this
        ->getStorage('entity_form_display')
        ->loadByProperties([
          'targetEntityType' => $this->fieldDefinition->getTargetEntityTypeId(),
        ]);

      foreach ($form_displays as $form_display) {
        \assert($form_display instanceof EntityFormDisplay);
        if (($widget = $form_display->getComponent($field_name)) && CshsElement::ID === ($widget['type'] ?? '')) {
          $this->perDisplaySettings[] = $widget['settings'] ?? [];
        }
      }
    }

    return $this->perDisplaySettings;
  }

}
