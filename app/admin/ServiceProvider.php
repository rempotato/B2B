<?php

namespace Admin;

use Admin\Classes\Navigation;
use Admin\Classes\OnboardingSteps;
use Admin\Classes\PermissionManager;
use Admin\Classes\UserState;
use Admin\Classes\Widgets;
use Admin\Facades\AdminLocation;
use Admin\Facades\AdminMenu;
use Admin\Middleware\LogUserLastSeen;
use Igniter\Flame\ActivityLog\Models\Activity;
use Igniter\Flame\Foundation\Providers\AppServiceProvider;
use Igniter\Flame\Support\ClassLoader;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use System\Classes\MailManager;
use System\Libraries\Assets;
use System\Models\Settings;

class ServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     * @return void
     */
    public function boot()
    {
        parent::boot('admin');

        $this->defineEloquentMorphMaps();

        if ($this->app->runningInAdmin()) {
            $this->resolveFlashSessionKey();
            $this->replaceNavMenuItem();

            $this->app['router']->pushMiddlewareToGroup('web', LogUserLastSeen::class);
        }
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        parent::register('admin');

        // Provide backward compatibility for old model class names
        $this->registerModelClassAliases();

        $this->registerAssets();
        $this->registerActivityTypes();
        $this->registerMailTemplates();
        $this->registerSchedule();

        if ($this->app->runningInAdmin()) {
            $this->registerSystemSettings();
            $this->registerPermissions();
            $this->registerDashboardWidgets();
            $this->registerBulkActionWidgets();
            $this->registerFormWidgets();
            $this->registerMainMenuItems();
            $this->registerNavMenuItems();
            $this->registerOnboardingSteps();
        }
    }

    protected function registerMailTemplates()
    {
        MailManager::instance()->registerCallback(function (MailManager $manager) {
            $manager->registerMailTemplates([
                'admin::_mail.order_update' => 'lang:system::lang.mail_templates.text_order_update',
                'admin::_mail.reservation_update' => 'lang:system::lang.mail_templates.text_reservation_update',
                'admin::_mail.password_reset' => 'lang:system::lang.mail_templates.text_password_reset_alert',
                'admin::_mail.password_reset_request' => 'lang:system::lang.mail_templates.text_password_reset_request_alert',
            ]);
        });
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            if ($this->app->runningInAdmin()) {
                $manager->registerSourcePath(app_path('admin/assets'));

                $manager->addFromManifest('~/app/admin/views/_meta/assets.json', 'admin');
            }

            // Admin asset bundles
            $manager->registerBundle('scss', '~/app/admin/assets/scss/admin.scss', null, 'admin');
            $manager->registerBundle('js', [
                '~/app/system/assets/ui/flame.js',
                '~/app/admin/assets/node_modules/js-cookie/dist/js.cookie.js',
                '~/app/admin/assets/node_modules/select2/dist/js/select2.min.js',
                '~/app/admin/assets/node_modules/metismenu/dist/metisMenu.min.js',
                '~/app/admin/assets/js/src/app.js',
            ], '~/app/admin/assets/js/admin.js', 'admin');
        });
    }

    /*
     * Register dashboard widgets
     */
    protected function registerDashboardWidgets()
    {
        Widgets::instance()->registerDashboardWidgets(function (Widgets $manager) {
            $manager->registerDashboardWidget(\System\DashboardWidgets\Activities::class, [
                'label' => 'Recent activities',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\System\DashboardWidgets\Cache::class, [
                'label' => 'Cache Usage',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\System\DashboardWidgets\News::class, [
                'label' => 'Latest News',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Admin\DashboardWidgets\Statistics::class, [
                'label' => 'Statistics widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Admin\DashboardWidgets\Onboarding::class, [
                'label' => 'Onboarding widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Admin\DashboardWidgets\Charts::class, [
                'label' => 'Charts widget',
                'context' => 'dashboard',
            ]);
        });
    }

    protected function registerBulkActionWidgets()
    {
        Widgets::instance()->registerBulkActionWidgets(function (Widgets $manager) {
            $manager->registerBulkActionWidget(\Admin\BulkActionWidgets\Status::class, [
                'code' => 'status',
            ]);

            $manager->registerBulkActionWidget(\Admin\BulkActionWidgets\Delete::class, [
                'code' => 'delete',
            ]);
        });
    }

    /**
     * Register widgets
     */
    protected function registerFormWidgets()
    {
        Widgets::instance()->registerFormWidgets(function (Widgets $manager) {
            $manager->registerFormWidget(\Admin\FormWidgets\CodeEditor::class, [
                'label' => 'Code editor',
                'code' => 'codeeditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\ColorPicker::class, [
                'label' => 'Color picker',
                'code' => 'colorpicker',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\Connector::class, [
                'label' => 'Connector',
                'code' => 'connector',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\DataTable::class, [
                'label' => 'Data Table',
                'code' => 'datatable',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\DatePicker::class, [
                'label' => 'Date picker',
                'code' => 'datepicker',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\MapArea::class, [
                'label' => 'Map Area',
                'code' => 'maparea',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\MapView::class, [
                'label' => 'Map View',
                'code' => 'mapview',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\MarkdownEditor::class, [
                'label' => 'Markdown Editor',
                'code' => 'markdowneditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\MediaFinder::class, [
                'label' => 'Media finder',
                'code' => 'mediafinder',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\MenuOptionEditor::class, [
                'label' => 'Menu Option Editor',
                'code' => 'menuoptioneditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\PermissionEditor::class, [
                'label' => 'Permission Editor',
                'code' => 'permissioneditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\RecordEditor::class, [
                'label' => 'Record Editor',
                'code' => 'recordeditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\Relation::class, [
                'label' => 'Relationship',
                'code' => 'relation',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\Repeater::class, [
                'label' => 'Repeater',
                'code' => 'repeater',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\RichEditor::class, [
                'label' => 'Rich editor',
                'code' => 'richeditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\StatusEditor::class, [
                'label' => 'Status Editor',
                'code' => 'statuseditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\ScheduleEditor::class, [
                'label' => 'Schedule Editor',
                'code' => 'scheduleeditor',
            ]);

            $manager->registerFormWidget(\Admin\FormWidgets\StockEditor::class, [
                'label' => 'Stock Editor',
                'code' => 'stockeditor',
            ]);
        });
    }

    /**
     * Register admin top menu navigation items
     */
    protected function registerMainMenuItems()
    {
        AdminMenu::registerCallback(function (Navigation $manager) {
            $manager->registerMainItems([
                'preview' => [
                    'icon' => 'fa-store',
                    'attributes' => [
                        'class' => 'nav-link front-end',
                        'title' => 'lang:admin::lang.side_menu.storefront',
                        'href' => root_url(),
                        'target' => '_blank',
                    ],
                ],
                'activity' => [
                    'label' => 'lang:admin::lang.text_activity_title',
                    'icon' => 'fa-bell',
                    'badge' => 'badge-danger',
                    'type' => 'dropdown',
                    'badgeCount' => [\System\Models\Activity::class, 'unreadCount'],
                    'markAsRead' => [\System\Models\Activity::class, 'markAllAsRead'],
                    'options' => [\System\Models\Activity::class, 'listMenuActivities'],
                    'partial' => '~/app/system/views/activities/latest',
                    'viewMoreUrl' => admin_url('activities'),
                    'permission' => 'Admin.Activities',
                    'attributes' => [
                        'class' => 'nav-link',
                        'href' => '',
                        'data-toggle' => 'dropdown',
                    ],
                ],
                'settings' => [
                    'type' => 'partial',
                    'path' => 'top_settings_menu',
                    'badgeCount' => [\System\Models\Settings::class, 'updatesCount'],
                    'options' => [\System\Models\Settings::class, 'listMenuSettingItems'],
                    'permission' => 'Site.Settings',
                ],
                'user' => [
                    'type' => 'partial',
                    'path' => 'top_nav_user_menu',
                    'options' => [\Admin\Classes\UserPanel::class, 'listMenuLinks'],
                ],
            ]);
        });

        Event::listen('admin.menu.extendUserMenuLinks', function (Collection $items) {
            $items->put('userState', [
                'priority' => 10,
                'label' => 'admin::lang.text_set_status',
                'iconCssClass' => 'fa fa-circle fa-fw text-'.UserState::forUser()->getStatusColorName(),
                'attributes' => [
                    'data-toggle' => 'modal',
                    'data-target' => '#editStaffStatusModal',
                    'role' => 'button',
                ],
            ]);
        });
    }

    /**
     * Register admin menu navigation items
     */
    protected function registerNavMenuItems()
    {
        AdminMenu::registerCallback(function (Navigation $manager) {
            $manager->registerNavItems([
                'dashboard' => [
                    'priority' => 0,
                    'class' => 'dashboard admin',
                    'href' => admin_url('dashboard'),
                    'icon' => 'fa-tachometer-alt',
                    'title' => lang('admin::lang.side_menu.dashboard'),
                ],
                'restaurant' => [
                    'priority' => 10,
                    'class' => 'restaurant',
                    'icon' => 'fa-store',
                    'title' => lang('admin::lang.side_menu.restaurant'),
                    'child' => [
                        'locations' => [
                            'priority' => 10,
                            'class' => 'locations',
                            'href' => admin_url('locations'),
                            'title' => lang('admin::lang.side_menu.location'),
                            'permission' => 'Admin.Locations',
                        ],
                        'menus' => [
                            'priority' => 20,
                            'class' => 'menus',
                            'href' => admin_url('menus'),
                            'title' => lang('admin::lang.side_menu.menu'),
                            'permission' => 'Admin.Menus',
                        ],
                        'categories' => [
                            'priority' => 30,
                            'class' => 'categories',
                            'href' => admin_url('categories'),
                            'title' => lang('admin::lang.side_menu.category'),
                            'permission' => 'Admin.Categories',
                        ],
                        'mealtimes' => [
                            'priority' => 40,
                            'class' => 'mealtimes',
                            'href' => admin_url('mealtimes'),
                            'title' => lang('admin::lang.side_menu.mealtimes'),
                            'permission' => 'Admin.Mealtimes',
                        ],
                        'tables' => [
                            'priority' => 50,
                            'class' => 'tables',
                            'href' => admin_url('tables'),
                            'title' => lang('admin::lang.side_menu.table'),
                            'permission' => 'Admin.Tables',
                        ],
                    ],
                ],
                'sales' => [
                    'priority' => 30,
                    'class' => 'sales',
                    'icon' => 'fa-chart-bar',
                    'title' => lang('admin::lang.side_menu.sale'),
                    'child' => [
                        'orders' => [
                            'priority' => 10,
                            'class' => 'orders',
                            'href' => admin_url('orders'),
                            'title' => lang('admin::lang.side_menu.order'),
                            'permission' => 'Admin.Orders',
                        ],
                        'reservations' => [
                            'priority' => 20,
                            'class' => 'reservations',
                            'href' => admin_url('reservations'),
                            'title' => lang('admin::lang.side_menu.reservation'),
                            'permission' => 'Admin.Reservations',
                        ],
                        'statuses' => [
                            'priority' => 40,
                            'class' => 'statuses',
                            'href' => admin_url('statuses'),
                            'title' => lang('admin::lang.side_menu.status'),
                            'permission' => 'Admin.Statuses',
                        ],
                        'payments' => [
                            'priority' => 50,
                            'class' => 'payments',
                            'href' => admin_url('payments'),
                            'title' => lang('admin::lang.side_menu.payment'),
                            'permission' => 'Admin.Payments',
                        ],
                    ],
                ],
                'marketing' => [
                    'priority' => 40,
                    'class' => 'marketing',
                    'icon' => 'fa-chart-line',
                    'title' => lang('admin::lang.side_menu.marketing'),
                    'child' => [],
                ],
                'customers' => [
                    'priority' => 100,
                    'class' => 'customers',
                    'icon' => 'fa-user',
                    'href' => admin_url('customers'),
                    'title' => lang('admin::lang.side_menu.customer'),
                    'permission' => 'Admin.Customers',
                ],
                'design' => [
                    'priority' => 200,
                    'class' => 'design',
                    'icon' => 'fa-paint-brush',
                    'title' => lang('admin::lang.side_menu.design'),
                    'child' => [
                        'themes' => [
                            'priority' => 10,
                            'class' => 'themes',
                            'href' => admin_url('themes'),
                            'title' => lang('admin::lang.side_menu.theme'),
                            'permission' => 'Site.Themes',
                        ],
                        'mail_templates' => [
                            'priority' => 20,
                            'class' => 'mail_templates',
                            'href' => admin_url('mail_templates'),
                            'title' => lang('admin::lang.side_menu.mail_template'),
                            'permission' => 'Admin.MailTemplates',
                        ],
                    ],
                ],
                'localisation' => [
                    'priority' => 300,
                    'class' => 'localisation',
                    'icon' => 'fa-globe',
                    'title' => lang('admin::lang.side_menu.localisation'),
                    'child' => [
                        'languages' => [
                            'priority' => 10,
                            'class' => 'languages',
                            'href' => admin_url('languages'),
                            'title' => lang('admin::lang.side_menu.language'),
                            'permission' => 'Site.Languages',
                        ],
                        'currencies' => [
                            'priority' => 20,
                            'class' => 'currencies',
                            'href' => admin_url('currencies'),
                            'title' => lang('admin::lang.side_menu.currency'),
                            'permission' => 'Site.Currencies',
                        ],
                        'countries' => [
                            'priority' => 30,
                            'class' => 'countries',
                            'href' => admin_url('countries'),
                            'title' => lang('admin::lang.side_menu.country'),
                            'permission' => 'Site.Countries',
                        ],
                    ],
                ],
                'tools' => [
                    'priority' => 400,
                    'class' => 'tools',
                    'icon' => 'fa-wrench',
                    'title' => lang('admin::lang.side_menu.tool'),
                    'child' => [
                        'media_manager' => [
                            'priority' => 10,
                            'class' => 'media_manager',
                            'href' => admin_url('media_manager'),
                            'title' => lang('admin::lang.side_menu.media_manager'),
                            'permission' => 'Admin.MediaManager',
                        ],
                    ],
                ],
                'system' => [
                    'priority' => 999,
                    'class' => 'system',
                    'icon' => 'fa-cogs',
                    'title' => lang('admin::lang.side_menu.system'),
                    'child' => [
                        'users' => [
                            'priority' => 0,
                            'class' => 'users',
                            'href' => admin_url('users'),
                            'title' => lang('admin::lang.side_menu.user'),
                            'permission' => 'Admin.Staffs',
                        ],
                        'extensions' => [
                            'priority' => 10,
                            'class' => 'extensions',
                            'href' => admin_url('extensions'),
                            'title' => lang('admin::lang.side_menu.extension'),
                            'permission' => 'Admin.Extensions',
                        ],
                        'settings' => [
                            'priority' => 20,
                            'class' => 'settings',
                            'href' => admin_url('settings'),
                            'title' => lang('admin::lang.side_menu.setting'),
                            'permission' => 'Site.Settings',
                        ],
                        'updates' => [
                            'priority' => 30,
                            'class' => 'updates',
                            'href' => admin_url('updates'),
                            'title' => lang('admin::lang.side_menu.updates'),
                            'permission' => 'Site.Updates',
                        ],
                        'system_logs' => [
                            'priority' => 50,
                            'class' => 'system_logs',
                            'href' => admin_url('system_logs'),
                            'title' => lang('admin::lang.side_menu.system_logs'),
                            'permission' => 'Admin.SystemLogs',
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function replaceNavMenuItem()
    {
        AdminMenu::registerCallback(function (Navigation $manager) {
            // Change nav menu if single location mode is activated
            if (AdminLocation::check()) {
                $manager->mergeNavItem('locations', [
                    'href' => admin_url('locations/settings'),
                    'title' => lang('admin::lang.side_menu.setting'),
                ], 'restaurant');
            }
        });
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'addresses' => \Admin\Models\Address::class,
            'assignable_logs' => \Admin\Models\AssignableLog::class,
            'categories' => \Admin\Models\Category::class,
            'customer_groups' => \Admin\Models\CustomerGroup::class,
            'customers' => \Admin\Models\Customer::class,
            'ingredients' => \Admin\Models\Ingredient::class,
            'location_areas' => \Admin\Models\LocationArea::class,
            'locations' => \Admin\Models\Location::class,
            'mealtimes' => \Admin\Models\Mealtime::class,
            'menu_categories' => \Admin\Models\MenuCategory::class,
            'menu_item_option_values' => \Admin\Models\MenuItemOptionValue::class,
            'menu_option_values' => \Admin\Models\MenuOptionValue::class,
            'menu_options' => \Admin\Models\MenuOption::class,
            'menus' => \Admin\Models\Menu::class,
            'menus_specials' => \Admin\Models\MenuSpecial::class,
            'orders' => \Admin\Models\Order::class,
            'payment_logs' => \Admin\Models\PaymentLog::class,
            'payments' => \Admin\Models\Payment::class,
            'reservations' => \Admin\Models\Reservation::class,
            'status_history' => \Admin\Models\StatusHistory::class,
            'statuses' => \Admin\Models\Status::class,
            'stocks' => \Admin\Models\Stock::class,
            'stock_history' => \Admin\Models\StockHistory::class,
            'tables' => \Admin\Models\Table::class,
            'user_groups' => \Admin\Models\UserGroup::class,
            'users' => \Admin\Models\User::class,
            'working_hours' => \Admin\Models\WorkingHour::class,
        ]);
    }

    protected function resolveFlashSessionKey()
    {
        $this->app->resolving('flash', function (\Igniter\Flame\Flash\FlashBag $flash) {
            $flash->setSessionKey('flash_data_admin');
        });
    }

    protected function registerOnboardingSteps()
    {
        OnboardingSteps::registerCallback(function (OnboardingSteps $manager) {
            $manager->registerSteps([
                'admin::settings' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_settings',
                    'description' => 'admin::lang.dashboard.onboarding.help_settings',
                    'icon' => 'fa-gears',
                    'url' => admin_url('settings'),
                    'complete' => [\System\Models\Settings::class, 'onboardingIsComplete'],
                ],
                'admin::locations' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_locations',
                    'description' => 'admin::lang.dashboard.onboarding.help_locations',
                    'icon' => 'fa-store',
                    'url' => admin_url('locations'),
                    'complete' => [\Admin\Models\Location::class, 'onboardingIsComplete'],
                ],
                'admin::themes' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_themes',
                    'description' => 'admin::lang.dashboard.onboarding.help_themes',
                    'icon' => 'fa-paint-brush',
                    'url' => admin_url('themes'),
                    'complete' => [\System\Models\Theme::class, 'onboardingIsComplete'],
                ],
                'admin::extensions' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_extensions',
                    'description' => 'admin::lang.dashboard.onboarding.help_extensions',
                    'icon' => 'fa-plug',
                    'url' => admin_url('extensions'),
                    'complete' => [\System\Models\Extension::class, 'onboardingIsComplete'],
                ],
                'admin::payments' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_payments',
                    'description' => 'admin::lang.dashboard.onboarding.help_payments',
                    'icon' => 'fa-credit-card',
                    'url' => admin_url('payments'),
                    'complete' => [\Admin\Models\Payment::class, 'onboardingIsComplete'],
                ],
                'admin::menus' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_menus',
                    'description' => 'admin::lang.dashboard.onboarding.help_menus',
                    'icon' => 'fa-cutlery',
                    'url' => admin_url('menus'),
                ],
                'admin::mail' => [
                    'label' => 'admin::lang.dashboard.onboarding.label_mail',
                    'description' => 'admin::lang.dashboard.onboarding.help_mail',
                    'icon' => 'fa-envelope',
                    'url' => admin_url('settings/edit/mail'),
                ],
            ]);
        });
    }

    protected function registerActivityTypes()
    {
        Activity::registerCallback(function (Activity $manager) {
            $manager->registerActivityTypes([
                ActivityTypes\AssigneeUpdated::class => [
                    ActivityTypes\AssigneeUpdated::ORDER_ASSIGNED_TYPE,
                    ActivityTypes\AssigneeUpdated::RESERVATION_ASSIGNED_TYPE,
                ],
                ActivityTypes\StatusUpdated::class => [
                    ActivityTypes\StatusUpdated::ORDER_UPDATED_TYPE,
                    ActivityTypes\StatusUpdated::RESERVATION_UPDATED_TYPE,
                ],
            ]);
        });
    }

    protected function registerPermissions()
    {
        PermissionManager::instance()->registerCallback(function ($manager) {
            $manager->registerPermissions('Admin', [
                'Admin.Dashboard' => [
                    'label' => 'admin::lang.permissions.dashboard', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Allergens' => [
                    'label' => 'admin::lang.permissions.allergens', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Categories' => [
                    'label' => 'admin::lang.permissions.categories', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Menus' => [
                    'label' => 'admin::lang.permissions.menus', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Mealtimes' => [
                    'label' => 'admin::lang.permissions.mealtimes', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Locations' => [
                    'label' => 'admin::lang.permissions.locations', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Tables' => [
                    'label' => 'admin::lang.permissions.tables', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Orders' => [
                    'label' => 'admin::lang.permissions.orders', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.DeleteOrders' => [
                    'label' => 'admin::lang.permissions.delete_orders', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.AssignOrders' => [
                    'label' => 'admin::lang.permissions.assign_orders', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Reservations' => [
                    'label' => 'admin::lang.permissions.reservations', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.DeleteReservations' => [
                    'label' => 'admin::lang.permissions.delete_reservations', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.AssignReservations' => [
                    'label' => 'admin::lang.permissions.assign_reservations', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Payments' => [
                    'label' => 'admin::lang.permissions.payments', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.CustomerGroups' => [
                    'label' => 'admin::lang.permissions.customer_groups', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Customers' => [
                    'label' => 'admin::lang.permissions.customers', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Impersonate' => [
                    'label' => 'admin::lang.permissions.impersonate_staff', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.ImpersonateCustomers' => [
                    'label' => 'admin::lang.permissions.impersonate_customers', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.StaffGroups' => [
                    'label' => 'admin::lang.permissions.user_groups', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Staffs' => [
                    'label' => 'admin::lang.permissions.staffs', 'group' => 'admin::lang.permissions.name',
                ],
                'Admin.Statuses' => [
                    'label' => 'admin::lang.permissions.statuses', 'group' => 'admin::lang.permissions.name',
                ],
            ]);
        });
    }

    protected function registerSchedule()
    {
        Event::listen('console.schedule', function (Schedule $schedule) {
            // Check for assignables to assign every minute
            if (Classes\Allocator::isEnabled()) {
                $schedule->call(function () {
                    Classes\Allocator::allocate();
                })->name('Assignables Allocator')->withoutOverlapping(5)->runInBackground()->everyMinute();
            }
        });
    }

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'setup' => [
                    'label' => 'lang:admin::lang.settings.text_tab_setup',
                    'description' => 'lang:admin::lang.settings.text_tab_desc_setup',
                    'icon' => 'fa fa-toggle-on',
                    'priority' => 1,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/setup'),
                    'form' => '~/app/admin/models/config/setupsettings',
                    'request' => \Admin\Requests\SetupSettings::class,
                ],
                'user' => [
                    'label' => 'lang:admin::lang.settings.text_tab_user',
                    'description' => 'lang:admin::lang.settings.text_tab_desc_user',
                    'icon' => 'fa fa-user',
                    'priority' => 3,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/user'),
                    'form' => '~/app/admin/models/config/usersettings',
                    'request' => \Admin\Requests\UserSettings::class,
                ],
            ]);
        });
    }

    protected function registerModelClassAliases()
    {
        resolve(ClassLoader::class)->addAliases([
            \Admin\Models\Status::class => 'Admin\Models\Statuses_model',
            \Admin\Models\Location::class => 'Admin\Models\Locations_model',
            \Admin\Models\Customer::class => 'Admin\Models\Customers_model',
            \Admin\Models\Allergen::class => 'Admin\Models\Allergens_model',
            \Admin\Models\StockHistory::class => 'Admin\Models\Stock_history_model',
            \Admin\Models\MenuSpecial::class => 'Admin\Models\Menu_specials_model',
            \Admin\Models\WorkingHour::class => 'Admin\Models\Working_hours_model',
            \Admin\Models\Table::class => 'Admin\Models\Tables_model',
            \Admin\Models\AssignableLog::class => 'Admin\Models\Assignable_logs_model',
            \Admin\Models\StaffGroup::class => 'Admin\Models\Staff_groups_model',
            \Admin\Models\MenuCategory::class => 'Admin\Models\Menu_categories_model',
            \Admin\Models\Category::class => 'Admin\Models\Categories_model',
            \Admin\Models\MenuItemOptionValue::class => 'Admin\Models\Menu_item_option_values_model',
            \Admin\Models\User::class => 'Admin\Models\Users_model',
            \Admin\Models\StaffRole::class => 'Admin\Models\Staff_roles_model',
            \Admin\Models\Order::class => 'Admin\Models\Orders_model',
            \Admin\Models\Mealtime::class => 'Admin\Models\Mealtimes_model',
            \Admin\Models\Staff::class => 'Admin\Models\Staffs_model',
            \Admin\Models\CustomerGroup::class => 'Admin\Models\Customer_groups_model',
            \Admin\Models\StatusHistory::class => 'Admin\Models\Status_history_model',
            \Admin\Models\MenuOption::class => 'Admin\Models\Menu_options_model',
            \Admin\Models\PaymentProfile::class => 'Admin\Models\Payment_profiles_model',
            \Admin\Models\LocationArea::class => 'Admin\Models\Location_areas_model',
            \Admin\Models\Menu::class => 'Admin\Models\Menus_model',
            \Admin\Models\PaymentLog::class => 'Admin\Models\Payment_logs_model',
            \Admin\Models\Reservation::class => 'Admin\Models\Reservations_model',
            \Admin\Models\MenuOptionValue::class => 'Admin\Models\Menu_option_values_model',
            \Admin\Models\Stock::class => 'Admin\Models\Stocks_model',
            \Admin\Models\Address::class => 'Admin\Models\Addresses_model',
            \Admin\Models\UserPreference::class => 'Admin\Models\User_preferences_model',
            \Admin\Models\Payment::class => 'Admin\Models\Payments_model',
        ]);
    }
}
