<?php

abstract class PM_Data
{
  protected $id = 0;
  protected $data = array();

  public function set_id($id)
  {
    $this->id = absint($id);
  }
  protected function get_prop($prop, $context = 'view')
  {
    $value = null;
    if (array_key_exists($prop, $this->data)) {
      $value = array_key_exists($prop, $this->changes) ? $this->changes[$prop] : $this->data[$prop];
    }
    return $value;
  }

  public function get_id()
  {
    return $this->id;
  }
  public function get_slug($context = 'view')
  {
    return $this->get_prop('slug', $context);
  }
  public function get_name($context = 'view')
  {
    return $this->get_prop('name', $context);
  }

  public function get_type()
  {
    return isset($this->product_type) ? $this->product_type : 'property';
  }
  public function get_status($context = 'view')
  {
    return $this->get_prop('status', $context);
  }
  public function get_featured($context = 'view')
  {
    return $this->get_prop('featured', $context);
  }
  public function get_catalog_visibility($context = 'view')
  {
    return $this->get_prop('catalog_visibility', $context);
  }
  public function get_description($context = 'view')
  {
    return $this->get_prop('description', $context);
  }
  public function get_short_description($context = 'view')
  {
    return $this->get_prop('short_description', $context);
  }
  public function get_price($context = 'view')
  {
    return $this->get_prop('price', $context);
  }
  public function get_price_sqm($context = 'view')
  {
    return $this->get_prop('price_sqm', $context);
  }
  public function get_area($context = 'view')
  {
    return $this->get_prop('area', $context);
  }
  public function get_parent_id($context = 'view')
  {
    return $this->get_prop('parent_id', $context);
  }
  public function get_category_ids($context = 'view')
  {
    return $this->get_prop('category_ids', $context);
  }
  public function get_gallery_image_ids($context = 'view')
  {
    return $this->get_prop('gallery_image_ids', $context);
  }
  public function get_image_id($context = 'view')
  {
    return $this->get_prop('image_id', $context);
  }
}
