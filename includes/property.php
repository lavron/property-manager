<?php
class PM_Property extends PM_Data
{
  protected $data = array(
    'name'               => '',
    'slug'               => '',
    'date_created'       => null,
    'date_modified'      => null,
    'status'             => false,
    'featured'           => false,
    'catalog_visibility' => 'visible',

    'description'        => '',
    'short_description'  => '',
    'price'              => '',
    'price_sqm'          => '',

    'attributes'         => array(),

    'area'                  => '',

    'address'             => '',
    'lat'              => '',
    'lng'             => '',

    'type_ids'       => array(),
    'deal_type_ids'  => array(),

    'image_id'           => '',
    'gallery_image_ids'  => array(),
  );

  /**
   * Get the property if ID is passed, otherwise the property is new and empty.
   * This class should NOT be instantiated, but the wc_get_property() function
   * should be used. It is possible, but the wc_get_property() is preferred.
   *
   * @param int|WP_Property|object $property Property to init.
   */

  public function __construct($property = 0)
  {
    // parent::__construct( $property );
    if (is_numeric($property) && $property > 0) {
      $this->set_id($property);
    } elseif ($property instanceof self) {
      $this->set_id(absint($property->get_id()));
    } elseif (!empty($property->ID)) {
      $this->set_id(absint($property->ID));
    }
  }

 
}
