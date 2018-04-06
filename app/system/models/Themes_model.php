<?php namespace System\Models;

use File;
use Main\Classes\Theme;
use Main\Classes\ThemeManager;
use Model;
use URL;

/**
 * Themes Model Class
 * @package System
 */
class Themes_model extends Model
{
    /**
     * @var array data cached array
     */
    protected static $dataCache = [];

    /**
     * @var string The database table code
     */
    protected $table = 'themes';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'theme_id';

    protected $fillable = ['name', 'code', 'version', 'description'];

    public $casts = [
        'data' => 'serialize',
    ];

    /**
     * @var ThemeManager
     */
    public $manager = null;

    /**
     * @var \Main\Classes\Theme
     */
    public $themeClass = null;

    public static function getDataFromTheme(Theme $theme)
    {
        $dirName = $theme->getDirName();
        if ($data = array_get(self::$dataCache, $dirName)) {
            return $data;
        }

        $model = self::whereCode($dirName)->first();

        $data = ($model AND is_array($model->data)) ? $model->data : [];

        return self::$dataCache[$dirName] = $data;
    }

    //
    // Events
    //

    public function afterFetch()
    {
        $this->applyThemeManager();
    }

    //
    // Scopes
    //

    public function scopeIsEnabled($query)
    {
        $query->where('status', 1);
    }

    //
    // Manager
    //

    /**
     * Attach the theme object to this class
     * @return boolean
     */
    public function applyThemeManager()
    {
        $code = $this->code;

        if (!$code)
            return FALSE;

        $themeManager = ThemeManager::instance();
        if (!$themeClass = $themeManager->findTheme($code)) {
            return FALSE;
        }

        $themePath = File::localToPublic($themeClass->getPath());
        $themeClass->screenshot = URL::asset($themePath.'/screenshot.png');

        $this->manager = $themeManager;
        $this->themeClass = $themeClass;

        $this->description = !strlen($this->description) ? $themeClass->description : $this->description;

        return TRUE;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getFieldsConfig()
    {
        $fields = [];
        $customizeConfig = $this->themeClass->getConfigValue('form', []);
        foreach ($customizeConfig as $section => $item) {
            foreach (array_get($item, 'fields', []) as $name => $field) {
                if (!isset($field['tab']))
                    $field['tab'] = $item['title'];

                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    public function getFieldValues()
    {
        $customizeConfig = $this->themeClass->getConfigValue('form', []);

        return $this->data ?: [];
    }

    //
    // Helpers
    //

    public static function syncAll()
    {
        $themes = self::get();

        $installedThemes = [];
        $themeManager = ThemeManager::instance();
        foreach ($themeManager->paths() as $code => $path) {

            if (!($themeClass = $themeManager->findTheme($code))) continue;

            $themeMeta = (object)$themeClass;
            $installedThemes[] = $name = isset($themeMeta->name) ? $themeMeta->name : $code;

            // Only add themes whose meta code match their directory name
            // or theme has no record
            if (
                $code != $name OR
                $extension = $themes->where('code', $name)->first()
            ) continue;

            self::create([
                'name'        => isset($themeMeta->label) ? $themeMeta->label : title_case($code),
                'code'        => $name,
                'version'     => isset($themeMeta->version) ? $themeMeta->version : '1.0.0',
                'description' => isset($themeMeta->description) ? $themeMeta->description : '',
            ]);
        }

        // Disable themes not found in file system
        // This allows admin to remove an enabled theme from admin UI after deleting files
        self::whereNotIn('code', $installedThemes)->update(['status' => FALSE]);

        self::updateInstalledThemes();
    }

    /**
     * Update installed extensions config value
     */
    public static function updateInstalledThemes()
    {
        $installedThemes = self::select('status', 'name')->lists('status', 'code')->all();

        if (!is_array($installedThemes))
            $installedThemes = [];

        $installedThemes = array_map(function ($status) {
            return (bool) $status;
        }, $installedThemes);

        setting()->set('installed_themes', $installedThemes);
        setting()->save();
    }

    /**
     * Activate theme
     *
     * @param string $code
     *
     * @return bool|mixed
     */
    public static function activateTheme($code)
    {
        if (empty($code) OR !$theme = self::whereCode($code)->first())
            return FALSE;

        params()->set('default_themes.main', $theme->code);

        foreach ($theme->themeClass->requires as $require => $version) {
            Extensions_model::install($require);
        }

        return $theme;
    }

    /**
     * Create child theme from existing theme files and data
     *
     * @param string $theme_code
     * @param array $files
     * @param bool $copy_data
     *
     * @return bool
     */
    public static function copyTheme($themeCode, $copy_data = TRUE)
    {
        $themeModel = self::where('code', $themeCode)->first();
        if (!$themeModel)
            return FALSE;

        $childTheme = $themeModel->replicate();
        $childTheme->code = self::getUniqueThemeCode("{$themeCode}-child");
        $childTheme->name = "{$themeModel->name} Child";
        $childTheme->save();

        ThemeManager::instance()->createChild($themeCode, $childTheme->code);

        return TRUE;
    }

    /**
     * Find an existing theme in DB by theme code
     *
     * @param string $code
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public static function themeExists($code)
    {
        return self::where('code', $code)->first() ? TRUE : FALSE;
    }

    /**
     * Create a unique theme code
     *
     * @param string $theme_code
     * @param int $count
     *
     * @return string
     */
    protected static function getUniqueThemeCode($themeCode, $count = 0)
    {
        do {
            $newThemeCode = ($count > 0) ? "{$themeCode}-{$count}" : $themeCode;
            $count++;
        } // Already exist in DB? Try again
        while (self::themeExists($newThemeCode));

        return $newThemeCode;
    }

    /**
     * Delete a single theme by code
     *
     * @param string $theme_code
     * @param bool $delete_data
     *
     * @return bool
     */
    public static function deleteTheme($themeCode, $deleteData = TRUE)
    {
        $themeModel = self::where('code', $themeCode)->first();

        if ($themeModel AND ($deleteData OR !$themeModel->data)) {
            $themeModel->delete();
        }

        $filesDeleted = ThemeManager::instance()->removeTheme($themeCode);

        return $filesDeleted;
    }
}