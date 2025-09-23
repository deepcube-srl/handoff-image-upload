# Handoff Image Upload

![Static Badge](https://img.shields.io/badge/packagist-released-yellow?style=for-the-badge&logo=packagist&logoColor=white&color=green&link=https%3A%2F%2Fpackagist.org%2Fpackages%2Fdeepcube%2Fhandoff-image-upload)
![Static Badge](https://img.shields.io/badge/filamentphp-3.x-yellow?style=for-the-badge&logo=filament&link=https%3A%2F%2Ffilamentphp.com%2Fdocs%2F3.x%2Fpanels%2Finstallation)

A Filament component for image uploads with support for camera, QR Code, and direct upload. Allows users to upload images directly from the current device or using a QR Code to upload from a mobile device.

## Features

- **Filament Integration**: Native component for Filament Forms
- **Multiple Upload Methods**: Direct upload, camera capture, or via QR Code
- **Temporary Management**: Automatic handling of temporary images
- **Multilingual**: Support for customizable translations
- **Cleanup Command**: Automatic command to clean temporary images

## Installation

Install the package via Composer:

```bash
composer require deepcube/handoff-image-upload
```

Run the migrations:

```bash
php artisan migrate
```

Publish the configuration files (optional):

```bash
php artisan vendor:publish --tag="handoff-image-upload-config"
```

Publish the views (optional):

```bash
php artisan vendor:publish --tag="handoff-image-upload-views"
```

## Filament Forms Integration

To use the component in a Filament form, add the field to your Form Schema:

```php
use Deepcube\HandoffImageUpload\HandoffImageUpload;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            HandoffImageUpload::make('profile_image')
                ->label('Profile Image'),
                
            HandoffImageUpload::make('document_scan')
                ->label('Document Scan'),
                
            // Other fields...
        ]);
}
```

### Complete Resource Example:

```php
<?php

namespace App\Filament\Resources;

use App\Models\User;
use Deepcube\HandoffImageUpload\HandoffImageUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),
                    
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                    
                HandoffImageUpload::make('avatar')
                    ->label('User Avatar'),
                    
                HandoffImageUpload::make('document')
                    ->label('Identity Document'),
            ]);
    }
    
    // ... rest of the Resource
}
```

## Temporary Images Cleanup Command

The package includes a command to automatically clean temporary images:

### Basic Usage

```bash
php artisan handoff-image:cleanup-temp
```

### Available Options

```bash
# Delete files older than 48 hours
php artisan handoff-image:cleanup-temp --hours=48

# Dry-run mode (shows what would be deleted without actually deleting)
php artisan handoff-image:cleanup-temp --dry-run

# Combination of options
php artisan handoff-image:cleanup-temp --hours=12 --dry-run
```

### Automation with Task Scheduler

Add the command to your `app/Console/Kernel.php` to run it automatically:

```php
protected function schedule(Schedule $schedule)
{
    // Daily cleanup of temporary images older than 24 hours
    $schedule->command('handoff-image:cleanup-temp')
             ->daily()
             ->at('02:00');
             
    // Or every 6 hours
    $schedule->command('handoff-image:cleanup-temp --hours=6')
             ->everySixHours();
}
```

## Translations

### Publishing Translation Files

To customize translations, publish the language files:

```bash
php artisan vendor:publish --tag="handoff-image-upload-translations"
```

Translation files will be published to `lang/vendor/handoff-image-upload/`.

### Translation Structure

The package includes translations for:
- **English** (`en/handoff-image-upload.php`)
- **Italian** (`it/handoff-image-upload.php`)

### Adding New Languages

1. Create a new folder for the language in `lang/vendor/handoff-image-upload/`:
   ```bash
   mkdir -p lang/vendor/handoff-image-upload/es
   ```

2. Copy the English file as a base:
   ```bash
   cp lang/vendor/handoff-image-upload/en/handoff-image-upload.php \
      lang/vendor/handoff-image-upload/es/handoff-image-upload.php
   ```

3. Translate the strings in the new file:
   ```php
   <?php
   // lang/vendor/handoff-image-upload/es/handoff-image-upload.php
   return [
       'take_photo_instruction' => 'Toma una foto para subirla',
       'switch_camera' => 'Cambiar cámara',
       'take_photo' => 'Tomar foto',
       'retry' => 'Reintentar',
       'confirm' => 'Confirmar',
       // ... other translations
   ];
   ```

### Customizing Existing Translations

After publishing the translations, you can modify the files in `lang/vendor/handoff-image-upload/` to customize the messages according to your needs.

## Usage

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Deepcube Srl](https://deepcube.eu)
<!--
- [All Contributors](../../contributors) 
-->

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
