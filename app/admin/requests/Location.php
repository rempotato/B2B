<?php

namespace Admin\Requests;

use System\Classes\FormRequest;

class Location extends FormRequest
{
    public function attributes()
    {
        return [
            'location_name' => lang('admin::lang.label_name'),
            'location_email' => lang('admin::lang.label_email'),
            'location_telephone' => lang('admin::lang.locations.label_telephone'),
            'location_address_1' => lang('admin::lang.locations.label_address_1'),
            'location_address_2' => lang('admin::lang.locations.label_address_2'),
            'location_city' => lang('admin::lang.locations.label_city'),
            'location_state' => lang('admin::lang.locations.label_state'),
            'location_postcode' => lang('admin::lang.locations.label_postcode'),
            'location_country_id' => lang('admin::lang.locations.label_country'),
            'options.auto_lat_lng' => lang('admin::lang.locations.label_auto_lat_lng'),
            'location_lat' => lang('admin::lang.locations.label_latitude'),
            'location_lng' => lang('admin::lang.locations.label_longitude'),
            'description' => lang('admin::lang.label_description'),
            'options.limit_orders' => lang('admin::lang.locations.label_limit_orders'),
            'options.limit_orders_count' => lang('admin::lang.locations.label_limit_orders_count'),
            'options.offer_delivery' => lang('admin::lang.locations.label_offer_delivery'),
            'options.offer_collection' => lang('admin::lang.locations.label_offer_collection'),
            'options.offer_reservation' => lang('admin::lang.locations.label_offer_collection'),
            'options.delivery_time_interval' => lang('admin::lang.locations.label_delivery_time_interval'),
            'options.collection_time_interval' => lang('admin::lang.locations.label_collection_time_interval'),
            'options.delivery_lead_time' => lang('admin::lang.locations.label_delivery_lead_time'),
            'options.collection_lead_time' => lang('admin::lang.locations.label_collection_lead_time'),
            'options.future_orders.enable_delivery' => lang('admin::lang.locations.label_future_delivery_order'),
            'options.future_orders.enable_collection' => lang('admin::lang.locations.label_future_collection_order'),
            'options.future_orders.delivery_days' => lang('admin::lang.locations.label_future_delivery_days'),
            'options.future_orders.collection_days' => lang('admin::lang.locations.label_future_collection_days'),
            'options.delivery_time_restriction' => lang('admin::lang.locations.label_delivery_time_restriction'),
            'options.collection_time_restriction' => lang('admin::lang.locations.label_collection_time_restriction'),
            'options.delivery_cancellation_timeout' => lang('admin::lang.locations.label_delivery_cancellation_timeout'),
            'options.collection_cancellation_timeout' => lang('admin::lang.locations.label_collection_cancellation_timeout'),
            'options.payments.*' => lang('admin::lang.locations.label_payments'),
            'options.reservation_time_interval' => lang('admin::lang.locations.label_reservation_time_interval'),
            'options.reservation_stay_time' => lang('admin::lang.locations.reservation_stay_time'),
            'options.auto_allocate_table' => lang('admin::lang.locations.label_auto_allocate_table'),
            'options.limit_guests' => lang('admin::lang.locations.label_limit_guests'),
            'options.limit_guests_count' => lang('admin::lang.locations.label_limit_guests_count'),
            'options.min_reservation_advance_time' => lang('admin::lang.locations.label_min_reservation_advance_time'),
            'options.max_reservation_advance_time' => lang('admin::lang.locations.label_max_reservation_advance_time'),
            'options.reservation_cancellation_timeout' => lang('admin::lang.locations.label_reservation_cancellation_timeout'),
            'location_status' => lang('admin::lang.label_status'),
            'permalink_slug' => lang('admin::lang.locations.label_permalink_slug'),
            'gallery.title' => lang('admin::lang.locations.label_gallery_title'),
            'gallery.description' => lang('admin::lang.label_description'),
        ];
    }

    public function rules()
    {
        return [
            'location_name' => ['required', 'between:2,32', 'unique:locations'],
            'location_email' => ['required', 'email:filter', 'max:96'],
            'location_telephone' => ['sometimes'],
            'location_address_1' => ['required', 'between:2,128'],
            'location_address_2' => ['max:128'],
            'location_city' => ['max:128'],
            'location_state' => ['max:128'],
            'location_postcode' => ['max:10'],
            'location_country_id' => ['required', 'integer'],
            'options.auto_lat_lng' => ['required', 'boolean'],
            'location_lat' => ['sometimes', 'numeric'],
            'location_lng' => ['sometimes', 'numeric'],
            'description' => ['max:3028'],
            'options.limit_orders' => ['boolean'],
            'options.limit_orders_count' => ['integer', 'min:1', 'max:999'],
            'options.offer_delivery' => ['boolean'],
            'options.offer_collection' => ['boolean'],
            'options.offer_reservation' => ['boolean'],
            'options.delivery_time_interval' => ['integer', 'min:5'],
            'options.collection_time_interval' => ['integer', 'min:5'],
            'options.delivery_lead_time' => ['integer', 'min:5'],
            'options.collection_lead_time' => ['integer', 'min:5'],
            'options.future_orders.enable_delivery' => ['boolean'],
            'options.future_orders.enable_collection' => ['boolean'],
            'options.future_orders.delivery_days' => ['integer'],
            'options.future_orders.collection_days' => ['integer'],
            'options.delivery_time_restriction' => ['nullable', 'integer', 'max:2'],
            'options.collection_time_restriction' => ['nullable', 'integer', 'max:2'],
            'options.delivery_cancellation_timeout' => ['integer', 'min:0', 'max:999'],
            'options.collection_cancellation_timeout' => ['integer', 'min:0', 'max:999'],
            'options.reservation_time_interval' => ['min:5', 'integer'],
            'options.reservation_stay_time' => ['min:5', 'integer'],
            'options.auto_allocate_table' => ['integer'],
            'options.limit_guests' => ['boolean'],
            'options.limit_guests_count' => ['integer', 'min:1', 'max:999'],
            'options.min_reservation_advance_time' => ['integer', 'min:0', 'max:999'],
            'options.max_reservation_advance_time' => ['integer', 'min:0', 'max:999'],
            'options.reservation_cancellation_timeout' => ['integer', 'min:0', 'max:999'],
            'location_status' => ['boolean'],
            'permalink_slug' => ['alpha_dash', 'max:255'],
            'gallery.title' => ['max:128'],
            'gallery.description' => ['max:255'],
        ];
    }
}
