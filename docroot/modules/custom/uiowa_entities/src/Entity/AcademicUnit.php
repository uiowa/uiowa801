<?php

namespace Drupal\uiowa_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uiowa_entities\UnitInterface;

/**
 * Defines the academic unit entity type.
 *
 * @ConfigEntityType(
 *   id = "uiowa_academic_unit",
 *   label = @Translation("Non-Collegiate Academic Unit"),
 *   label_collection = @Translation("Non-Collegiate Academic Units"),
 *   label_singular = @Translation("non-collegiate academic unit"),
 *   label_plural = @Translation("non-collegiate academic units"),
 *   label_count = @PluralTranslation(
 *     singular = "@count non-collegiate academic unit",
 *     plural = "@count non-collegiate academic units",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\uiowa_entities\UnitListBuilder",
 *     "form" = {
 *       "add" = "Drupal\uiowa_entities\Form\AcademicUnitForm",
 *       "edit" = "Drupal\uiowa_entities\Form\AcademicUnitForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "unit.academic_unit",
 *   admin_permission = "administer uiowa_academic_unit",
 *   links = {
 *     "collection" = "/admin/structure/uiowa-acadmeic-unit",
 *     "add-form" = "/admin/structure/uiowa-academic-unit/add",
 *     "edit-form" = "/admin/structure/uiowa-academic-unit/{uiowa_academic_unit}",
 *     "delete-form" = "/admin/structure/uiowa-academic-unit/{uiowa_academic_unit}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "homepage"
 *   }
 * )
 */
class AcademicUnit extends ConfigEntityBase implements UnitInterface {

  /**
   * The academic unit ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The academic unit label.
   *
   * @var string
   */
  protected $label;

  /**
   * The academic unit status.
   *
   * @var bool
   */
  protected $status;

  /**
   * A link to the academic unit homepage.
   *
   * @var string
   */
  protected $homepage;

}
